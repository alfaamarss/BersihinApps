<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GarbageDump;
use App\Models\TpaTransport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TpaTransportController extends Controller
{
    public function index(Request $request)
    {
        $query = TpaTransport::with(['garbageDump', 'collector']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('garbageDump', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('collector', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('transported_at', $request->date);
        }

        $transports = $query->latest('transported_at')->paginate(15);

        return view('admin.tpa_transports.index', compact('transports'));
    }

    public function show(TpaTransport $tpaTransport)
    {
        $tpaTransport->load(['garbageDump', 'collector']);
        return view('admin.tpa_transports.show', compact('tpaTransport'));
    }

    public function create()
    {
        $garbageDumps = GarbageDump::where('status', 'active')->orderBy('name')->get();
        $collectors = User::role('collector')->orderBy('name')->get();

        return view('admin.tpa_transports.create', compact('garbageDumps', 'collectors'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'garbage_dump_id' => 'required|exists:garbage_dumps,id',
            'collector_id' => 'required|exists:users,id',
            'transported_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        TpaTransport::create($request->all());

        return redirect()->route('admin.tpa-transports.index')->with('success', 'Data riwayat angkut berhasil ditambahkan.');
    }

    public function edit(TpaTransport $tpaTransport)
    {
        $garbageDumps = GarbageDump::where('status', 'active')->orderBy('name')->get();
        $collectors = User::role('collector')->orderBy('name')->get();

        return view('admin.tpa_transports.edit', compact('tpaTransport', 'garbageDumps', 'collectors'));
    }

    public function update(Request $request, TpaTransport $tpaTransport)
    {
        $validator = Validator::make($request->all(), [
            'garbage_dump_id' => 'required|exists:garbage_dumps,id',
            'collector_id' => 'required|exists:users,id',
            'transported_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $tpaTransport->update($request->all());

        return redirect()->route('admin.tpa-transports.index')->with('success', 'Data riwayat angkut berhasil diperbarui.');
    }

    public function destroy(TpaTransport $tpaTransport)
    {
        $tpaTransport->delete();
        return redirect()->route('admin.tpa-transports.index')->with('success', 'Data riwayat angkut berhasil dihapus.');
    }
}
