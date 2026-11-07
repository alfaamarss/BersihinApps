<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\CollectorAssignment;
use App\Models\Schedule;
use App\Models\User;
use App\Services\WasteDeliveryGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CollectorAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = CollectorAssignment::query()
            ->with(['user', 'schedule.area', 'schedule.area.parent', 'rw.kelurahan'])
            ->orderBy('start_date', 'desc');

        // Filter by collector
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // Filter by kecamatan (parent area)
        if ($kecamatanId = $request->input('kecamatan_id')) {
            $query->whereHas('schedule.area', function($q) use ($kecamatanId) {
                $q->where('parent_id', $kecamatanId);
            });
        }

        // Filter by specific kelurahan (area)
        if ($areaId = $request->input('area_id')) {
            $query->whereHas('schedule', function($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });
        }

        // Filter by day
        if ($day = $request->input('day_of_week')) {
            $query->whereHas('schedule', function($q) use ($day) {
                $q->where('day_of_week', $day);
            });
        }

        // Filter by status (aktif/selesai)
        if ($status = $request->input('status')) {
            $today = now()->toDateString();
            if ($status === 'active') {
                $query->where(function($q) use ($today) {
                    $q->where('start_date', '<=', $today)
                      ->where(function($sq) use ($today) {
                          $sq->whereNull('end_date')
                             ->orWhere('end_date', '>=', $today);
                      });
                });
            } elseif ($status === 'ended') {
                $query->where('end_date', '<', $today);
            }
        }

        $assignments = $query->paginate(10)->withQueryString();

        // Get collectors (users with collector role)
        $collectors = User::role('collector')->orderBy('name')->get();

        // Get kecamatans that have kelurahans with schedules
        $kecamatans = Area::where('level', 'Kecamatan')
            ->whereHas('children', function($q) {
                $q->where('level', 'Kelurahan')
                  ->whereHas('schedules');
            })
            ->orderBy('name')
            ->get();

        $dayOptions = $this->dayOptions();

        $statusOptions = [
            ['value' => 'active', 'label' => 'Aktif'],
            ['value' => 'ended', 'label' => 'Nonaktif'],
        ];

        return view('admin.collector_assignments.index', compact(
            'assignments',
            'collectors',
            'kecamatans',
            'dayOptions',
            'statusOptions'
        ));
    }

    public function create(): View
    {
        $collectors = User::role('collector')->orderBy('name')->get();
        
        // Only get kecamatans that have kelurahans with schedules
        $kecamatans = Area::where('level', 'Kecamatan')
            ->whereHas('children', function($q) {
                $q->where('level', 'Kelurahan')
                  ->whereHas('schedules');
            })
            ->orderBy('name')
            ->get();

        return view('admin.collector_assignments.create', compact('collectors', 'kecamatans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'schedule_id' => ['required', 'exists:schedules,id'],
            'rw_id' => ['nullable', 'exists:rws,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'generate_deliveries' => ['nullable', 'boolean'],
        ]);

        // Check for kecamatan conflict - collector can only work in 1 kecamatan per day
        $kecamatanConflict = CollectorAssignment::checkKecamatanConflict(
            $data['user_id'],
            $data['schedule_id'],
            $data['start_date'],
            $data['end_date'] ?? null,
            null // excludeId
        );

        if ($kecamatanConflict) {
            $errorMessage = sprintf(
                'Collector %s sudah ditugaskan di kecamatan "%s" (kelurahan %s) pada hari %s (periode %s - %s). Collector hanya boleh ditugaskan ke kelurahan-kelurahan dalam 1 kecamatan yang sama pada hari yang sama.',
                $kecamatanConflict['collector_name'],
                $kecamatanConflict['existing_kecamatan'],
                $kecamatanConflict['existing_kelurahan'],
                $this->translateDay($kecamatanConflict['day']),
                $kecamatanConflict['existing_start'],
                $kecamatanConflict['existing_end']
            );
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['schedule_id' => $errorMessage]);
        }

        // Check for conflicts with existing assignments for same collector
        $conflict = CollectorAssignment::checkConflict(
            $data['user_id'],
            $data['schedule_id'],
            $data['start_date'],
            $data['end_date'] ?? null,
            null, // excludeId
            $data['rw_id'] ?? null
        );

        if ($conflict) {
            $rwText = isset($conflict['rw_info']) ? ' (' . $conflict['rw_info'] . ')' : '';
            $errorMessage = sprintf(
                'Collector %s sudah ditugaskan ke area "%s"%s pada hari %s (periode %s - %s). Tidak boleh ada penugasan duplikat ke area dan RW yang sama.',
                $conflict['collector_name'],
                $conflict['existing_area'],
                $rwText,
                $this->translateDay($conflict['day']),
                $conflict['existing_start'],
                $conflict['existing_end']
            );
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['schedule_id' => $errorMessage]);
        }

        // Check if area/RW already has another collector assigned
        $areaRwConflict = CollectorAssignment::checkAreaRwConflict(
            $data['schedule_id'],
            $data['start_date'],
            $data['end_date'] ?? null,
            null, // excludeId
            $data['rw_id'] ?? null
        );

        if ($areaRwConflict) {
            $rwText = isset($areaRwConflict['rw_info']) ? ' ' . $areaRwConflict['rw_info'] : '';
            $errorMessage = sprintf(
                'Area "%s"%s pada hari %s sudah ditugaskan ke collector %s (%s) untuk periode %s - %s. Tidak boleh ada 2 collector di area yang sama pada waktu yang sama.',
                $areaRwConflict['area'],
                $rwText,
                $this->translateDay($areaRwConflict['day']),
                $areaRwConflict['collector_name'],
                $areaRwConflict['collector_email'],
                $areaRwConflict['existing_start'],
                $areaRwConflict['existing_end']
            );
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['schedule_id' => $errorMessage]);
        }

        $assignment = CollectorAssignment::create($data);

        // Auto-generate deliveries jika diminta
        if ($request->input('generate_deliveries', false)) {
            try {
                $generator = app(WasteDeliveryGeneratorService::class);
                $count = $generator->generateForCollectorAssignment($assignment);
                
                Log::info("Auto-generated {$count} deliveries for collector assignment {$assignment->id}");
                
                return redirect()->route('admin.collector-assignments.index')
                    ->with('success', "Penugasan collector berhasil dibuat dan {$count} delivery telah di-generate untuk 30 hari ke depan.");
            } catch (\Exception $e) {
                Log::error("Failed to generate deliveries for assignment {$assignment->id}: " . $e->getMessage());
                
                return redirect()->route('admin.collector-assignments.index')
                    ->with('warning', 'Penugasan collector berhasil dibuat, tetapi gagal generate deliveries: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.collector-assignments.index')
            ->with('success', 'Penugasan collector berhasil dibuat.');
    }

    public function edit(CollectorAssignment $collectorAssignment): View
    {
        $collectors = User::role('collector')->orderBy('name')->get();
        
        // Only get kecamatans that have kelurahans with schedules
        $kecamatans = Area::where('level', 'Kecamatan')
            ->whereHas('children', function($q) {
                $q->where('level', 'Kelurahan')
                  ->whereHas('schedules');
            })
            ->orderBy('name')
            ->get();

        // Get kelurahans and schedules for the selected schedule
        $selectedKecamatanId = null;
        $kelurahans = collect();
        $schedules = collect();
        $selectedAreaId = null;

        $dayLabels = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        if ($collectorAssignment->schedule && $collectorAssignment->schedule->area) {
            $area = $collectorAssignment->schedule->area;
            $selectedAreaId = $area->id;

            if ($area->level === 'Kelurahan' && $area->parent_id) {
                $selectedKecamatanId = $area->parent_id;
                
                // Only get kelurahans that have schedules
                $kelurahans = Area::where('parent_id', $selectedKecamatanId)
                    ->where('level', 'Kelurahan')
                    ->whereHas('schedules')
                    ->orderBy('name')
                    ->get();

                // Get schedules for this area - formatted for JavaScript
                $schedules = Schedule::where('area_id', $selectedAreaId)
                    ->orderBy('day_of_week')
                    ->orderBy('pickup_time')
                    ->get()
                    ->map(function($schedule) use ($dayLabels) {
                        return [
                            'id' => $schedule->id,
                            'day' => $dayLabels[$schedule->day_of_week] ?? $schedule->day_of_week,
                            'time' => \Carbon\Carbon::parse($schedule->pickup_time)->format('H:i'),
                            'label' => ($dayLabels[$schedule->day_of_week] ?? $schedule->day_of_week) . ' - ' . \Carbon\Carbon::parse($schedule->pickup_time)->format('H:i'),
                        ];
                    });
            }
        }

        return view('admin.collector_assignments.edit', compact(
            'collectorAssignment',
            'collectors',
            'kecamatans',
            'kelurahans',
            'schedules',
            'selectedKecamatanId',
            'selectedAreaId'
        ));
    }

    public function update(Request $request, CollectorAssignment $collectorAssignment): RedirectResponse
    {
        // Build validation rules dynamically
        $rules = [
            'user_id' => ['sometimes', 'exists:users,id'],
            'schedule_id' => ['sometimes', 'exists:schedules,id'],
            'rw_id' => ['nullable', 'exists:rws,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'in:active,ended'],
        ];

        // If start_date is being changed and is different from original, it must be >= today
        if ($request->has('start_date') && $request->start_date !== $collectorAssignment->start_date->format('Y-m-d')) {
            $rules['start_date'][] = 'after_or_equal:today';
        }

        $data = $request->validate($rules);

        // Handle status change to 'ended' - set end_date to yesterday if not already set
        if (isset($data['status']) && $data['status'] === 'ended') {
            if (empty($data['end_date']) && (!$collectorAssignment->end_date || $collectorAssignment->end_date->gte(now()))) {
                $data['end_date'] = now()->subDay()->toDateString();
            }
        } elseif (isset($data['status']) && $data['status'] === 'active') {
            // If activating, clear end_date if it's in the past
            if ($collectorAssignment->end_date && $collectorAssignment->end_date->lt(now())) {
                $data['end_date'] = null;
            }
        }

        // Remove status from data as it's not a database column
        $statusChanged = isset($data['status']);
        $newStatus = $data['status'] ?? null;
        unset($data['status']);

        // Determine effective values for conflict checking
        $effectiveUserId = $data['user_id'] ?? $collectorAssignment->user_id;
        $effectiveScheduleId = $data['schedule_id'] ?? $collectorAssignment->schedule_id;
        $effectiveStartDate = $data['start_date'] ?? $collectorAssignment->start_date;
        $effectiveEndDate = array_key_exists('end_date', $data) ? $data['end_date'] : $collectorAssignment->end_date;
        $effectiveRwId = array_key_exists('rw_id', $data) ? $data['rw_id'] : $collectorAssignment->rw_id;

        // Check for kecamatan conflict - collector can only work in 1 kecamatan per day
        $kecamatanConflict = CollectorAssignment::checkKecamatanConflict(
            $effectiveUserId,
            $effectiveScheduleId,
            $effectiveStartDate,
            $effectiveEndDate,
            $collectorAssignment->id // excludeId
        );

        if ($kecamatanConflict) {
            $errorMessage = sprintf(
                'Collector %s sudah ditugaskan di kecamatan "%s" (kelurahan %s) pada hari %s (periode %s - %s). Collector hanya boleh ditugaskan ke kelurahan-kelurahan dalam 1 kecamatan yang sama pada hari yang sama.',
                $kecamatanConflict['collector_name'],
                $kecamatanConflict['existing_kecamatan'],
                $kecamatanConflict['existing_kelurahan'],
                $this->translateDay($kecamatanConflict['day']),
                $kecamatanConflict['existing_start'],
                $kecamatanConflict['existing_end']
            );
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['schedule_id' => $errorMessage]);
        }

        // Check for conflicts with existing assignments (excluding current assignment)
        $conflict = CollectorAssignment::checkConflict(
            $effectiveUserId,
            $effectiveScheduleId,
            $effectiveStartDate,
            $effectiveEndDate,
            $collectorAssignment->id, // Exclude current assignment from check
            $effectiveRwId
        );

        if ($conflict) {
            $rwText = isset($conflict['rw_info']) ? ' (' . $conflict['rw_info'] . ')' : '';
            $errorMessage = sprintf(
                'Collector %s sudah ditugaskan ke area "%s"%s pada hari %s (periode %s - %s). Tidak boleh ada penugasan duplikat ke area dan RW yang sama.',
                $conflict['collector_name'],
                $conflict['existing_area'],
                $rwText,
                $this->translateDay($conflict['day']),
                $conflict['existing_start'],
                $conflict['existing_end']
            );
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['schedule_id' => $errorMessage]);
        }

        // Check if area/RW already has another collector assigned (excluding current assignment)
        $areaRwConflict = CollectorAssignment::checkAreaRwConflict(
            $effectiveScheduleId,
            $effectiveStartDate,
            $effectiveEndDate,
            $collectorAssignment->id, // Exclude current assignment from check
            $effectiveRwId
        );

        if ($areaRwConflict) {
            $rwText = isset($areaRwConflict['rw_info']) ? ' ' . $areaRwConflict['rw_info'] : '';
            $errorMessage = sprintf(
                'Area "%s"%s pada hari %s sudah ditugaskan ke collector %s (%s) untuk periode %s - %s. Tidak boleh ada 2 collector di area yang sama pada waktu yang sama.',
                $areaRwConflict['area'],
                $rwText,
                $this->translateDay($areaRwConflict['day']),
                $areaRwConflict['collector_name'],
                $areaRwConflict['collector_email'],
                $areaRwConflict['existing_start'],
                $areaRwConflict['existing_end']
            );
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['schedule_id' => $errorMessage]);
        }

        $collectorAssignment->update($data);

        return redirect()->route('admin.collector-assignments.index')
            ->with('success', 'Penugasan collector berhasil diperbarui.');
    }

    public function destroy(CollectorAssignment $collectorAssignment): RedirectResponse
    {
        $collectorAssignment->delete();

        return redirect()->route('admin.collector-assignments.index')
            ->with('success', 'Penugasan collector berhasil dihapus.');
    }

    public function getKelurahans(Request $request)
    {
        $kecamatanId = $request->input('kecamatan_id');

        if (!$kecamatanId) {
            return response()->json([]);
        }

        // Only get kelurahans that have schedules
        $kelurahans = Area::where('parent_id', $kecamatanId)
            ->where('level', 'Kelurahan')
            ->whereHas('schedules')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($kelurahans);
    }

    public function getSchedules(Request $request)
    {
        $areaId = $request->input('area_id');

        if (!$areaId) {
            return response()->json([]);
        }

        $schedules = Schedule::where('area_id', $areaId)
            ->orderBy('day_of_week')
            ->orderBy('pickup_time')
            ->get(['id', 'day_of_week', 'pickup_time']);

        $dayLabels = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        $schedules = $schedules->map(function($schedule) use ($dayLabels) {
            return [
                'id' => $schedule->id,
                'day' => $dayLabels[$schedule->day_of_week] ?? $schedule->day_of_week,
                'time' => \Carbon\Carbon::parse($schedule->pickup_time)->format('H:i'),
                'label' => ($dayLabels[$schedule->day_of_week] ?? $schedule->day_of_week) . ' - ' . \Carbon\Carbon::parse($schedule->pickup_time)->format('H:i'),
            ];
        });

        return response()->json($schedules);
    }

    public function getRws(Request $request)
    {
        $kelurahanId = $request->input('kelurahan_id');

        if (!$kelurahanId) {
            return response()->json([]);
        }

        $rws = \App\Models\Rw::where('kelurahan_id', $kelurahanId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rws);
    }

    /**
     * Get RWs with their assignment status for a specific kelurahan, day, and date range
     * Used to show which RWs are already assigned to other collectors
     */
    public function getRwsWithAssignmentStatus(Request $request)
    {
        $kelurahanId = $request->input('kelurahan_id');
        $scheduleId = $request->input('schedule_id');
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date');
        $excludeAssignmentId = $request->input('exclude_assignment_id');

        if (!$kelurahanId) {
            return response()->json([]);
        }

        // Get all RWs for this kelurahan
        $rws = \App\Models\Rw::where('kelurahan_id', $kelurahanId)
            ->orderBy('name')
            ->get(['id', 'name']);

        // If no schedule selected, return RWs without assignment info
        if (!$scheduleId) {
            return response()->json($rws->map(function($rw) {
                return [
                    'id' => $rw->id,
                    'name' => $rw->name,
                    'assigned' => false,
                    'assigned_to' => null,
                ];
            }));
        }

        // Get schedule to find day_of_week
        $schedule = Schedule::find($scheduleId);
        if (!$schedule) {
            return response()->json($rws->map(function($rw) {
                return [
                    'id' => $rw->id,
                    'name' => $rw->name,
                    'assigned' => false,
                    'assigned_to' => null,
                ];
            }));
        }

        // Check if entire kelurahan is assigned
        $entireKelurahanAssigned = CollectorAssignment::checkEntireKelurahanAssigned(
            $kelurahanId,
            $schedule->day_of_week,
            $startDate,
            $endDate,
            $excludeAssignmentId
        );

        if ($entireKelurahanAssigned) {
            // All RWs are blocked because entire kelurahan is assigned
            return response()->json([
                'entire_kelurahan_assigned' => true,
                'assigned_to' => $entireKelurahanAssigned['collector_name'],
                'rws' => $rws->map(function($rw) use ($entireKelurahanAssigned) {
                    return [
                        'id' => $rw->id,
                        'name' => $rw->name,
                        'assigned' => true,
                        'assigned_to' => $entireKelurahanAssigned['collector_name'],
                    ];
                }),
            ]);
        }

        // Get assigned RWs
        $assignedRws = CollectorAssignment::getAssignedRws(
            $kelurahanId,
            $schedule->day_of_week,
            $startDate,
            $endDate,
            $excludeAssignmentId
        );

        // Create a map of assigned RW IDs
        $assignedRwMap = collect($assignedRws)->keyBy('rw_id');

        return response()->json([
            'entire_kelurahan_assigned' => false,
            'rws' => $rws->map(function($rw) use ($assignedRwMap) {
                $isAssigned = $assignedRwMap->has($rw->id);
                return [
                    'id' => $rw->id,
                    'name' => $rw->name,
                    'assigned' => $isAssigned,
                    'assigned_to' => $isAssigned ? $assignedRwMap[$rw->id]['collector_name'] : null,
                ];
            }),
        ]);
    }

    private function dayOptions(): array
    {
        return [
            ['value' => 'Monday', 'label' => 'Senin'],
            ['value' => 'Tuesday', 'label' => 'Selasa'],
            ['value' => 'Wednesday', 'label' => 'Rabu'],
            ['value' => 'Thursday', 'label' => 'Kamis'],
            ['value' => 'Friday', 'label' => 'Jumat'],
            ['value' => 'Saturday', 'label' => 'Sabtu'],
            ['value' => 'Sunday', 'label' => 'Minggu'],
        ];
    }

    private function translateDay(string $day): string
    {
        $translations = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        return $translations[$day] ?? $day;
    }

    /**
     * Get list of collectors for quick assign dropdown
     */
    public function getCollectors()
    {
        $collectors = User::role('collector')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json($collectors);
    }

    /**
     * Quick assign a collector to a schedule from calendar popup
     */
    public function quickAssign(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'schedule_id' => ['required', 'exists:schedules,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        // Default end_date to 30 days from start if not provided
        if (empty($data['end_date'])) {
            $data['end_date'] = \Carbon\Carbon::parse($data['start_date'])->addDays(30)->toDateString();
        }

        // Check for kecamatan conflict
        $kecamatanConflict = CollectorAssignment::checkKecamatanConflict(
            $data['user_id'],
            $data['schedule_id'],
            $data['start_date'],
            $data['end_date']
        );

        if ($kecamatanConflict) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Collector sudah ditugaskan di kecamatan "%s" pada hari %s.',
                    $kecamatanConflict['existing_kecamatan'],
                    $this->translateDay($kecamatanConflict['day'])
                )
            ], 422);
        }

        // Check for conflicts with existing assignments
        $conflict = CollectorAssignment::checkConflict(
            $data['user_id'],
            $data['schedule_id'],
            $data['start_date'],
            $data['end_date']
        );

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Collector sudah ditugaskan ke area "%s" pada hari %s.',
                    $conflict['existing_area'],
                    $this->translateDay($conflict['day'])
                )
            ], 422);
        }

        // Check if area already has another collector assigned
        $areaConflict = CollectorAssignment::checkAreaRwConflict(
            $data['schedule_id'],
            $data['start_date'],
            $data['end_date']
        );

        if ($areaConflict) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Area sudah ditugaskan ke collector %s.',
                    $areaConflict['collector_name']
                )
            ], 422);
        }

        try {
            $assignment = CollectorAssignment::create($data);

            // Auto-generate deliveries
            $generator = app(WasteDeliveryGeneratorService::class);
            $count = $generator->generateForCollectorAssignment($assignment);

            return response()->json([
                'success' => true,
                'message' => "Collector berhasil ditugaskan. {$count} jadwal pengambilan telah dibuat.",
                'assignment_id' => $assignment->id
            ]);
        } catch (\Exception $e) {
            Log::error("Quick assign failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menugaskan collector: ' . $e->getMessage()
            ], 500);
        }
    }
}
