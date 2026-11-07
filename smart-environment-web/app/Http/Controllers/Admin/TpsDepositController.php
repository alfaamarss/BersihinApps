<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TpsDeposit;
use App\Models\User;
use App\Models\GarbageDump;
use Illuminate\Http\Request;

class TpsDepositController extends Controller
{
    public function index(Request $request)
    {
        $query = TpsDeposit::with(['collector', 'garbageDump']);

        // Search by collector name
        if ($request->filled('search')) {
            $query->whereHas('collector', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('garbage_dump_id')) {
            $query->where('garbage_dump_id', $request->garbage_dump_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('deposited_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('deposited_at', '<=', $request->end_date);
        }

        $deposits = $query->latest('deposited_at')->paginate(10);
        
        $garbageDumps = GarbageDump::all();

        return view('admin.tps_deposits.index', compact('deposits', 'garbageDumps'));
    }

    public function show(TpsDeposit $tpsDeposit)
    {
        $tpsDeposit->load(['collector', 'garbageDump']);
        return view('admin.tps_deposits.show', compact('tpsDeposit'));
    }
}
