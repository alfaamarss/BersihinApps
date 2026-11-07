<?php

namespace App\Http\Controllers\API\Mobile\Collector;

use App\Http\Controllers\Controller;
use App\Models\CollectorAssignment;
use App\Models\WasteDelivery;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get collector dashboard with today's tasks and statistics
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Check if user is a collector
        if (!$user->hasRole('collector')) {
            return $this->error('Unauthorized. Only collectors can access this endpoint.', 403);
        }

        $today = Carbon::today();
        $dayOfWeek = $today->format('l'); // Monday, Tuesday, etc.

        // Get today's assignments
        $todayAssignments = CollectorAssignment::query()
            ->where('user_id', $user->id)
            ->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->with(['schedule.area.parent', 'rw'])
            ->whereHas('schedule', function ($q) use ($dayOfWeek) {
                $q->where('day_of_week', $dayOfWeek);
            })
            ->get();

        // Get deliveries for today based on assignments
        $areaIds = $todayAssignments->pluck('schedule.area_id')->filter()->unique()->toArray();

        $todayDeliveries = WasteDelivery::query()
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->where('pickup_date', $today)
            ->whereHas('serviceAccount', function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds);
                // Uncomment line below to filter only active service accounts
                // ->where('status', 'active');
            })
            ->with(['serviceAccount.area', 'user'])
            ->get();

        // Statistics - using schedule-based system
        $scheduleIds = $todayAssignments->pluck('schedule_id')->unique()->toArray();

        $stats = [
            'total_assignments' => $todayAssignments->count(),
            'total_deliveries' => $todayDeliveries->count(),
            'completed_today' => WasteDelivery::query()
                ->whereIn('schedule_id', $scheduleIds)
                ->whereDate('collected_at', $today)
                ->where('status', 'completed')
                ->count(),
            'pending_collections' => $todayDeliveries->where('status', 'scheduled')->count(),
            'in_progress' => $todayDeliveries->where('status', 'on_progress')->count(),
        ];

        // Recent deliveries (last 10 completed)
        $recentCollections = WasteDelivery::query()
            ->whereIn('schedule_id', $scheduleIds)
            ->where('status', 'completed')
            ->with(['serviceAccount'])
            ->orderByDesc('collected_at')
            ->limit(10)
            ->get()
            ->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'status' => $delivery->status,
                    'collected_at' => $delivery->collected_at?->toIso8601String(),
                    'total_amount' => (float) $delivery->total_amount,
                    'service_account' => $delivery->serviceAccount ? [
                        'id' => $delivery->serviceAccount->id,
                        'name' => $delivery->serviceAccount->name,
                        'address' => $delivery->serviceAccount->address,
                    ] : null,
                ];
            });

        // Map assignments to areas
        $areas = $todayAssignments->map(function ($assignment) {
            return [
                'assignment_id' => $assignment->id,
                'area' => $assignment->schedule?->area ? [
                    'id' => $assignment->schedule->area->id,
                    'name' => $assignment->schedule->area->name,
                    'level' => $assignment->schedule->area->level,
                    'parent' => $assignment->schedule->area->parent ? [
                        'id' => $assignment->schedule->area->parent->id,
                        'name' => $assignment->schedule->area->parent->name,
                    ] : null,
                ] : null,
                'rw' => $assignment->rw ? [
                    'id' => $assignment->rw->id,
                    'name' => $assignment->rw->name,
                ] : null,
                'schedule' => [
                    'id' => $assignment->schedule->id,
                    'day_of_week' => $assignment->schedule->day_of_week,
                    'pickup_time' => $assignment->schedule->pickup_time,
                ],
            ];
        })->values();

        return $this->ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'today' => [
                'date' => $today->toDateString(),
                'day' => $dayOfWeek,
            ],
            'statistics' => $stats,
            'areas' => $areas,
            'recent_collections' => $recentCollections,
        ]);
    }
}
