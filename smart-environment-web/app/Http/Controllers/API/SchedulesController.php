<?php

namespace App\Http\Controllers\API;

use App\Models\Schedule;
use Illuminate\Http\Request;

class SchedulesController extends BaseResourceController
{
    protected string $modelClass = Schedule::class;
    protected array $rulesStore = [
        'area_id' => 'required|exists:areas,id',
        'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        'pickup_time' => 'required|date_format:H:i:s',
    ];
    protected array $rulesUpdate = [
        'area_id' => 'sometimes|exists:areas,id',
        'day_of_week' => 'sometimes|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        'pickup_time' => 'sometimes|date_format:H:i:s',
    ];
    protected array $filterable = ['day_of_week'];
    protected array $sortable = ['id','day_of_week','pickup_time'];
    protected ?string $permissionPrefix = 'schedule';

    public function index(Request $request)
    {
        $query = Schedule::query()->with('area');

        $filters = (array) $request->query('filter', []);
        if (!empty($filters['area_id'])) {
            $query->where('area_id', $filters['area_id']);
        }
        if (!empty($filters['area_name'])) {
            $query->whereHas('area', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['area_name'] . '%');
            });
        }

        $this->applyApiQuery($query, $request, $this->filterable, $this->sortable);

        return $this->okPaginated(
            $query->paginate((int) $request->query('page.size', 10))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rulesStore);
        $schedule = Schedule::create($data)->load('area');

        return $this->ok($schedule);
    }

    public function show(string $id)
    {
        $schedule = Schedule::with('area')->findOrFail($id);

        return $this->ok($schedule);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate($this->rulesUpdate);
        $schedule = Schedule::findOrFail($id);
        $schedule->fill($data);
        $schedule->save();

        return $this->ok($schedule->load('area'));
    }

    public function destroy(string $id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return $this->ok(['deleted' => true]);
    }
}
