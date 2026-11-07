<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Support\ApiQuery;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class BaseResourceController extends Controller
{
    use ApiResponse, ApiQuery;

    protected string $modelClass;
    protected array $rulesStore = [];
    protected array $rulesUpdate = [];
    protected array $filterable = [];
    protected array $sortable = ['id'];
    protected ?string $permissionPrefix = null; // e.g. 'area' -> read_area, create_area, etc

    public function __construct()
    {
        if ($this->permissionPrefix) {
            $this->middleware('permission:read_' . $this->permissionPrefix)->only(['index','show']);
            $this->middleware('permission:create_' . $this->permissionPrefix)->only(['store']);
            $this->middleware('permission:update_' . $this->permissionPrefix)->only(['update']);
            $this->middleware('permission:delete_' . $this->permissionPrefix)->only(['destroy']);
        }
    }

    public function index(Request $request)
    {
        /** @var Model $model */
        $model = new $this->modelClass();
        $q = $model->newQuery();
        $this->applyApiQuery($q, $request, $this->filterable, $this->sortable);
        return $this->okPaginated($q->paginate((int) $request->query('page.size', 10)));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rulesStore);
        /** @var Model $model */
        $model = new $this->modelClass();
        $item = $model->create($data);
        return $this->ok($item);
    }

    public function show(string $id)
    {
        /** @var Model $model */
        $model = new $this->modelClass();
        $item = $model->newQuery()->findOrFail($id);
        return $this->ok($item);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate($this->rulesUpdate);
        /** @var Model $model */
        $model = new $this->modelClass();
        $item = $model->newQuery()->findOrFail($id);
        $item->fill($data);
        $item->save();
        return $this->ok($item);
    }

    public function destroy(string $id)
    {
        /** @var Model $model */
        $model = new $this->modelClass();
        $item = $model->newQuery()->findOrFail($id);
        $item->delete();
        return $this->ok(['deleted' => true]);
    }
}
