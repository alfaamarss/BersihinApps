<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceAccount;
use App\Models\User;
use App\Models\Area;
use App\Models\Rw;
use App\Notifications\User\ServiceAccountDeactivatedByAdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read_service_account')->only('index', 'show');
        $this->middleware('permission:create_service_account')->only('create', 'store');
        $this->middleware('permission:update_service_account')->only('edit', 'update');
        $this->middleware('permission:delete_service_account')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = ServiceAccount::query()
            ->with(['user', 'area', 'rw']);

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

        // Kecamatan filter (via area's parent)
        if ($request->filled('kecamatan_id')) {
            $kecamatanId = $request->input('kecamatan_id');
            $query->whereHas('area', function($q) use ($kecamatanId) {
                $q->where('parent_id', $kecamatanId);
            });
        }

        // Kelurahan filter (area_id)
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        // RW filter
        if ($request->filled('rw_id')) {
            $query->where('rw_id', $request->input('rw_id'));
        }

        $serviceAccounts = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get kecamatans for filter
        $kecamatans = Area::where('level', 'Kecamatan')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.service_accounts.index', compact('serviceAccounts', 'kecamatans'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        
        // Get kecamatans (parent areas)
        $kecamatans = Area::where('level', 'Kecamatan')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.service_accounts.create', compact('users', 'kecamatans'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'area_id' => 'required|exists:areas,id',
            'rw' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'note' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();

        // Jika ada input RW, cari atau buat RW baru
        if ($request->filled('rw')) {
            $rwName = $request->input('rw');
            $areaId = $request->input('area_id');

            // Cari atau buat RW
            $rw = Rw::firstOrCreate(
                [
                    'kelurahan_id' => $areaId,
                    'name' => $rwName,
                ],
                [
                    'kelurahan_id' => $areaId,
                    'name' => $rwName,
                ]
            );

            // Ganti 'rw' dengan 'rw_id'
            $data['rw_id'] = $rw->id;
            unset($data['rw']);
        }

        $serviceAccount = ServiceAccount::create($data);

        // Kirim notifikasi ke semua admin
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewServiceAccountNotification($serviceAccount));
        }

        return redirect()->route('admin.service-accounts.index')
            ->with('success', 'Akun layanan berhasil ditambahkan.');
    }

    public function show($id)
    {
        $serviceAccount = ServiceAccount::with(['user', 'area'])->findOrFail($id);
        return view('admin.service_accounts.show', compact('serviceAccount'));
    }

    public function edit($id)
    {
        $serviceAccount = ServiceAccount::with('area.parent')->findOrFail($id);
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        
        // Get kecamatans (parent areas)
        $kecamatans = Area::where('level', 'Kecamatan')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.service_accounts.edit', compact('serviceAccount', 'users', 'kecamatans'));
    }

    public function update(Request $request, $id)
    {
        $serviceAccount = ServiceAccount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'area_id' => 'required|exists:areas,id',
            'rw' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'note' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();

        // Jika ada input RW, cari atau buat RW baru
        if ($request->filled('rw')) {
            $rwName = $request->input('rw');
            $areaId = $request->input('area_id');

            // Cari atau buat RW
            $rw = Rw::firstOrCreate(
                [
                    'kelurahan_id' => $areaId,
                    'name' => $rwName,
                ],
                [
                    'kelurahan_id' => $areaId,
                    'name' => $rwName,
                ]
            );

            // Ganti 'rw' dengan 'rw_id'
            $data['rw_id'] = $rw->id;
            unset($data['rw']);
        }

        // Cek apakah status berubah menjadi inactive
        $wasActive = $serviceAccount->status === 'active';
        $nowInactive = $data['status'] === 'inactive';

        $serviceAccount->update($data);

        // Kirim notifikasi jika status berubah dari active ke inactive
        if ($wasActive && $nowInactive) {
            // Notifikasi ke admins (existing)
            $admins = User::role('admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\ServiceAccountDeactivatedNotification($serviceAccount));
            }

            // Notifikasi ke user yang memiliki akun layanan
            if ($serviceAccount->user) {
                $reason = $request->input('deactivation_reason'); // Optional reason from admin
                $serviceAccount->user->notify(
                    new ServiceAccountDeactivatedByAdminNotification($serviceAccount, $reason)
                );
            }
        }

        return redirect()->route('admin.service-accounts.index')
            ->with('success', 'Akun layanan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $serviceAccount = ServiceAccount::findOrFail($id);
        $serviceAccount->delete();

        return redirect()->route('admin.service-accounts.index')
            ->with('success', 'Akun layanan berhasil dihapus.');
    }

    /**
     * Get kelurahans by kecamatan for AJAX
     */
    public function getKelurahans(Request $request)
    {
        $kecamatanId = $request->input('kecamatan_id');

        if (!$kecamatanId) {
            return response()->json([]);
        }

        $kelurahans = Area::where('parent_id', $kecamatanId)
            ->where('level', 'Kelurahan')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($kelurahans);
    }
}
