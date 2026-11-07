<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Rw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RwController extends Controller
{
    /**
     * Display a listing of RWs
     */
    public function index(Request $request)
    {
        $query = Rw::with(['kelurahan.parent'])
            ->withCount(['serviceAccounts', 'collectorAssignments']);

        // Filter by kecamatan
        if ($kecamatanId = $request->query('kecamatan_id')) {
            $query->whereHas('kelurahan', function ($q) use ($kecamatanId) {
                $q->where('parent_id', $kecamatanId);
            });
        }

        // Filter by kelurahan
        if ($kelurahanId = $request->query('kelurahan_id')) {
            $query->where('kelurahan_id', $kelurahanId);
        }

        // Search by name
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $rws = $query->orderBy('kelurahan_id')->orderBy('name')->paginate(20);

        // Get kecamatans for filter
        $kecamatans = Area::where('level', 'Kecamatan')->orderBy('name')->get();

        // Get kelurahans for filter (based on selected kecamatan or all)
        $kelurahans = Area::where('level', 'Kelurahan')
            ->when($kecamatanId, fn($q) => $q->where('parent_id', $kecamatanId))
            ->orderBy('name')
            ->get();

        return view('admin.rws.index', compact('rws', 'kecamatans', 'kelurahans'));
    }

    /**
     * Show the form for creating a new RW
     */
    public function create()
    {
        $kecamatans = Area::where('level', 'Kecamatan')->orderBy('name')->get();
        
        return view('admin.rws.create', compact('kecamatans'));
    }

    /**
     * Store a newly created RW
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelurahan_id' => ['required', 'exists:areas,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^RW\s+\d{1,3}$/i',
                Rule::unique('rws')->where(function ($query) use ($request) {
                    return $query->where('kelurahan_id', $request->kelurahan_id);
                }),
            ],
        ], [
            'name.regex' => 'Format RW harus: "RW XX" (contoh: RW 01, RW 15)',
            'name.unique' => 'RW ini sudah ada di kelurahan yang dipilih.',
        ]);

        // Normalize RW name
        $rwName = strtoupper(trim($validated['name']));
        if (preg_match('/^RW\s+(\d+)$/i', $rwName, $matches)) {
            $rwNumber = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $rwName = "RW {$rwNumber}";
        }
        $validated['name'] = $rwName;

        $rw = Rw::create($validated);

        // Kirim notifikasi ke semua admin
        $admins = \App\Models\User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewRwNotification($rw));
        }

        return redirect()
            ->route('admin.rws.index')
            ->with('success', 'RW berhasil ditambahkan.');
    }

    /**
     * Show the form for editing RW
     */
    public function edit(Rw $rw)
    {
        $rw->load('kelurahan.parent');
        $kecamatans = Area::where('level', 'Kecamatan')->orderBy('name')->get();
        
        return view('admin.rws.edit', compact('rw', 'kecamatans'));
    }

    /**
     * Update the specified RW
     */
    public function update(Request $request, Rw $rw)
    {
        $validated = $request->validate([
            'kelurahan_id' => ['required', 'exists:areas,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^RW\s+\d{1,3}$/i',
                Rule::unique('rws')->where(function ($query) use ($request) {
                    return $query->where('kelurahan_id', $request->kelurahan_id);
                })->ignore($rw->id),
            ],
        ], [
            'name.regex' => 'Format RW harus: "RW XX" (contoh: RW 01, RW 15)',
            'name.unique' => 'RW ini sudah ada di kelurahan yang dipilih.',
        ]);

        // Normalize RW name
        $rwName = strtoupper(trim($validated['name']));
        if (preg_match('/^RW\s+(\d+)$/i', $rwName, $matches)) {
            $rwNumber = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $rwName = "RW {$rwNumber}";
        }
        $validated['name'] = $rwName;

        $rw->update($validated);

        return redirect()
            ->route('admin.rws.index')
            ->with('success', 'RW berhasil diperbarui.');
    }

    /**
     * Remove the specified RW
     */
    public function destroy(Rw $rw)
    {
        // Check if RW is being used
        $serviceAccountsCount = $rw->serviceAccounts()->count();
        $assignmentsCount = $rw->collectorAssignments()->count();

        if ($serviceAccountsCount > 0 || $assignmentsCount > 0) {
            return redirect()
                ->route('admin.rws.index')
                ->with('error', "RW tidak dapat dihapus karena masih digunakan oleh {$serviceAccountsCount} service account dan {$assignmentsCount} penugasan.");
        }

        $rw->delete();

        return redirect()
            ->route('admin.rws.index')
            ->with('success', 'RW berhasil dihapus.');
    }

    /**
     * Get RWs by kelurahan (AJAX)
     */
    public function getByKelurahan(Request $request)
    {
        $kelurahanId = $request->query('kelurahan_id');
        
        if (!$kelurahanId) {
            return response()->json([]);
        }

        $rws = Rw::where('kelurahan_id', $kelurahanId)
            ->orderBy('name')
            ->get(['id', 'name', 'kelurahan_id']);

        return response()->json($rws);
    }
}
