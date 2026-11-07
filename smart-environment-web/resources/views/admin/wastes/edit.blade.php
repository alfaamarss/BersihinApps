@extends('layouts.master')

@section('title','Edit Harga Sampah')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12 lg:col-span-8">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <h2 class="text-2xl font-bold text-black dark:text-white mb-6">Edit Harga Sampah</h2>

      <form action="{{ route('admin.wastes.update', $waste->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <x-form.select name="waste_id" label="Jenis Sampah" :options="$wastes->map(function($w) { return ['value' => $w->id, 'label' => ucfirst($w->category) . ($w->code ? ' ('.$w->code.')' : '')]; })" placeholder="Pilih Jenis Sampah" required :value="old('waste_id', $waste->waste_id)" />
        <x-form.select name="pocket_size_id" label="Ukuran" :options="collect($sizes)->map(function($s) { return ['value' => $s->id, 'label' => $s->name]; })" placeholder="Pilih Ukuran" required :value="old('pocket_size_id', $waste->pocket_size_id)" />
        <x-form.input type="number" name="price" label="Harga (Rp)" step="0.01" min="0.01" placeholder="Masukkan harga" required>{{ old('price', number_format($waste->price, 2, '.', '')) }}</x-form.input>
        <x-form.input type="date" name="effective_from" label="Berlaku Dari" min="{{ now()->toDateString() }}" required>{{ old('effective_from', \Carbon\Carbon::parse($waste->effective_from)->toDateString()) }}</x-form.input>
        <x-form.input type="date" name="effective_to" label="Berlaku Sampai (opsional)" :min="old('effective_from', \Carbon\Carbon::parse($waste->effective_from)->toDateString())" help="Kosongkan jika harga berlaku selamanya">{{ old('effective_to', $waste->effective_to ? \Carbon\Carbon::parse($waste->effective_to)->toDateString() : '') }}</x-form.input>

        <div class="flex justify-end gap-3">
          <x-button as="a" href="{{ route('admin.wastes.index') }}" variant="secondary">Batal</x-button>
          <x-button type="submit" variant="primary">Simpan Perubahan</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
