<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\GarbageDump;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GarbageDumpController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read_garbage_dump')->only('index', 'show');
        $this->middleware('permission:create_garbage_dump')->only('create', 'store');
        $this->middleware('permission:update_garbage_dump')->only('edit', 'update', 'toggleStatus');
        $this->middleware('permission:delete_garbage_dump')->only('destroy');
    }

    /**
     * Display a listing of TPS
     */
    public function index(Request $request)
    {
        $query = GarbageDump::with(['kecamatan', 'kelurahan', 'rw'])
            ->withCount('deposits');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Kecamatan filter
        if ($request->filled('kecamatan_id')) {
            $query->where('kecamatan_id', $request->input('kecamatan_id'));
        }

        // Kelurahan filter
        if ($request->filled('kelurahan_id')) {
            $query->where('kelurahan_id', $request->input('kelurahan_id'));
        }

        $garbageDumps = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get kecamatans for filter
        $kecamatans = Area::where('level', 'Kecamatan')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.garbage_dumps.index', compact('garbageDumps', 'kecamatans'));
    }

    /**
     * Show the form for creating a new TPS
     */
    public function create()
    {
        $kecamatans = Area::where('level', 'Kecamatan')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get collectors (users with collector role) with their current TPS assignment
        $collectors = User::role('collector')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function($collector) {
                $assignment = DB::table('garbage_dump_collector')
                    ->where('collector_id', $collector->id)
                    ->join('garbage_dumps', 'garbage_dumps.id', '=', 'garbage_dump_collector.garbage_dump_id')
                    ->select('garbage_dumps.name')
                    ->first();
                $collector->assigned_tps = $assignment ? $assignment->name : null;
                return $collector;
            });

        return view('admin.garbage_dumps.create', compact('kecamatans', 'collectors'));
    }

    /**
     * Store a newly created TPS
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'kecamatan_id' => 'required|exists:areas,id',
            'kelurahan_id' => 'nullable|exists:areas,id',
            'rw' => 'nullable|string|max:50',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity_volume' => 'nullable|numeric|min:0',
            'capacity_weight' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'collector_ids' => 'nullable|array',
            'collector_ids.*' => 'exists:users,id',
        ], [
            'name.required' => 'Nama TPS wajib diisi.',
            'kecamatan_id.required' => 'Kecamatan wajib dipilih.',
            'address.required' => 'Alamat lengkap wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();

        // Handle RW
        if ($request->filled('rw') && $request->filled('kelurahan_id')) {
            $rwName = $request->input('rw');
            $kelurahanId = $request->input('kelurahan_id');

            $rw = Rw::firstOrCreate(
                [
                    'kelurahan_id' => $kelurahanId,
                    'name' => $rwName,
                ],
                [
                    'kelurahan_id' => $kelurahanId,
                    'name' => $rwName,
                ]
            );

            $data['rw_id'] = $rw->id;
        }
        
        if (isset($data['rw'])) {
            unset($data['rw']);
        }

        // Remove collector_ids from data as it's handled separately
        $collectorIds = $data['collector_ids'] ?? [];
        unset($data['collector_ids']);

        // Check if any collector is already assigned to another TPS
        if (!empty($collectorIds)) {
            $assignedCollectors = DB::table('garbage_dump_collector')
                ->whereIn('collector_id', $collectorIds)
                ->join('users', 'users.id', '=', 'garbage_dump_collector.collector_id')
                ->join('garbage_dumps', 'garbage_dumps.id', '=', 'garbage_dump_collector.garbage_dump_id')
                ->select('users.name as collector_name', 'garbage_dumps.name as tps_name')
                ->get();

            if ($assignedCollectors->isNotEmpty()) {
                $errorMessages = $assignedCollectors->map(function($item) {
                    return "Collector {$item->collector_name} sudah di-assign ke TPS {$item->tps_name}";
                })->implode(', ');

                return redirect()->back()
                    ->withErrors(['collector_ids' => $errorMessages])
                    ->withInput();
            }
        }

        $garbageDump = GarbageDump::create($data);

        // Sync collectors
        if (!empty($collectorIds)) {
            $garbageDump->collectors()->sync($collectorIds);
        }

        return redirect()->route('admin.garbage-dumps.index')
            ->with('success', 'TPS berhasil ditambahkan.');
    }

    /**
     * Display the specified TPS
     */
    public function show($id)
    {
        $garbageDump = GarbageDump::with(['kecamatan', 'kelurahan', 'rw', 'deposits.collector', 'collectors'])
            ->findOrFail($id);

        // Get recent deposits
        $recentDeposits = $garbageDump->deposits()
            ->with('collector')
            ->orderBy('deposited_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.garbage_dumps.show', compact('garbageDump', 'recentDeposits'));
    }

    /**
     * Show the form for editing the specified TPS
     */
    public function edit($id)
    {
        $garbageDump = GarbageDump::with(['kecamatan', 'kelurahan', 'rw', 'collectors'])->findOrFail($id);
        
        $kecamatans = Area::where('level', 'Kecamatan')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get kelurahans for selected kecamatan
        $kelurahans = [];
        if ($garbageDump->kecamatan_id) {
            $kelurahans = Area::where('level', 'Kelurahan')
                ->where('parent_id', $garbageDump->kecamatan_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // Get collectors (users with collector role) with their current TPS assignment
        $collectors = User::role('collector')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function($collector) use ($id) {
                $assignment = DB::table('garbage_dump_collector')
                    ->where('collector_id', $collector->id)
                    ->where('garbage_dump_id', '!=', $id) // Exclude current TPS
                    ->join('garbage_dumps', 'garbage_dumps.id', '=', 'garbage_dump_collector.garbage_dump_id')
                    ->select('garbage_dumps.name')
                    ->first();
                $collector->assigned_tps = $assignment ? $assignment->name : null;
                return $collector;
            });

        return view('admin.garbage_dumps.edit', compact('garbageDump', 'kecamatans', 'kelurahans', 'collectors'));
    }

    /**
     * Update the specified TPS
     */
    public function update(Request $request, $id)
    {
        $garbageDump = GarbageDump::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'kecamatan_id' => 'required|exists:areas,id',
            'kelurahan_id' => 'nullable|exists:areas,id',
            'rw' => 'nullable|string|max:50',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity_volume' => 'nullable|numeric|min:0',
            'capacity_weight' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'collector_ids' => 'nullable|array',
            'collector_ids.*' => 'exists:users,id',
        ], [
            'name.required' => 'Nama TPS wajib diisi.',
            'kecamatan_id.required' => 'Kecamatan wajib dipilih.',
            'address.required' => 'Alamat lengkap wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();

        // Handle RW
        if ($request->filled('rw') && $request->filled('kelurahan_id')) {
            $rwName = $request->input('rw');
            $kelurahanId = $request->input('kelurahan_id');

            $rw = Rw::firstOrCreate(
                [
                    'kelurahan_id' => $kelurahanId,
                    'name' => $rwName,
                ],
                [
                    'kelurahan_id' => $kelurahanId,
                    'name' => $rwName,
                ]
            );

            $data['rw_id'] = $rw->id;
        } else {
            $data['rw_id'] = null;
        }
        unset($data['rw']);

        // Remove collector_ids from data as it's handled separately
        $collectorIds = $data['collector_ids'] ?? [];
        unset($data['collector_ids']);

        // Check if any collector is already assigned to another TPS (excluding current TPS)
        if (!empty($collectorIds)) {
            $assignedCollectors = DB::table('garbage_dump_collector')
                ->whereIn('collector_id', $collectorIds)
                ->where('garbage_dump_id', '!=', $id)
                ->join('users', 'users.id', '=', 'garbage_dump_collector.collector_id')
                ->join('garbage_dumps', 'garbage_dumps.id', '=', 'garbage_dump_collector.garbage_dump_id')
                ->select('users.name as collector_name', 'garbage_dumps.name as tps_name')
                ->get();

            if ($assignedCollectors->isNotEmpty()) {
                $errorMessages = $assignedCollectors->map(function($item) {
                    return "Collector {$item->collector_name} sudah di-assign ke TPS {$item->tps_name}";
                })->implode(', ');

                return redirect()->back()
                    ->withErrors(['collector_ids' => $errorMessages])
                    ->withInput();
            }
        }

        $garbageDump->update($data);

        // Sync collectors
        $garbageDump->collectors()->sync($collectorIds);

        return redirect()->route('admin.garbage-dumps.index')
            ->with('success', 'TPS berhasil diperbarui.');
    }

    /**
     * Remove the specified TPS
     */
    public function destroy($id)
    {
        $garbageDump = GarbageDump::findOrFail($id);
        
        // Check if TPS has deposits
        if ($garbageDump->deposits()->exists()) {
            return redirect()->route('admin.garbage-dumps.index')
                ->with('error', 'TPS tidak dapat dihapus karena memiliki riwayat setor.');
        }

        $garbageDump->delete();

        return redirect()->route('admin.garbage-dumps.index')
            ->with('success', 'TPS berhasil dihapus.');
    }

    /**
     * Toggle TPS status (active/inactive)
     */
    public function toggleStatus($id)
    {
        $garbageDump = GarbageDump::findOrFail($id);
        
        $garbageDump->status = $garbageDump->status === 'active' ? 'inactive' : 'active';
        $garbageDump->save();

        $statusLabel = $garbageDump->status === 'active' ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.garbage-dumps.index')
            ->with('success', "TPS berhasil {$statusLabel}.");
    }

    /**
     * Get kelurahans by kecamatan (AJAX)
     */
    public function getKelurahans(Request $request)
    {
        $kecamatanId = $request->query('kecamatan_id');
        
        $kelurahans = Area::where('level', 'Kelurahan')
            ->where('parent_id', $kecamatanId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($kelurahans);
    }

    /**
     * Get RWs by kelurahan (AJAX)
     */
    public function getRws(Request $request)
    {
        $kelurahanId = $request->query('kelurahan_id');
        
        $rws = Rw::where('kelurahan_id', $kelurahanId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rws);
    }
}
