<?php

namespace App\Http\Controllers\API;

use App\Models\CollectorAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CollectorAssignmentsController extends BaseResourceController
{
    protected string $modelClass = CollectorAssignment::class;
    protected array $rulesStore = [
        'user_id' => 'required|exists:users,id',
        'schedule_id' => 'required|exists:schedules,id',
        'rw_id' => 'nullable|exists:rws,id',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
    ];
    protected array $rulesUpdate = [
        'user_id' => 'sometimes|exists:users,id',
        'schedule_id' => 'sometimes|exists:schedules,id',
        'rw_id' => 'nullable|exists:rws,id',
        'start_date' => 'sometimes|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
    ];
    protected array $filterable = [];
    protected array $sortable = ['id','start_date'];
    protected ?string $permissionPrefix = 'collector_assignment';

    public function store(Request $request)
    {
        $data = $request->validate($this->rulesStore);

        // Check for conflicts with existing assignments
        $conflict = CollectorAssignment::checkConflict(
            $data['user_id'],
            $data['schedule_id'],
            $data['start_date'],
            $data['end_date'] ?? null
        );

        if ($conflict) {
            $errorMessage = $conflict['is_same_area']
                ? sprintf(
                    'Collector %s sudah ditugaskan ke area "%s" pada hari %s (periode %s - %s). Tidak boleh ada penugasan duplikat.',
                    $conflict['collector_name'],
                    $conflict['existing_area'],
                    $this->translateDay($conflict['day']),
                    $conflict['existing_start'],
                    $conflict['existing_end']
                )
                : sprintf(
                    'Collector %s sudah ditugaskan ke area "%s" pada hari %s (periode %s - %s). Satu collector hanya bisa ditugaskan ke satu area per hari.',
                    $conflict['collector_name'],
                    $conflict['existing_area'],
                    $this->translateDay($conflict['day']),
                    $conflict['existing_start'],
                    $conflict['existing_end']
                );
            
            throw ValidationException::withMessages(['schedule_id' => $errorMessage]);
        }

        $item = CollectorAssignment::create($data);
        return $this->ok($item);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate($this->rulesUpdate);
        
        $item = CollectorAssignment::findOrFail($id);

        // Check for conflicts with existing assignments (excluding current assignment)
        $conflict = CollectorAssignment::checkConflict(
            $data['user_id'] ?? $item->user_id,
            $data['schedule_id'] ?? $item->schedule_id,
            $data['start_date'] ?? $item->start_date,
            $data['end_date'] ?? $item->end_date,
            $item->id // Exclude current assignment from check
        );

        if ($conflict) {
            $errorMessage = $conflict['is_same_area']
                ? sprintf(
                    'Collector %s sudah ditugaskan ke area "%s" pada hari %s (periode %s - %s). Tidak boleh ada penugasan duplikat.',
                    $conflict['collector_name'],
                    $conflict['existing_area'],
                    $this->translateDay($conflict['day']),
                    $conflict['existing_start'],
                    $conflict['existing_end']
                )
                : sprintf(
                    'Collector %s sudah ditugaskan ke area "%s" pada hari %s (periode %s - %s). Satu collector hanya bisa ditugaskan ke satu area per hari.',
                    $conflict['collector_name'],
                    $conflict['existing_area'],
                    $this->translateDay($conflict['day']),
                    $conflict['existing_start'],
                    $conflict['existing_end']
                );
            
            throw ValidationException::withMessages(['schedule_id' => $errorMessage]);
        }

        $item->fill($data);
        $item->save();
        return $this->ok($item);
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
}
