<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Menu;
use App\Models\GarbageDump;
use App\Models\ServiceAccount;
use App\Models\Payment;
use App\Models\Complaint;
use App\Models\WasteDelivery;
use App\Models\Area;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Basic stats for old dashboard (keeping for backward compatibility)
        $stats = [
            'users' => User::count(),
            'roles' => SpatieRole::count(),
            'menus' => Menu::count(),
            'permissions' => SpatiePermission::count(),
        ];

        // New stats for updated dashboard design
        $dashboardStats = [
            'collectors' => User::role('collector')->count(),
            'serviceAccounts' => ServiceAccount::count(),
            // Count payments with status 'settlement' or 'success' (Midtrans success statuses)
            'paymentsReceived' => Payment::whereIn('status', ['settlement', 'success'])->count(),
            'totalTps' => GarbageDump::count(),
        ];

        // Complaint status distribution for pie chart
        // Note: 'open' = Menunggu, 'in_progress' = Diproses, 'resolved' = Selesai, 'rejected' = Ditolak
        $complaintStats = [
            'pending' => Complaint::where('status', 'open')->count(),
            'in_progress' => Complaint::where('status', 'in_progress')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'rejected' => Complaint::where('status', 'rejected')->count(),
        ];

        // Top 5 areas (Kecamatan) with most waste pickups for current month
        // Service accounts are linked to Kelurahan, which has parent_id pointing to Kecamatan
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $currentMonthName = now()->translatedFormat('F Y');
        
        $topAreas = Area::where('areas.level', 'Kecamatan')
            ->select('areas.id', 'areas.name')
            ->leftJoin('areas as kelurahan', 'kelurahan.parent_id', '=', 'areas.id')
            ->leftJoin('service_accounts', 'service_accounts.area_id', '=', 'kelurahan.id')
            ->leftJoin('waste_deliveries', function($join) use ($currentMonth, $currentYear) {
                $join->on('waste_deliveries.service_account_id', '=', 'service_accounts.id')
                     ->where('waste_deliveries.status', 'collected')
                     ->whereMonth('waste_deliveries.collected_at', $currentMonth)
                     ->whereYear('waste_deliveries.collected_at', $currentYear);
            })
            ->selectRaw('COALESCE(SUM(waste_deliveries.bag_count), 0) as total_bags')
            ->groupBy('areas.id', 'areas.name')
            ->orderByDesc('total_bags')
            ->limit(5)
            ->get();

        // Calculate total for percentage
        $totalBags = $topAreas->sum('total_bags');
        $topAreas = $topAreas->map(function ($area) use ($totalBags) {
            $area->percentage = $totalBags > 0 ? round(($area->total_bags / $totalBags) * 100) : 0;
            return $area;
        });

        $recentUsers = User::with('roles')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Recent service accounts
        $recentServiceAccounts = ServiceAccount::with(['user', 'area'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Recent pickup requests (off-schedule pickups)
        $recentPickupRequests = WasteDelivery::with(['serviceAccount', 'assignedCollector'])
            ->where('pickup_type', 'request')
            ->orderByDesc('requested_at')
            ->take(5)
            ->get();

        return view('home', compact(
            'stats', 
            'dashboardStats',
            'complaintStats',
            'topAreas',
            'currentMonthName',
            'recentUsers',
            'recentServiceAccounts',
            'recentPickupRequests'
        ));
    }
}
