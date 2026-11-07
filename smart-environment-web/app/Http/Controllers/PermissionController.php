<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission as ModelsPermission;

class PermissionController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read_permission')->only('index', 'show');
        $this->middleware('permission:create_permission')->only('create', 'store');
        $this->middleware('permission:update_permission')->only('edit', 'update');
        $this->middleware('permission:delete_permission')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'permission' => 'required|string',
            'menu_id' => 'required|exists:menus,id',
        ]);

        try {
            ModelsPermission::create([
                'name' => $request->permission,
                'menu_id' => $request->menu_id,
            ]);

            return redirect()->route('manage-menu.index')->with('success', 'Permission created successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-menu.index')->with('error', 'Server error occurred.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            ModelsPermission::findOrFail($id)->delete();
            return redirect()->route('manage-menu.index')->with('success', 'Permission deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-menu.index')->with('error', 'Failed to delete permission.');
        }
    }
}
