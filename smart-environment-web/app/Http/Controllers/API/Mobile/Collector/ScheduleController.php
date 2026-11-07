<?php

namespace App\Http\Controllers\API\Mobile\Collector;

use App\Http\Controllers\Controller;
use App\Models\CollectorAssignment;
use App\Models\WasteDelivery;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    use ApiResponse;

    /**
     * Get collector's schedules
     * Query params:
     * - date: YYYY-MM-DD (default: today)
     * - range: week|month (default: single day)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('collector')) {
            return $this->error('Unauthorized. Only collectors can access this endpoint.', 403);
        }

        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $range = $request->input('range', 'day'); // day, week, month

        // Determine date range
        switch ($range) {
            case 'week':
                $startDate = $date->copy()->startOfWeek();
                $endDate = $date->copy()->endOfWeek();
                break;
            case 'month':
                $startDate = $date->copy()->startOfMonth();
                $endDate = $date->copy()->endOfMonth();
                break;
            default:
                $startDate = $date->copy()->startOfDay();
                $endDate = $date->copy()->endOfDay();
        }

        // Get assignments for the date range
        $assignments = CollectorAssignment::query()
            ->where('user_id', $user->id)
            ->where('start_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $startDate);
            })
            ->with(['schedule.area.parent'])
            ->get();

        // Build schedule calendar
        $schedules = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayOfWeek = $currentDate->format('l');
            $dateString = $currentDate->toDateString();

            // Find assignments for this day
            $dayAssignments = $assignments->filter(function ($assignment) use ($dayOfWeek, $currentDate) {
                return $assignment->schedule->day_of_week === $dayOfWeek
                    && $assignment->start_date <= $currentDate->toDateString()
                    && (is_null($assignment->end_date) || $assignment->end_date >= $currentDate->toDateString());
            });

            if ($dayAssignments->count() > 0) {
                $areaIds = $dayAssignments->pluck('schedule.area_id')->filter()->unique()->toArray();

                // Get deliveries for this date
                $deliveries = WasteDelivery::query()
                    ->whereDate('scheduled_at', $dateString)
                    ->whereHas('serviceAccount', function ($q) use ($areaIds) {
                        $q->whereIn('area_id', $areaIds);
                        // Uncomment line below to filter only active service accounts
                        // ->where('status', 'active');
                    })
                    ->with(['serviceAccount'])
                    ->get();

                $schedules[] = [
                    'date' => $dateString,
                    'day_of_week' => $dayOfWeek,
                    'assignments' => $dayAssignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'area' => [
                                'id' => $assignment->schedule->area->id,
                                'name' => $assignment->schedule->area->name,
                                'level' => $assignment->schedule->area->level,
                            ],
                            'schedule' => [
                                'id' => $assignment->schedule->id,
                                'pickup_time' => $assignment->schedule->pickup_time,
                            ],
                        ];
                    })->values(),
                    'deliveries_count' => $deliveries->count(),
                    'deliveries' => $deliveries->map(function ($delivery) {
                        return [
                            'id' => $delivery->id,
                            'status' => $delivery->status,
                            'scheduled_at' => $delivery->scheduled_at?->toIso8601String(),
                            'service_account' => [
                                'id' => $delivery->serviceAccount->id,
                                'name' => $delivery->serviceAccount->name,
                                'address' => $delivery->serviceAccount->address,
                                'contact_phone' => $delivery->serviceAccount->contact_phone,
                                'latitude' => $delivery->serviceAccount->latitude,
                                'longitude' => $delivery->serviceAccount->longitude,
                            ],
                        ];
                    })->values(),
                ];
            }

            $currentDate->addDay();
        }

        return $this->ok([
            'range' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'type' => $range,
            ],
            'schedules' => $schedules,
        ]);
    }

    /**
     * Get deliveries for a specific area and date
     */
    public function deliveries(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('collector')) {
            return $this->error('Unauthorized. Only collectors can access this endpoint.', 403);
        }

        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'date' => 'required|date',
        ]);

        $areaId = $request->input('area_id');
        $date = Carbon::parse($request->input('date'));

        // Verify collector has assignment for this area on this date
        $dayOfWeek = $date->format('l');

        $hasAssignment = CollectorAssignment::query()
            ->where('user_id', $user->id)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->whereHas('schedule', function ($q) use ($areaId, $dayOfWeek) {
                $q->where('area_id', $areaId)
                  ->where('day_of_week', $dayOfWeek);
            })
            ->exists();

        if (!$hasAssignment) {
            return $this->error('You are not assigned to this area on this date.', 403);
        }

        // Get deliveries
        $deliveries = WasteDelivery::query()
            ->whereDate('scheduled_at', $date)
            ->whereHas('serviceAccount', function ($q) use ($areaId) {
                $q->where('area_id', $areaId);
                // Uncomment line below to filter only active service accounts
                // ->where('status', 'active');
            })
            ->with(['serviceAccount', 'wasteDeliveryItems'])
            ->get()
            ->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'status' => $delivery->status,
                    'confirmation_status' => $delivery->confirmation_status,
                    'scheduled_at' => $delivery->scheduled_at?->toIso8601String(),
                    'collected_at' => $delivery->collected_at?->toIso8601String(),
                    'total_amount' => (float) $delivery->total_amount,
                    'note' => $delivery->note,
                    'service_account' => [
                        'id' => $delivery->serviceAccount->id,
                        'name' => $delivery->serviceAccount->name,
                        'address' => $delivery->serviceAccount->address,
                        'contact_phone' => $delivery->serviceAccount->contact_phone,
                        'latitude' => $delivery->serviceAccount->latitude,
                        'longitude' => $delivery->serviceAccount->longitude,
                    ],
                    'items' => $delivery->wasteDeliveryItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'waste_id' => $item->waste_id,
                            'pocket_size_id' => $item->pocket_size_id,
                            'quantity' => $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'total_price' => (float) $item->total_price,
                        ];
                    }),
                ];
            });

        return $this->ok([
            'date' => $date->toDateString(),
            'area_id' => $areaId,
            'deliveries' => $deliveries,
        ]);
    }
}
