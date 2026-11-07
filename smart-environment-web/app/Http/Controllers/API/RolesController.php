<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Models\Role;
use App\Support\ApiResponse;
use App\Support\ApiQuery;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    use ApiResponse, ApiQuery;

    public function __construct()
    {
        $this->middleware('permission:read_role')->only(['index','show']);
        $this->middleware('permission:create_role')->only(['store']);
        $this->middleware('permission:update_role')->only(['update']);
        $this->middleware('permission:delete_role')->only(['destroy']);
    }

    public function index(Request $request)
    {
    $q = Role::query();
    $this->applyApiQuery($q, $request, ['name'], ['name','id']);
    return $this->okPaginated($q->paginate((int) $request->query('page.size', 10)));
    }

    public function store(RoleStoreRequest $request)
    {
        $data = $request->validated();
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'api']);
        if (!empty($data['permissions'])) $role->syncPermissions($data['permissions']);
        return $this->ok($role);
    }

    public function show(string $id)
    {
        return $this->ok(Role::findOrFail($id));
    }

    public function update(RoleUpdateRequest $request, string $id)
    {
        $data = $request->validated();
        $role = Role::findOrFail($id);
        if (isset($data['name'])) $role->name = $data['name'];
        $role->save();
        if (array_key_exists('permissions', $data)) {
            $role->syncPermissions($data['permissions'] ?? []);
        }
        return $this->ok($role);
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        return $this->ok(['deleted' => true]);
    }
}
