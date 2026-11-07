<?php

namespace App\Http\Controllers\API\Mobile\Collector;

use App\Http\Controllers\Controller;
use App\Models\WasteDelivery;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
    use ApiResponse;

    /**
     * Update delivery status (start pickup, complete, cancel)
     */
    public function updateStatus(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user->hasRole('collector')) {
            return $this->error('Unauthorized. Only collectors can access this endpoint.', 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:on_progress,collected,cancelled',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $delivery = WasteDelivery::with('serviceAccount')->find($id);

        if (!$delivery) {
            return $this->error('Delivery not found.', 404);
        }

        // Verify collector is assigned to this area (with optional RW check)
        $hasAssignment = DB::table('collector_assignments as ca')
            ->join('schedules as s', 'ca.schedule_id', '=', 's.id')
            ->join('service_accounts as sa', 's.area_id', '=', 'sa.area_id')
            ->where('ca.user_id', $user->id)
            ->where('sa.id', $delivery->service_account_id)
            ->where('ca.start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ca.end_date')
                  ->orWhere('ca.end_date', '>=', now());
            })
            ->where(function ($q) {
                // If RW is assigned, check if service account's RW matches
                $q->whereNull('ca.rw_id')
                  ->orWhereRaw('ca.rw_id = sa.rw_id');
            })
            ->exists();

        if (!$hasAssignment) {
            return $this->error('You are not assigned to collect from this area.', 403);
        }

        // Status transition validation
        $allowedTransitions = [
            'scheduled' => ['on_progress', 'cancelled'],
            'on_progress' => ['collected', 'cancelled'],
        ];

        if (!isset($allowedTransitions[$delivery->status]) ||
            !in_array($request->status, $allowedTransitions[$delivery->status])) {
            return $this->error(
                "Cannot change status from '{$delivery->status}' to '{$request->status}'",
                400
            );
        }

        $delivery->status = $request->status;

        if ($request->has('note')) {
            $delivery->note = $request->note;
        }

        // Jika status collected, set confirmation_status ke pending untuk menunggu konfirmasi warga
        if ($request->status === 'collected') {
            $delivery->confirmation_status = 'pending';
            $delivery->collected_at = now();
        }

        $delivery->save();

        $message = 'Delivery status updated successfully.';
        if ($request->status === 'collected') {
            $message = 'Sampah berhasil diambil. Menunggu konfirmasi dari warga.';
        }

        return $this->ok([
            'id' => $delivery->id,
            'status' => $delivery->status,
            'confirmation_status' => $delivery->confirmation_status,
            'scheduled_at' => $delivery->scheduled_at?->toIso8601String(),
            'collected_at' => $delivery->collected_at?->toIso8601String(),
            'note' => $delivery->note,
            'service_account' => [
                'id' => $delivery->serviceAccount->id,
                'name' => $delivery->serviceAccount->name,
                'address' => $delivery->serviceAccount->address,
            ],
        ], ['message' => $message]);
    }

    /**
     * Get delivery detail
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user->hasRole('collector')) {
            return $this->error('Unauthorized. Only collectors can access this endpoint.', 403);
        }

        $delivery = WasteDelivery::with([
            'serviceAccount.area.parent',
            'serviceAccount.rw',
            'wasteDeliveryItems.waste',
            'wasteDeliveryItems.pocketSize'
        ])->find($id);

        if (!$delivery) {
            return $this->error('Delivery not found.', 404);
        }

        return $this->ok([
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
                'latitude' => $delivery->serviceAccount->latitude,
                'longitude' => $delivery->serviceAccount->longitude,
                'area' => $delivery->serviceAccount->area ? [
                    'id' => $delivery->serviceAccount->area->id,
                    'name' => $delivery->serviceAccount->area->name,
                    'parent' => $delivery->serviceAccount->area->parent ? [
                        'id' => $delivery->serviceAccount->area->parent->id,
                        'name' => $delivery->serviceAccount->area->parent->name,
                    ] : null,
                ] : null,
                'rw' => $delivery->serviceAccount->rw ? [
                    'id' => $delivery->serviceAccount->rw->id,
                    'name' => $delivery->serviceAccount->rw->name,
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
                    ],
                ];
            })->values(),
            'confirmation_status' => $delivery->confirmation_status,
            'collected_at' => $delivery->collected_at?->toIso8601String(),
            'photo_url' => $delivery->photo_url ? url($delivery->photo_url) : null,
        ]);
    }
}
