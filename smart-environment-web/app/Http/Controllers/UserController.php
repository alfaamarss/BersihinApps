<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read_user')->only('index', 'show');
        $this->middleware('permission:create_user')->only('create', 'store');
        $this->middleware('permission:update_user')->only('edit', 'update');
        $this->middleware('permission:delete_user')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = User::withTrashed(); // Include soft deleted users

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        // Filter by email verification status
        if ($request->filled('status')) {
            if ($request->input('status') === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->input('status') === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        // Filter by deleted status
        if ($request->filled('deleted')) {
            if ($request->input('deleted') === 'only') {
                $query->onlyTrashed();
            } elseif ($request->input('deleted') === 'active') {
                $query->withoutTrashed();
            }
            // 'with' is default (withTrashed already applied)
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::all()->map(function($role) {
            return [
                'value' => $role->name,
                'label' => $role->name . ' (' . $role->guard_name . ')'
            ];
        });
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email:rfc|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'nullable',
            'verified' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'User creation failed. Please check your data.');
        }

        try {
            $data = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => !empty($request->verified) ? now() : null
            ]);

            if (!empty($request->role)) {
                $data->assignRole($request->role);
            }

            return redirect()->route('manage-user.index')->with('success', 'User created successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-user.index')->with('error', 'Server error occurred.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $roles = Role::all()->map(function($role) {
            return [
                'value' => $role->name,
                'label' => $role->name . ' (' . $role->guard_name . ')'
            ];
        });
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email:rfc',
            'password' => 'nullable|string|min:8',
            'role' => 'nullable',
            'verified' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'User update failed. Please check your data.');
        }

        try {
            $user = User::findOrFail($id);

            $update_data = [
                'name' => $request->name,
                'email' => $request->email,
                'email_verified_at' => !empty($request->verified) ? now() : null
            ];

            if (!empty($request->password)) {
                $update_data['password'] = Hash::make($request->password);
            }

            $user->update($update_data);

            if (!empty($request->role)) {
                $user->syncRoles($request->role);
            } else {
                $user->syncRoles([]);
            }

            return redirect()->route('manage-user.index')->with('success', 'User updated successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-user.index')->with('error', 'Server error occurred.');
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete(); // Soft delete
            return redirect()->route('manage-user.index')->with('success', 'User deactivated successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-user.index')->with('error', 'Failed to deactivate user.');
        }
    }

    /**
     * Restore a soft deleted user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();
            return redirect()->route('manage-user.index')->with('success', 'User restored successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-user.index')->with('error', 'Failed to restore user.');
        }
    }
}
