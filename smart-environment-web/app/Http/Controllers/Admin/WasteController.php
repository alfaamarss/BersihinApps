<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waste;
use App\Models\PocketSize;
use App\Models\WastePricing;
use Illuminate\Http\Request;

class WasteController extends Controller
{
    public function index()
    {
        $wastePricings = WastePricing::with(['waste', 'size'])->latest()->paginate(10);
        return view('admin.wastes.index', compact('wastePricings'));
    }

    public function create()
    {
        $wastes = Waste::all();
        $sizes = PocketSize::all();
        return view('admin.wastes.create', compact('wastes', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'waste_id' => 'required|exists:wastes,id',
            'pocket_size_id' => 'required|exists:pocket_sizes,id',
            'price' => 'required|numeric|gt:0',
            'effective_to' => 'nullable|date|after_or_equal:today',
        ], [
            'effective_to.after_or_equal' => 'Tanggal berlaku sampai harus sama dengan atau lebih besar dari hari ini.',
            'effective_to.date' => 'Tanggal berlaku sampai harus berupa tanggal yang valid.',
            'price.required' => 'Harga harus diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.gt' => 'Harga harus lebih besar dari 0.',
            'waste_id.required' => 'Jenis sampah harus dipilih.',
            'waste_id.exists' => 'Jenis sampah yang dipilih tidak valid.',
            'pocket_size_id.required' => 'Ukuran harus dipilih.',
            'pocket_size_id.exists' => 'Ukuran yang dipilih tidak valid.',
        ]);

        // Check if combination already exists
        $exists = WastePricing::where('waste_id', $request->waste_id)
            ->where('pocket_size_id', $request->pocket_size_id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['waste_id' => 'Kombinasi jenis sampah dan ukuran ini sudah terdaftar.'])
                ->withInput();
        }

        WastePricing::create([
            'waste_id' => $request->waste_id,
            'pocket_size_id' => $request->pocket_size_id,
            'price' => $request->price,
            'effective_from' => now()->toDateString(),
            'effective_to' => $request->effective_to,
        ]);

        return redirect()->route('admin.wastes.index')->with('success', 'Harga sampah berhasil disimpan.');
    }

    public function edit(WastePricing $waste)
    {
        $wastes = Waste::all();
        $sizes = PocketSize::all();
        return view('admin.wastes.edit', compact('waste', 'wastes', 'sizes'));
    }

    public function update(Request $request, WastePricing $waste)
    {
        $request->validate([
            'waste_id' => 'required|exists:wastes,id',
            'pocket_size_id' => 'required|exists:pocket_sizes,id',
            'price' => 'required|numeric|gt:0',
            'effective_from' => 'required|date|after_or_equal:today',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ], [
            'effective_from.after_or_equal' => 'Tanggal berlaku dari harus sama dengan atau lebih besar dari hari ini.',
            'effective_from.required' => 'Tanggal berlaku dari harus diisi.',
            'effective_from.date' => 'Tanggal berlaku dari harus berupa tanggal yang valid.',
            'effective_to.after_or_equal' => 'Tanggal berlaku sampai harus sama dengan atau lebih besar dari tanggal berlaku dari.',
            'effective_to.date' => 'Tanggal berlaku sampai harus berupa tanggal yang valid.',
            'price.required' => 'Harga harus diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.gt' => 'Harga harus lebih besar dari 0.',
            'waste_id.required' => 'Jenis sampah harus dipilih.',
            'waste_id.exists' => 'Jenis sampah yang dipilih tidak valid.',
            'pocket_size_id.required' => 'Ukuran harus dipilih.',
            'pocket_size_id.exists' => 'Ukuran yang dipilih tidak valid.',
        ]);

        // Check if combination already exists (excluding current record)
        $exists = WastePricing::where('waste_id', $request->waste_id)
            ->where('pocket_size_id', $request->pocket_size_id)
            ->where('id', '!=', $waste->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['waste_id' => 'Kombinasi jenis sampah dan ukuran ini sudah terdaftar.'])
                ->withInput();
        }

        $waste->update($request->only(['waste_id', 'pocket_size_id', 'price', 'effective_from', 'effective_to']));

        return redirect()->route('admin.wastes.index')->with('success', 'Harga sampah berhasil diperbarui.');
    }

    public function destroy(WastePricing $waste)
    {
        $waste->delete();
        return redirect()->route('admin.wastes.index')->with('success', 'Harga sampah berhasil dihapus.');
    }



    public function storeSize(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        PocketSize::create($request->only('name', 'description'));

        return back()->with('success', 'Ukuran kantong berhasil ditambahkan.');
    }
}
