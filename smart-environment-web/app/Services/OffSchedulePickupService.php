<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\ServiceAccount;
use App\Models\User;
use App\Models\WasteDelivery;
use App\Models\WastePricing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OffSchedulePickupService
{
    /**
     * Create new off-schedule pickup request for resident
     * 
     * @return WasteDelivery|array Returns WasteDelivery or [WasteDelivery, array of skipped pickups]
     */
    public function createRequest(User $resident, array $payload): WasteDelivery|array
    {
        return DB::transaction(function () use ($resident, $payload) {
            $serviceAccount = ServiceAccount::query()
                ->where('id', $payload['service_account_id'])
                ->where('user_id', $resident->id)
                ->firstOrFail();

            $this->assertNoOutstandingRequest($serviceAccount->id);

            $requestedPickupDate = Carbon::parse($payload['requested_pickup_date'])->startOfDay();

            // Check if there's already a scheduled pickup on the requested date
            $this->assertNoScheduledPickupOnDate($serviceAccount->id, $requestedPickupDate);
            $requestedPickupTime = isset($payload['requested_pickup_time'])
                ? Carbon::parse($payload['requested_pickup_time'])->format('H:i:s')
                : null;

            // Create delivery without pricing - collector will input waste items later
            $delivery = WasteDelivery::create([
                'service_account_id' => $serviceAccount->id,
                'schedule_id' => null,
                'pickup_date' => $requestedPickupDate,
                'pickup_type' => 'request',
                'request_status' => 'sent',
                'requested_at' => now(),
                'requested_pickup_date' => $requestedPickupDate,
                'requested_pickup_time' => $requestedPickupTime,
                'requested_by' => $resident->id,
                'bag_count' => 0, // Will be updated by collector
                'unit_price_snapshot' => 0,
                'base_amount' => 0,
                'extra_fee' => 0,
                'scheduled_at' => $this->buildScheduledAt($requestedPickupDate, $requestedPickupTime),
                'status' => 'pending',
                'confirmation_status' => 'pending',
                'resident_note' => $payload['resident_note'] ?? null,
                'total_amount' => 0,
            ]);

            // Handle skip_next_scheduled option
            $skippedPickups = [];
            if (!empty($payload['skip_next_scheduled'])) {
                $skippedPickups = $this->skipNextScheduledPickups($serviceAccount->id, $requestedPickupDate);
            }

            $freshDelivery = $delivery->fresh();

            // Return delivery with skipped pickups info if any
            if (!empty($skippedPickups)) {
                return [$freshDelivery, $skippedPickups];
            }

            return $freshDelivery;
        });
    }

    public function assignCollector(WasteDelivery $delivery, User $collector, ?int $bagCount = null, ?float $extraFee = null): WasteDelivery
    {
        if (!$collector->hasRole('collector')) {
            throw ValidationException::withMessages([
                'collector_id' => 'Pengguna terpilih bukan kolektor',
            ]);
        }

        return DB::transaction(function () use ($delivery, $collector, $bagCount, $extraFee) {
            if ($bagCount !== null) {
                $delivery->bag_count = max(0, (int) $bagCount);
            }

            // Use provided extra_fee or get from app settings
            if ($extraFee !== null) {
                $delivery->extra_fee = max(0, (float) $extraFee);
            } else {
                // Auto-apply surcharge from settings if not specified
                $settings = AppSetting::first();
                $delivery->extra_fee = (float) ($settings?->off_schedule_surcharge ?? 0);
            }

            $this->recalculateTotals($delivery);

            $delivery->assigned_collector_id = $collector->id;
            $delivery->request_status = 'processing';
            $delivery->status = 'pending'; // Ensure status is pending
            $delivery->save();

            return $delivery->fresh();
        });
    }

    public function collectorSubmit(WasteDelivery $delivery, array $payload): WasteDelivery
    {
        return DB::transaction(function () use ($delivery, $payload) {
            if (isset($payload['bag_count'])) {
                $delivery->bag_count = max(0, (int) $payload['bag_count']);
            }

            if (array_key_exists('extra_fee', $payload)) {
                $delivery->extra_fee = max(0, (float) $payload['extra_fee']);
            }

            if (!empty($payload['photo_path'])) {
                $delivery->photo_url = $payload['photo_path'];
            }

            $delivery->collected_at = now();
            $delivery->status = 'collected';
            $delivery->request_status = 'pending';
            $delivery->collector_notes = $payload['collector_notes'] ?? $delivery->collector_notes;

            $this->recalculateTotals($delivery);

            $delivery->save();

            return $delivery->fresh();
        });
    }

    public function markResidentConfirmation(WasteDelivery $delivery): array
    {
        return DB::transaction(function () use ($delivery) {
            $delivery->request_status = 'completed';
            $delivery->status = 'completed';
            $delivery->completed_at = now();
            $delivery->confirmation_status = 'confirmed';
            $delivery->confirmed_at = now(); // Add confirmed_at timestamp
            $delivery->save();

            $invoice = $delivery->invoice;

            if (!$invoice) {
                $invoice = $delivery->invoice()->create([
                    'service_account_id' => $delivery->service_account_id,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'issued_at' => now(),
                    'total_amount' => $delivery->total_amount,
                    'status' => 'not_paid',
                ]);
            }

            return [$delivery->fresh(), $invoice];
        });
    }

    public function markPaid(WasteDelivery $delivery): WasteDelivery
    {
        $delivery->request_status = 'paid';
        $delivery->status = 'completed';
        $delivery->save();

        return $delivery->fresh();
    }

    public function reject(WasteDelivery $delivery, string $reason): WasteDelivery
    {
        $delivery->request_status = 'rejected';
        $delivery->status = 'cancelled';
        $delivery->rejection_reason = $reason;
        $delivery->save();

        return $delivery->fresh();
    }

    public function updateCosts(WasteDelivery $delivery, int $bagCount, ?float $extraFee = null): WasteDelivery
    {
        $delivery->bag_count = max(0, $bagCount);

        if ($extraFee !== null) {
            $delivery->extra_fee = max(0, (float) $extraFee);
        }

        $this->recalculateTotals($delivery);
        $delivery->save();

        return $delivery->fresh();
    }

    protected function assertNoOutstandingRequest(int $serviceAccountId): void
    {
        $exists = WasteDelivery::query()
            ->where('service_account_id', $serviceAccountId)
            ->where('pickup_type', 'request')
            ->where(function ($q) {
                $q->whereNull('request_status')
                  ->orWhereNotIn('request_status', ['paid', 'rejected']);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'service_account_id' => 'Masih ada pengajuan pickup di luar jadwal yang belum selesai atau dibayar.',
            ]);
        }
    }

    /**
     * Assert no scheduled pickup exists on the requested date
     * Prevents creating off-schedule request on a day that already has scheduled pickup
     * 
     * @param int $serviceAccountId
     * @param Carbon $requestedDate
     * @throws ValidationException
     */
    protected function assertNoScheduledPickupOnDate(int $serviceAccountId, Carbon $requestedDate): void
    {
        $exists = WasteDelivery::query()
            ->where('service_account_id', $serviceAccountId)
            ->where('pickup_type', 'scheduled')
            ->whereDate('pickup_date', $requestedDate)
            ->whereNotIn('status', ['cancelled', 'skipped'])
            ->whereNotIn('confirmation_status', ['skipped', 'no_waste'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'requested_pickup_date' => 'Tanggal ini sudah ada jadwal pengambilan rutin. Silakan pilih tanggal lain.',
            ]);
        }
    }

    /**
     * Skip next scheduled pickups in the same week after the requested date
     * 
     * @param int $serviceAccountId
     * @param Carbon $requestedDate
     * @return array Array of skipped WasteDelivery
     */
    protected function skipNextScheduledPickups(int $serviceAccountId, Carbon $requestedDate): array
    {
        // Get end of week (Sunday) from requested date
        $endOfWeek = $requestedDate->copy()->endOfWeek(Carbon::SUNDAY);

        // Find scheduled pickups after requested date until end of week
        $scheduledPickups = WasteDelivery::query()
            ->where('service_account_id', $serviceAccountId)
            ->where('pickup_type', 'scheduled')
            ->whereBetween('pickup_date', [$requestedDate->copy()->addDay(), $endOfWeek])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotIn('confirmation_status', ['skipped', 'no_waste'])
            ->get();

        $skippedPickups = [];

        foreach ($scheduledPickups as $pickup) {
            $pickup->update([
                'status' => 'skipped',
                'confirmation_status' => 'skipped',
                'resident_note' => ($pickup->resident_note ? $pickup->resident_note . ' | ' : '') 
                    . 'Di-skip karena request pickup di luar jadwal pada ' . $requestedDate->format('d/m/Y'),
            ]);

            $skippedPickups[] = $pickup->fresh();
        }

        return $skippedPickups;
    }

    /**
     * Get next scheduled pickups in the same week for preview
     * Used to show user what pickups will be skipped
     * 
     * @param int $serviceAccountId
     * @param Carbon $requestedDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNextScheduledPickupsInWeek(int $serviceAccountId, Carbon $requestedDate): \Illuminate\Database\Eloquent\Collection
    {
        $endOfWeek = $requestedDate->copy()->endOfWeek(Carbon::SUNDAY);

        return WasteDelivery::query()
            ->where('service_account_id', $serviceAccountId)
            ->where('pickup_type', 'scheduled')
            ->whereBetween('pickup_date', [$requestedDate->copy()->addDay(), $endOfWeek])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotIn('confirmation_status', ['skipped', 'no_waste'])
            ->with('schedule')
            ->orderBy('pickup_date')
            ->get();
    }

    protected function resolveExtraFee(?float $override): float
    {
        if ($override !== null) {
            return max(0, (float) $override);
        }

        $settings = AppSetting::first();

        return (float) ($settings?->off_schedule_surcharge ?? 0);
    }

    protected function resolveActivePricing(?int $wasteId, ?int $pocketSizeId): WastePricing
    {
        $today = today();

        $query = WastePricing::query()
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            });

        if ($wasteId) {
            $query->where('waste_id', $wasteId);
        }

        if ($pocketSizeId) {
            $query->where('pocket_size_id', $pocketSizeId);
        }

        $pricing = $query->orderBy('price')->first();

        if (!$pricing) {
            throw ValidationException::withMessages([
                'pricing' => 'Harga kantong belum tersedia, silakan hubungi admin.',
            ]);
        }

        return $pricing;
    }

    protected function recalculateTotals(WasteDelivery $delivery): void
    {
        // For off-schedule pickups, pricing will be determined by collector when completing
        // So we allow assignment without unit_price_snapshot
        $unitPrice = $delivery->unit_price_snapshot ?? 0;

        $delivery->base_amount = $delivery->bag_count * $unitPrice;
        $delivery->total_amount = $delivery->base_amount + $delivery->extra_fee;
    }

    protected function buildScheduledAt(Carbon $date, ?string $time): ?Carbon
    {
        if (!$time) {
            return $date->copy()->setTime(8, 0);
        }

        [$hour, $minute, $second] = array_pad(explode(':', $time), 3, '00');

        return $date->copy()->setTime((int) $hour, (int) $minute, (int) $second);
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastInvoice = Invoice::whereDate('created_at', today())->latest()->first();
        $sequence = $lastInvoice
            ? (intval(substr($lastInvoice->invoice_number, -4)) + 1)
            : 1;

        return $prefix . '-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
