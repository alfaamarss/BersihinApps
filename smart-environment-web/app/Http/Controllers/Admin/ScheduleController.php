<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\CollectorAssignment;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Schedule::query()
            ->with(['area', 'area.parent'])
            ->withCount(['collectorAssignments as active_assignments_count' => function($q) {
                $today = now()->toDateString();
                $q->where('start_date', '<=', $today)
                  ->where(function($sq) use ($today) {
                      $sq->whereNull('end_date')
                         ->orWhere('end_date', '>=', $today);
                  });
            }])
            ->orderBy('day_of_week')
            ->orderBy('pickup_time');

        // Filter by kecamatan (parent area)
        if ($kecamatanId = $request->input('kecamatan_id')) {
            $query->whereHas('area', function($q) use ($kecamatanId) {
                $q->where('parent_id', $kecamatanId);
            });
        }

        // Filter by specific kelurahan (area)
        if ($areaId = $request->input('area_id')) {
            $query->where('area_id', $areaId);
        }

        // Filter by day
        if ($day = $request->input('day_of_week')) {
            if (in_array($day, ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'], true)) {
                $query->where('day_of_week', $day);
            }
        }

        $schedules = $query->paginate(10)->withQueryString();

        // Get all areas for reference (both Kecamatan and Kelurahan)
        $areas = Area::orderBy('name')->get();

        // Get only Kecamatan for district filter
        $kecamatans = Area::where('level', 'Kecamatan')->orderBy('name')->get();

        $dayOptions = $this->dayOptions();

        return view('admin.schedules.index', compact('schedules', 'areas', 'kecamatans', 'dayOptions'));
    }

    public function create(): View
    {
        $kecamatans = Area::where('level', 'Kecamatan')->orderBy('name')->get();
        $dayOptions = $this->dayOptions();

        return view('admin.schedules.create', compact('kecamatans', 'dayOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'area_id' => [
                'required', 
                'exists:areas,id',
                Rule::unique('schedules')->where(function ($query) use ($request) {
                    return $query->where('area_id', $request->area_id)
                                 ->where('day_of_week', $request->day_of_week);
                })
            ],
            'day_of_week' => ['required', Rule::in(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])],
            'pickup_time' => ['required', 'date_format:H:i'],
        ], [
            'area_id.unique' => 'Jadwal untuk area dan hari ini sudah ada. Silakan pilih area atau hari yang berbeda.',
        ]);

        // Convert to H:i:s format for database
        $data['pickup_time'] = $data['pickup_time'] . ':00';

        Schedule::create($data);

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil dibuat.');
    }

    public function edit(Schedule $schedule): View
    {
        $kecamatans = Area::where('level', 'Kecamatan')->orderBy('name')->get();

        // Get kelurahans for the selected kecamatan
        $selectedKecamatanId = null;
        $kelurahans = collect();

        if ($schedule->area) {
            if ($schedule->area->level === 'Kelurahan') {
                $selectedKecamatanId = $schedule->area->parent_id;
                $kelurahans = Area::where('parent_id', $selectedKecamatanId)
                    ->where('level', 'Kelurahan')
                    ->orderBy('name')
                    ->get();
            }
        }

        $dayOptions = $this->dayOptions();

        // Get active collector assignments for this schedule
        $activeAssignments = CollectorAssignment::where('schedule_id', $schedule->id)
            ->where(function($q) {
                $today = now()->toDateString();
                $q->where('start_date', '<=', $today)
                  ->where(function($sq) use ($today) {
                      $sq->whereNull('end_date')
                         ->orWhere('end_date', '>=', $today);
                  });
            })
            ->with(['user', 'rw'])
            ->get();

        return view('admin.schedules.edit', compact('schedule', 'kecamatans', 'kelurahans', 'selectedKecamatanId', 'dayOptions', 'activeAssignments'));
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'area_id' => [
                'sometimes', 
                'exists:areas,id',
                Rule::unique('schedules')->where(function ($query) use ($request) {
                    return $query->where('area_id', $request->area_id)
                                 ->where('day_of_week', $request->day_of_week);
                })->ignore($schedule->id)
            ],
            'day_of_week' => ['sometimes', Rule::in(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])],
            'pickup_time' => ['sometimes', 'date_format:H:i'],
            'force_update' => ['nullable', 'boolean'],
            'end_assignments' => ['nullable', 'boolean'],
        ], [
            'area_id.unique' => 'Jadwal untuk area dan hari ini sudah ada. Silakan pilih area atau hari yang berbeda.',
        ]);

        $forceUpdate = $request->boolean('force_update', false);
        $endAssignments = $request->boolean('end_assignments', false);

        // Check if area or day is being changed
        $areaChanged = isset($data['area_id']) && $data['area_id'] != $schedule->area_id;
        $dayChanged = isset($data['day_of_week']) && $data['day_of_week'] != $schedule->day_of_week;

        // Get active collector assignments for this schedule
        $activeAssignments = CollectorAssignment::where('schedule_id', $schedule->id)
            ->where(function($q) {
                $today = now()->toDateString();
                $q->where('start_date', '<=', $today)
                  ->where(function($sq) use ($today) {
                      $sq->whereNull('end_date')
                         ->orWhere('end_date', '>=', $today);
                  });
            })
            ->with(['user', 'rw', 'schedule.area.parent'])
            ->get();

        // If area or day changes and there are active assignments, check for conflicts
        if (($areaChanged || $dayChanged) && $activeAssignments->isNotEmpty()) {
            
            // If not forcing update, return with warning
            if (!$forceUpdate) {
                $dayLabels = [
                    'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                    'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
                ];
                
                $assignmentList = $activeAssignments->map(function($a) use ($dayLabels) {
                    $rwText = $a->rw ? ' (RW ' . $a->rw->name . ')' : '';
                    return sprintf(
                        '- %s%s: %s - %s',
                        $a->user->name,
                        $rwText,
                        $a->start_date->format('d M Y'),
                        $a->end_date ? $a->end_date->format('d M Y') : 'Tidak terbatas'
                    );
                })->join("\n");

                $warningMessage = sprintf(
                    "Jadwal ini memiliki %d penugasan collector yang aktif:\n%s\n\nMengubah area atau hari jadwal dapat menyebabkan konflik dengan jadwal lain yang sudah ada. Silakan pilih tindakan yang diinginkan.",
                    $activeAssignments->count(),
                    $assignmentList
                );

                return redirect()->back()
                    ->withInput()
                    ->with('schedule_conflict_warning', $warningMessage)
                    ->with('active_assignments', $activeAssignments);
            }

            // Check for kecamatan conflicts if area changes
            if ($areaChanged) {
                $newArea = Area::with('parent')->find($data['area_id']);
                $newKecamatanId = $newArea->parent_id ?? null;
                $newDayOfWeek = $data['day_of_week'] ?? $schedule->day_of_week;

                foreach ($activeAssignments as $assignment) {
                    // Check if this collector has other assignments in different kecamatan on the same day
                    $conflictingAssignments = CollectorAssignment::where('user_id', $assignment->user_id)
                        ->where('id', '!=', $assignment->id)
                        ->whereHas('schedule', function($q) use ($newDayOfWeek) {
                            $q->where('day_of_week', $newDayOfWeek);
                        })
                        ->whereHas('schedule.area', function($q) use ($newKecamatanId) {
                            $q->where('parent_id', '!=', $newKecamatanId);
                        })
                        ->where(function($q) {
                            $today = now()->toDateString();
                            $q->where('start_date', '<=', $today)
                              ->where(function($sq) use ($today) {
                                  $sq->whereNull('end_date')
                                     ->orWhere('end_date', '>=', $today);
                              });
                        })
                        ->with(['schedule.area.parent'])
                        ->first();

                    if ($conflictingAssignments) {
                        $dayLabels = [
                            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
                        ];

                        $errorMessage = sprintf(
                            'Tidak dapat mengubah jadwal. Collector %s sudah ditugaskan di kecamatan "%s" pada hari %s. Collector hanya boleh ditugaskan ke kelurahan-kelurahan dalam 1 kecamatan yang sama pada hari yang sama. Silakan akhiri penugasan collector terlebih dahulu atau pilih kecamatan yang sama.',
                            $assignment->user->name,
                            $conflictingAssignments->schedule->area->parent->name ?? '-',
                            $dayLabels[$newDayOfWeek] ?? $newDayOfWeek
                        );

                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['area_id' => $errorMessage]);
                    }
                }
            }

            // Check for kecamatan conflicts if only day changes
            if ($dayChanged && !$areaChanged) {
                $newDayOfWeek = $data['day_of_week'];
                $currentKecamatanId = $schedule->area->parent_id ?? null;

                foreach ($activeAssignments as $assignment) {
                    // Check if this collector has other assignments in different kecamatan on the NEW day
                    $conflictingAssignments = CollectorAssignment::where('user_id', $assignment->user_id)
                        ->where('id', '!=', $assignment->id)
                        ->whereHas('schedule', function($q) use ($newDayOfWeek) {
                            $q->where('day_of_week', $newDayOfWeek);
                        })
                        ->whereHas('schedule.area', function($q) use ($currentKecamatanId) {
                            $q->where('parent_id', '!=', $currentKecamatanId);
                        })
                        ->where(function($q) {
                            $today = now()->toDateString();
                            $q->where('start_date', '<=', $today)
                              ->where(function($sq) use ($today) {
                                  $sq->whereNull('end_date')
                                     ->orWhere('end_date', '>=', $today);
                              });
                        })
                        ->with(['schedule.area.parent'])
                        ->first();

                    if ($conflictingAssignments) {
                        $dayLabels = [
                            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
                        ];

                        $errorMessage = sprintf(
                            'Tidak dapat mengubah hari jadwal ke %s. Collector %s sudah ditugaskan di kecamatan "%s" pada hari tersebut. Collector hanya boleh ditugaskan ke kelurahan-kelurahan dalam 1 kecamatan yang sama pada hari yang sama.',
                            $dayLabels[$newDayOfWeek] ?? $newDayOfWeek,
                            $assignment->user->name,
                            $conflictingAssignments->schedule->area->parent->name ?? '-'
                        );

                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['day_of_week' => $errorMessage]);
                    }
                }
            }

            // If user chose to end assignments, end them before updating schedule
            if ($endAssignments) {
                $yesterday = now()->subDay()->toDateString();
                foreach ($activeAssignments as $assignment) {
                    $assignment->update(['end_date' => $yesterday]);
                }
            }
        }

        // Remove non-database fields
        unset($data['force_update'], $data['end_assignments']);

        // Convert to H:i:s format if pickup_time is provided
        if (isset($data['pickup_time'])) {
            $data['pickup_time'] = $data['pickup_time'] . ':00';
        }

        $schedule->update($data);

        $successMessage = 'Jadwal berhasil diperbarui.';
        if ($endAssignments && $activeAssignments->isNotEmpty()) {
            $successMessage .= sprintf(' %d penugasan collector telah diakhiri.', $activeAssignments->count());
        }

        return redirect()->route('admin.schedules.index')->with('success', $successMessage);
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        // Check for active collector assignments
        $activeAssignments = CollectorAssignment::where('schedule_id', $schedule->id)
            ->where(function($q) {
                $today = now()->toDateString();
                $q->where('start_date', '<=', $today)
                  ->where(function($sq) use ($today) {
                      $sq->whereNull('end_date')
                         ->orWhere('end_date', '>=', $today);
                  });
            })
            ->with(['user'])
            ->get();

        if ($activeAssignments->isNotEmpty()) {
            $collectorNames = $activeAssignments->pluck('user.name')->implode(', ');
            return redirect()->route('admin.schedules.index')
                ->with('error', sprintf(
                    'Tidak dapat menghapus jadwal karena masih memiliki %d penugasan collector aktif (%s). Silakan akhiri penugasan terlebih dahulu.',
                    $activeAssignments->count(),
                    $collectorNames
                ));
        }

        $schedule->delete();

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    public function getKelurahans(Request $request)
    {
        $kecamatanId = $request->input('kecamatan_id');

        if (!$kecamatanId) {
            return response()->json([]);
        }

        $kelurahans = Area::where('parent_id', $kecamatanId)
            ->where('level', 'Kelurahan')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($kelurahans);
    }

    /**
     * Check for potential conflicts when changing schedule area or day
     * Returns conflict information for AJAX validation
     */
    public function checkConflict(Request $request, Schedule $schedule)
    {
        $newAreaId = $request->input('area_id', $schedule->area_id);
        $newDayOfWeek = $request->input('day_of_week', $schedule->day_of_week);

        $areaChanged = $newAreaId != $schedule->area_id;
        $dayChanged = $newDayOfWeek != $schedule->day_of_week;

        // Get active collector assignments for this schedule
        $activeAssignments = CollectorAssignment::where('schedule_id', $schedule->id)
            ->where(function($q) {
                $today = now()->toDateString();
                $q->where('start_date', '<=', $today)
                  ->where(function($sq) use ($today) {
                      $sq->whereNull('end_date')
                         ->orWhere('end_date', '>=', $today);
                  });
            })
            ->with(['user', 'rw', 'schedule.area.parent'])
            ->get();

        $response = [
            'has_active_assignments' => $activeAssignments->isNotEmpty(),
            'assignment_count' => $activeAssignments->count(),
            'assignments' => [],
            'has_kecamatan_conflict' => false,
            'kecamatan_conflicts' => [],
            'area_changed' => $areaChanged,
            'day_changed' => $dayChanged,
        ];

        if ($activeAssignments->isEmpty()) {
            return response()->json($response);
        }

        // Format assignments for display
        $dayLabels = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];

        foreach ($activeAssignments as $assignment) {
            $response['assignments'][] = [
                'id' => $assignment->id,
                'collector_name' => $assignment->user->name ?? '-',
                'rw_name' => $assignment->rw ? 'RW ' . $assignment->rw->name : 'Seluruh RW',
                'start_date' => $assignment->start_date->format('d M Y'),
                'end_date' => $assignment->end_date ? $assignment->end_date->format('d M Y') : 'Tidak terbatas',
            ];
        }

        // Check for kecamatan conflicts
        if ($areaChanged || $dayChanged) {
            $newArea = $areaChanged ? Area::with('parent')->find($newAreaId) : $schedule->area;
            $newKecamatanId = $newArea->parent_id ?? null;

            foreach ($activeAssignments as $assignment) {
                $conflictingAssignment = CollectorAssignment::where('user_id', $assignment->user_id)
                    ->where('id', '!=', $assignment->id)
                    ->whereHas('schedule', function($q) use ($newDayOfWeek) {
                        $q->where('day_of_week', $newDayOfWeek);
                    })
                    ->whereHas('schedule.area', function($q) use ($newKecamatanId) {
                        $q->where('parent_id', '!=', $newKecamatanId);
                    })
                    ->where(function($q) {
                        $today = now()->toDateString();
                        $q->where('start_date', '<=', $today)
                          ->where(function($sq) use ($today) {
                              $sq->whereNull('end_date')
                                 ->orWhere('end_date', '>=', $today);
                          });
                    })
                    ->with(['schedule.area.parent'])
                    ->first();

                if ($conflictingAssignment) {
                    $response['has_kecamatan_conflict'] = true;
                    $response['kecamatan_conflicts'][] = [
                        'collector_name' => $assignment->user->name,
                        'conflicting_kecamatan' => $conflictingAssignment->schedule->area->parent->name ?? '-',
                        'conflicting_kelurahan' => $conflictingAssignment->schedule->area->name ?? '-',
                        'day' => $dayLabels[$newDayOfWeek] ?? $newDayOfWeek,
                    ];
                }
            }
        }

        return response()->json($response);
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

    /**
     * Show calendar view for schedules
     */
    public function calendar(Request $request): View
    {
        $kecamatans = Area::where('level', 'Kecamatan')->orderBy('name')->get();
        
        return view('admin.schedules.calendar', compact('kecamatans'));
    }

    /**
     * Get calendar events for FullCalendar
     */
    public function calendarEvents(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $kecamatanId = $request->input('kecamatan_id');
        $areaId = $request->input('area_id');

        // Fix for timezone offset space issue (e.g. "2025-12-01T00:00:00 07:00")
        if ($start && strlen($start) > 10) {
            $start = substr($start, 0, 10);
        }
        if ($end && strlen($end) > 10) {
            $end = substr($end, 0, 10);
        }

        // Parse dates
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        // Build schedule query
        $query = Schedule::query()
            ->with(['area.parent', 'collectorAssignments' => function($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate->toDateString())
                  ->where(function($sq) use ($startDate) {
                      $sq->whereNull('end_date')
                         ->orWhere('end_date', '>=', $startDate->toDateString());
                  })
                  ->with('user');
            }])
            ->where('is_active', true);

        // Filter by kecamatan
        if ($kecamatanId) {
            $query->whereHas('area', function($q) use ($kecamatanId) {
                $q->where('parent_id', $kecamatanId);
            });
        }

        // Filter by specific kelurahan
        if ($areaId) {
            $query->where('area_id', $areaId);
        }

        $schedules = $query->get();

        // Day mapping for Carbon
        $dayMapping = [
            'Monday' => Carbon::MONDAY,
            'Tuesday' => Carbon::TUESDAY,
            'Wednesday' => Carbon::WEDNESDAY,
            'Thursday' => Carbon::THURSDAY,
            'Friday' => Carbon::FRIDAY,
            'Saturday' => Carbon::SATURDAY,
            'Sunday' => Carbon::SUNDAY,
        ];

        $dayLabels = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        // Color palette for different schedules
        $colors = [
            '#3B82F6', // blue
            '#10B981', // green
            '#F59E0B', // yellow
            '#EF4444', // red
            '#8B5CF6', // purple
            '#EC4899', // pink
            '#06B6D4', // cyan
            '#F97316', // orange
        ];

        $events = [];
        $scheduleColorMap = [];
        $colorIndex = 0;

        foreach ($schedules as $schedule) {
            // Assign color to schedule
            if (!isset($scheduleColorMap[$schedule->id])) {
                $scheduleColorMap[$schedule->id] = $colors[$colorIndex % count($colors)];
                $colorIndex++;
            }
            $color = $scheduleColorMap[$schedule->id];

            // Find the Carbon day constant
            $dayOfWeek = $dayMapping[$schedule->day_of_week] ?? null;
            if ($dayOfWeek === null) continue;

            // Generate events for each occurrence within the date range
            $currentDate = $startDate->copy();
            
            // Move to first occurrence of this day of week
            while ($currentDate->dayOfWeek !== $dayOfWeek && $currentDate->lte($endDate)) {
                $currentDate->addDay();
            }

            while ($currentDate->lte($endDate)) {
                $pickupTime = Carbon::parse($schedule->pickup_time)->format('H:i');
                $eventStart = $currentDate->copy()->setTimeFromTimeString($pickupTime);
                $eventEnd = $eventStart->copy()->addHour();

                // Get active collectors for this date
                $activeCollectors = $schedule->collectorAssignments->filter(function($assignment) use ($currentDate) {
                    $assignmentStart = Carbon::parse($assignment->start_date);
                    $assignmentEnd = $assignment->end_date ? Carbon::parse($assignment->end_date) : null;
                    
                    return $currentDate->gte($assignmentStart) && 
                           ($assignmentEnd === null || $currentDate->lte($assignmentEnd));
                });

                $collectorNames = $activeCollectors->map(fn($a) => $a->user->name ?? '-')->join(', ');
                $hasCollector = $activeCollectors->isNotEmpty();

                $areaName = $schedule->area->name ?? '-';
                $kecamatanName = $schedule->area->parent->name ?? '-';

                $events[] = [
                    'id' => $schedule->id . '_' . $currentDate->format('Y-m-d'),
                    'title' => $areaName,
                    'start' => $eventStart->toIso8601String(),
                    'end' => $eventEnd->toIso8601String(),
                    'backgroundColor' => $hasCollector ? $color : '#9CA3AF',
                    'borderColor' => $hasCollector ? $color : '#6B7280',
                    'textColor' => '#FFFFFF',
                    'extendedProps' => [
                        'schedule_id' => $schedule->id,
                        'area_name' => $areaName,
                        'kecamatan_name' => $kecamatanName,
                        'day_label' => $dayLabels[$schedule->day_of_week] ?? $schedule->day_of_week,
                        'pickup_time' => $pickupTime,
                        'collectors' => $collectorNames ?: 'Belum ada collector',
                        'has_collector' => $hasCollector,
                        'collector_count' => $activeCollectors->count(),
                    ],
                ];

                // Move to next week
                $currentDate->addWeek();
            }
        }

        return response()->json($events);
    }
}
