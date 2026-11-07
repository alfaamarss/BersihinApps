<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class AreaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read_area')->only('index', 'show');
        $this->middleware('permission:create_area')->only('create', 'store');
        $this->middleware('permission:update_area')->only('edit', 'update');
        $this->middleware('permission:delete_area')->only('destroy');
    }

    public function index()
    {
        $areas = Area::query()
            ->with('parent') // Load parent relationship for hierarchical display
            ->orderBy('level') // Kecamatan first, then Kelurahan
            ->orderBy('name') // Then by name alphabetically
            ->paginate(20);
        return view('admin.areas.index', compact('areas'));
    }

    public function create()
    {
        return view('admin.areas.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'province_id' => 'required',
            'regency_id' => 'required',
            'district_id' => 'required',
            'village_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $apiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';
        $districtId = $request->input('district_id');
        $villageId = $request->input('village_id');

        try {
            $resDistrict = Http::get($apiBase . '/district/' . $districtId . '.json');
            $resVillage = Http::get($apiBase . '/village/' . $villageId . '.json');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['api' => 'Gagal menghubungi API wilayah.'])->withInput();
        }

        if (!$resDistrict->ok() || !$resVillage->ok()) {
            return redirect()->back()->withErrors(['api' => 'Data wilayah tidak valid.'])->withInput();
        }

        $district = $resDistrict->json();
        $village = $resVillage->json();

        // Create/find Kecamatan
        $kecamatan = Area::firstOrCreate([
            'name' => $district['name'],
            'level' => 'Kecamatan',
            'parent_id' => null,
        ]);

        // Create/find Kelurahan with parent Kecamatan
        Area::firstOrCreate(
            ['name' => $village['name'], 'level' => 'Kelurahan', 'parent_id' => $kecamatan->id]
        );

        return redirect()->route('admin.areas.index')->with('success','Area kecamatan & kelurahan berhasil dibuat.');
    }

    public function edit($id)
    {
        $area = Area::findOrFail($id);
        $parents = Area::where('id','!=',$id)->orderBy('name')->get(['id','name']);
        $levels = ['Kecamatan','Kelurahan'];
        return view('admin.areas.edit', compact('area','parents','levels'));
    }

    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'level' => 'required|in:Kecamatan,Kelurahan',
            'parent_id' => 'nullable|exists:areas,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        if (isset($data['parent_id']) && $data['parent_id'] == $area->id) {
            unset($data['parent_id']);
        }
        $area->update($data);
        return redirect()->route('admin.areas.index')->with('success','Area updated successfully.');
    }

    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();
        return redirect()->route('admin.areas.index')->with('success','Area deleted successfully.');
    }
}
