<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\ApiQuery;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    use ApiResponse, ApiQuery;

    public function __construct()
    {
        $this->middleware('permission:read_user')->only(['index','show']);
        $this->middleware('permission:create_user')->only(['store']);
        $this->middleware('permission:update_user')->only(['update']);
        $this->middleware('permission:delete_user')->only(['destroy']);
    }

    public function index(Request $request)
    {
    $q = User::query();
    $this->applyApiQuery($q, $request, ['name','email'], ['name','email','id']);
    return $this->okPaginated($q->paginate((int) $request->query('page.size', 10)));
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->email_verified_at = now();
        $user->save();
        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }
        return $this->ok($user);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return $this->ok($user);
    }

    public function update(UserUpdateRequest $request, string $id)
    {
        $data = $request->validated();
        $user = User::findOrFail($id);
        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (!empty($data['password'])) $user->password = bcrypt($data['password']);
        $user->save();
        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles'] ?? []);
        }
        return $this->ok($user);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return $this->ok(['deleted' => true]);
    }
}
