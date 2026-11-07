<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read_role')->only('index', 'show');
        $this->middleware('permission:create_role')->only('create', 'store');
        $this->middleware('permission:update_role')->only('edit', 'update');
        $this->middleware('permission:delete_role')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $menus = Menu::where('parent_id', 0)->orWhereNull('parent_id')->get();
        return view('admin.roles.create', compact('menus'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'menu_id' => 'required|array',
        ]);

        try {
            $role = Role::create(['name' => strtolower($request->name)]);

            if ($request->has('menu_id')) {
                foreach ($request->menu_id as $value) {
                    DB::table('role_has_menus')->insert([
                        'menu_id' => $value,
                        'role_id' => $role->id,
                    ]);
                }
            }

            if ($request->has('permission_id')) {
                $role->syncPermissions($request->permission_id);
            }

            return redirect()->route('manage-role.index')->with('success', 'Role created successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-role.index')->with('error', 'Server error occurred.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $menus = Menu::where('parent_id', 0)->orWhereNull('parent_id')->get();
        $role = Role::findOrFail($id);
        $getmenus = DB::table('role_has_menus')->where('role_id', $id)->pluck('menu_id')->toArray();

        $permissions = DB::table('permissions')
            ->join('role_has_permissions as a', 'a.permission_id', 'permissions.id')
            ->where('a.role_id', $role->id)
            ->get();

        return view('admin.roles.edit', compact('role', 'menus', 'getmenus', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'menu_id' => 'required|array',
        ]);

        try {
            DB::table('role_has_menus')->where('role_id', $id)->delete();
            $role = Role::findOrFail($id);

            if ($request->has('menu_id')) {
                foreach ($request->menu_id as $value) {
                    DB::table('role_has_menus')->insert([
                        'menu_id' => $value,
                        'role_id' => $role->id,
                    ]);
                }
            }

            $role->update(['name' => $request->name]);

            if ($request->has('permission_id')) {
                $role->syncPermissions($request->permission_id);
            } else {
                $role->syncPermissions([]);
            }

            return redirect()->route('manage-role.index')->with('success', 'Role updated successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-role.index')->with('error', 'Server error occurred.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::table('role_has_menus')->where('role_id', $id)->delete();
            Role::findOrFail($id)->delete();
            return redirect()->route('manage-role.index')->with('success', 'Role deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-role.index')->with('error', 'Failed to delete role.');
        }
    }
}
