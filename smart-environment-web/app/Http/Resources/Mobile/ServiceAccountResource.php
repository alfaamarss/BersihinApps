<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ServiceAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
    $area = $this->resource->relationLoaded('area') ? $this->area : null;
    $parentArea = $area?->parent;
    $rw = $this->resource->relationLoaded('rw') ? $this->rw : null;
    $schedules = $this->resource->relationLoaded('schedules') ? $this->schedules : collect();

        $scheduleDays = $schedules->map(function ($schedule) {
            $dayLabel = $this->translateDayName($schedule->day_of_week);
            $timeLabel = $schedule->pickup_time ? $schedule->pickup_time->format('H:i') : null;

            return [
                'day_of_week' => $schedule->day_of_week,
                'pickup_time' => $timeLabel,
                'label' => $timeLabel ? "{$dayLabel} • {$timeLabel}" : $dayLabel,
            ];
        })->values();

        $unpaidInvoices = $this->relationLoaded('invoices')
            ? $this->invoices->where('status', 'not_paid')
            : collect();

        $latestDelivery = $this->relationLoaded('wasteDeliveries')
            ? $this->wasteDeliveries->sortByDesc('scheduled_at')->first()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nama' => $this->name,
            'contact_phone' => $this->contact_phone,
            'phone' => $this->contact_phone,
            'address' => $this->address,
            'alamat_lengkap' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'area' => $area ? [
                'id' => $area->id,
                'name' => $area->name,
                'level' => $area->level,
                'parent' => $parentArea ? [
                    'id' => $parentArea->id,
                    'name' => $parentArea->name,
                    'level' => $parentArea->level,
                ] : null,
            ] : null,
            'rw' => $rw ? [
                'id' => $rw->id,
                'name' => $rw->name,
            ] : null,
            'kelurahan' => $area?->name,
            'kecamatan' => $parentArea?->name,
            'schedule_days' => $scheduleDays,
            'hari_pengangkutan' => $scheduleDays->pluck('label')->implode(' & '),
            'stats' => [
                'unpaid_invoice_total' => (float) $unpaidInvoices->sum('total_amount'),
                'unpaid_invoice_count' => $unpaidInvoices->count(),
                'latest_delivery' => $latestDelivery ? [
                    'id' => $latestDelivery->id,
                    'status' => $latestDelivery->status,
                    'scheduled_at' => optional($latestDelivery->scheduled_at)->toIso8601String(),
                    'total_amount' => (float) $latestDelivery->total_amount,
                ] : null,
            ],
        ];
    }

        protected function translateDayName(?string $day): string
        {
            if (! $day) {
                return '';
            }

            return match (strtolower($day)) {
                'monday' => 'Senin',
                'tuesday' => 'Selasa',
                'wednesday' => 'Rabu',
                'thursday' => 'Kamis',
                'friday' => 'Jumat',
                'saturday' => 'Sabtu',
                'sunday' => 'Minggu',
                default => Str::title($day),
            };
        }
}
