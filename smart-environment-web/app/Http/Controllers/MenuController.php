<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read_menu')->only('index', 'show');
        $this->middleware('permission:create_menu')->only('create', 'store');
        $this->middleware('permission:update_menu')->only('edit', 'update');
        $this->middleware('permission:delete_menu')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menus = Menu::all();
        return view('admin.menus.index', compact('menus'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $menus = Menu::all();
        return view('admin.menus.create', compact('menus'));
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
            'nama_menu' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Menu creation failed. Please check your data.');
        }

        try {
            Menu::create([
                'nama_menu' => $request->nama_menu,
                'url' => $request->url,
                'parent_id' => $request->parent_id,
                'icon' => $request->icon,
                'urutan' => 1,
            ]);

            return redirect()->route('manage-menu.index')->with('success', 'Menu created successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-menu.index')->with('error', 'Server error occurred.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function show(Menu $menu)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $menus = Menu::all();
        $menu = Menu::findOrFail($id);
        return view('admin.menus.edit', compact('menu', 'menus'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_menu' => 'required|string',
        ]);

        try {
            $menu = Menu::findOrFail($id);
            $menu->update([
                'nama_menu' => $request->nama_menu,
                'url' => $request->url,
                'icon' => $request->icon,
                'parent_id' => $request->parent_id,
            ]);

            return redirect()->route('manage-menu.index')->with('success', 'Menu updated successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-menu.index')->with('error', 'Server error occurred.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            Menu::findOrFail($id)->delete();
            return redirect()->route('manage-menu.index')->with('success', 'Menu deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('manage-menu.index')->with('error', 'Failed to delete menu.');
        }
    }
}
