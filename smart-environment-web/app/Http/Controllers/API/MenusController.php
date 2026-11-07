<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuStoreRequest;
use App\Http\Requests\MenuUpdateRequest;
use App\Models\Menu;
use App\Support\ApiResponse;
use App\Support\ApiQuery;
use Illuminate\Http\Request;

class MenusController extends Controller
{
    use ApiResponse, ApiQuery;

    public function __construct()
    {
        $this->middleware('permission:read_menu')->only(['index','show']);
        $this->middleware('permission:create_menu')->only(['store']);
        $this->middleware('permission:update_menu')->only(['update']);
        $this->middleware('permission:delete_menu')->only(['destroy']);
    }

    public function index(Request $request)
    {
    $q = Menu::query();
    $this->applyApiQuery($q, $request, ['nama_menu','url','id_html','tipe_menu'], ['nama_menu','id']);
    return $this->okPaginated($q->paginate((int) $request->query('page.size', 10)));
    }

    public function store(MenuStoreRequest $request)
    {
        $menu = Menu::create($request->validated());
        return $this->ok($menu);
    }

    public function show(string $id)
    {
        return $this->ok(Menu::findOrFail($id));
    }

    public function update(MenuUpdateRequest $request, string $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->fill($request->validated());
        $menu->save();
        return $this->ok($menu);
    }

    public function destroy(string $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();
        return $this->ok(['deleted' => true]);
    }
}
