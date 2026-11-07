<?php

namespace App\Http\Controllers\API\Mobile\Resident;

use App\Http\Controllers\Controller;
use App\Models\WasteDelivery;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class WasteDeliveryController extends Controller
{
    use ApiResponse;

    /**
     * Get list of waste deliveries for resident
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = WasteDelivery::query()
            ->whereHas('serviceAccount', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['serviceAccount.area']);

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by service account
        if ($serviceAccountId = $request->input('service_account_id')) {
            $query->where('service_account_id', $serviceAccountId);
        }

        // Filter by date range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('scheduled_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('scheduled_at', '<=', $endDate);
        }

        $perPage = $request->input('per_page', 15);
        $deliveries = $query->orderByDesc('scheduled_at')->paginate($perPage);

        $data = $deliveries->getCollection()->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'status' => $delivery->status,
                'scheduled_at' => $delivery->scheduled_at?->toIso8601String(),
                'total_amount' => (float) $delivery->total_amount,
                'note' => $delivery->note,
                'service_account' => [
                    'id' => $delivery->serviceAccount->id,
                    'name' => $delivery->serviceAccount->name,
                    'address' => $delivery->serviceAccount->address,
                    'area' => $delivery->serviceAccount->area ? [
                        'id' => $delivery->serviceAccount->area->id,
                        'name' => $delivery->serviceAccount->area->name,
                    ] : null,
                ],
                'confirmation_status' => $delivery->confirmation_status,
                'collected_at' => $delivery->collected_at?->toIso8601String() ?? null,
                'created_at' => $delivery->created_at?->toIso8601String(),
            ];
        });

        return $this->ok([
            'deliveries' => $data,
            'meta' => [
                'current_page' => $deliveries->currentPage(),
                'last_page' => $deliveries->lastPage(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
            ],
        ]);
    }

    /**
     * Get single delivery detail
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $delivery = WasteDelivery::with([
            'serviceAccount.area.parent',
            'wasteDeliveryItems.waste',
            'wasteDeliveryItems.pocketSize',
            'invoice'
        ])
        ->whereHas('serviceAccount', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->find($id);

        if (!$delivery) {
            return $this->error('Delivery not found.', 404);
        }

        $data = [
            'id' => $delivery->id,
            'status' => $delivery->status,
            'scheduled_at' => $delivery->scheduled_at?->toIso8601String(),
            'total_amount' => (float) $delivery->total_amount,
            'note' => $delivery->note,
            'service_account' => [
                'id' => $delivery->serviceAccount->id,
                'name' => $delivery->serviceAccount->name,
                'address' => $delivery->serviceAccount->address,
                'contact_phone' => $delivery->serviceAccount->contact_phone,
                'area' => $delivery->serviceAccount->area ? [
                    'id' => $delivery->serviceAccount->area->id,
                    'name' => $delivery->serviceAccount->area->name,
                    'level' => $delivery->serviceAccount->area->level,
                    'parent' => $delivery->serviceAccount->area->parent ? [
                        'id' => $delivery->serviceAccount->area->parent->id,
                        'name' => $delivery->serviceAccount->area->parent->name,
                    ] : null,
                ] : null,
            ],
            'items' => $delivery->wasteDeliveryItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'waste' => [
                        'id' => $item->waste->id,
                        'category' => $item->waste->category,
                        'code' => $item->waste->code,
                    ],
                    'pocket_size' => [
                        'id' => $item->pocketSize->id,
                        'name' => $item->pocketSize->name,
                        'capacity' => $item->pocketSize->capacity,
                    ],
                ];
            })->values(),
            'invoice' => $delivery->invoices->first() ? [
                'id' => $delivery->invoices->first()->id,
                'invoice_number' => $delivery->invoices->first()->invoice_number,
                'total_amount' => (float) $delivery->invoices->first()->total_amount,
                'status' => $delivery->invoices->first()->status,
                'issued_at' => $delivery->invoices->first()->issued_at?->toIso8601String(),
            ] : null,
            'created_at' => $delivery->created_at?->toIso8601String(),
            'updated_at' => $delivery->updated_at?->toIso8601String(),
        ];

        return $this->ok($data);
    }

    /**
     * Get next scheduled pickup
     */
    public function nextPickup(Request $request)
    {
        $user = $request->user();

        $serviceAccountId = $request->input('service_account_id');

        $query = WasteDelivery::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['scheduled', 'on_progress'])
            ->with(['serviceAccount.area.parent']);

        if ($serviceAccountId) {
            $query->where('service_account_id', $serviceAccountId);
        }

        $nextPickup = $query->orderBy('scheduled_at')->first();

        if (!$nextPickup) {
            return $this->ok([
                'next_pickup' => null,
                'message' => 'No scheduled pickup found.',
            ]);
        }

        return $this->ok([
            'next_pickup' => [
                'id' => $nextPickup->id,
                'status' => $nextPickup->status,
                'scheduled_at' => $nextPickup->scheduled_at?->toIso8601String(),
                'total_amount' => (float) $nextPickup->total_amount,
                'service_account' => [
                    'id' => $nextPickup->serviceAccount->id,
                    'name' => $nextPickup->serviceAccount->name,
                    'address' => $nextPickup->serviceAccount->address,
                    'area' => $nextPickup->serviceAccount->area ? [
                        'id' => $nextPickup->serviceAccount->area->id,
                        'name' => $nextPickup->serviceAccount->area->name,
                        'parent' => $nextPickup->serviceAccount->area->parent ? [
                            'name' => $nextPickup->serviceAccount->area->parent->name,
                        ] : null,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * Get delivery statistics for resident
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $serviceAccountId = $request->input('service_account_id');

        $query = WasteDelivery::where('user_id', $user->id);

        if ($serviceAccountId) {
            $query->where('service_account_id', $serviceAccountId);
        }

        $totalDeliveries = $query->count();
        $completedDeliveries = (clone $query)->where('status', 'completed')->count();
        $scheduledDeliveries = (clone $query)->whereIn('status', ['pending', 'confirmed', 'in_progress'])->count();
        $cancelledDeliveries = (clone $query)->whereIn('status', ['cancelled', 'skipped'])->count();

        // Get total amount earned (dari completed deliveries)
        $totalEarned = (clone $query)
            ->where('status', 'completed')
            ->sum('total_amount');

        return $this->ok([
            'statistics' => [
                'total_deliveries' => $totalDeliveries,
                'completed' => $completedDeliveries,
                'scheduled' => $scheduledDeliveries,
                'cancelled' => $cancelledDeliveries,
                'total_earned' => (float) $totalEarned,
            ],
        ]);
    }

    /**
     * Confirm pickup by resident
     * Called when resident confirms that waste has been collected
     */
    public function confirmPickup(Request $request, string $id)
    {
        $user = $request->user();

        $delivery = WasteDelivery::whereHas('serviceAccount', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->find($id);

        if (!$delivery) {
            return $this->error('Delivery not found.', 404);
        }

        // Hanya bisa konfirmasi jika status = collected dan confirmation_status = pending
        if ($delivery->status !== 'collected') {
            return $this->error('Pengambilan belum dilakukan oleh petugas.', 400);
        }

        if ($delivery->confirmation_status !== 'pending') {
            return $this->error('Pengambilan sudah dikonfirmasi sebelumnya.', 400);
        }

        // Update confirmation status dan status delivery
        $delivery->confirmation_status = 'confirmed';
        $delivery->confirmed_at = now();
        $delivery->status = 'completed';
        $delivery->completed_at = now();
        $delivery->save();

        return $this->ok([
            'id' => $delivery->id,
            'status' => $delivery->status,
            'confirmation_status' => $delivery->confirmation_status,
            'confirmed_at' => $delivery->confirmed_at?->toIso8601String(),
            'completed_at' => $delivery->completed_at?->toIso8601String(),
        ], 'Terima kasih! Pengambilan sampah telah dikonfirmasi.');
    }
}
