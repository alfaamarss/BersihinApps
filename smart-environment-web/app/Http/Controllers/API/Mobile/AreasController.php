<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AreasController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Area::query()->with('parent');

        if ($request->filled('level')) {
            $query->where('level', $request->query('level'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->query('parent_id'));
        }

        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $limit = min($request->integer('limit', 20), 100);

        $areas = $query->orderBy('name')->paginate($limit);

        $items = $areas->getCollection()->map(function (Area $area) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'level' => $area->level,
                'parent' => $area->parent ? [
                    'id' => $area->parent->id,
                    'name' => $area->parent->name,
                    'level' => $area->parent->level,
                ] : null,
            ];
        });

        return $this->ok(
            ['items' => $items],
            [
                'current_page' => $areas->currentPage(),
                'per_page' => $areas->perPage(),
                'total' => $areas->total(),
                'last_page' => $areas->lastPage(),
            ]
        );
    }

    /**
     * Get RWs by kelurahan
     * 
     * GET /api/v1/mobile/areas/{area_id}/rws
     * 
     * Returns list of RWs for a specific kelurahan
     * Used by mobile app for RW selection or filtering
     */
    public function rws(Request $request, int $areaId)
    {
        // Validate area exists and is kelurahan
        $area = Area::where('id', $areaId)
            ->where('level', 'Kelurahan')
            ->firstOrFail();

        $query = \App\Models\Rw::where('kelurahan_id', $areaId);

        // Search by name
        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $rws = $query->orderBy('name')->get()->map(function ($rw) {
            return [
                'id' => $rw->id,
                'name' => $rw->name,
                'kelurahan_id' => $rw->kelurahan_id,
            ];
        });

        return $this->ok([
            'kelurahan' => [
                'id' => $area->id,
                'name' => $area->name,
            ],
            'items' => $rws,
        ]);
    }
}
