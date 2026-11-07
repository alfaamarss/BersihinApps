@extends('layouts.master')

@section('title', 'Tambah Harga Sampah')

@section('content')
    <div class="grid grid-cols-12 gap-4 md:gap-6">
        <div class="col-span-12 lg:col-span-8">
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-2xl font-bold text-black dark:text-white mb-6">Tambah Harga Sampah Baru</h2>

                <form action="{{ route('admin.wastes.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <x-form.select name="waste_id" label="Jenis Sampah" :options="$wastes->map(function ($w) {
                        return [
                            'value' => $w->id,
                            'label' => ucfirst($w->category) . ($w->code ? ' (' . $w->code . ')' : ''),
                        ];
                    })" placeholder="Pilih Jenis Sampah"
                        required />
                    <x-form.select name="pocket_size_id" label="Ukuran" :options="collect($sizes)->map(function ($s) {
                        return ['value' => $s->id, 'label' => $s->name];
                    })" placeholder="Pilih Ukuran"
                        required />
                    <x-form.input type="number" name="price" label="Harga (Rp)" step="0.01" min="0.01"
                        placeholder="Masukkan harga" required />
                    <x-form.input type="date" name="effective_to" label="Berlaku Sampai (opsional)"
                        help="Kosongkan jika harga berlaku selamanya" />

                    <div class="flex justify-end gap-3">
                        <x-button as="a" href="{{ route('admin.wastes.index') }}" variant="secondary">Batal</x-button>
                        <x-button type="submit" variant="primary">Simpan Harga</x-button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-span-12 lg:col-span-4">
            <div
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="font-semibold mb-3">Tambah Ukuran Kantong Baru</h3>
                <form action="{{ route('admin.sampah.size.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <x-form.input name="name" label="Nama Ukuran" placeholder="Besar/Sedang/Kecil" required />
                    <x-form.input name="description" label="Deskripsi (opsional)" />
                    <x-button type="submit" variant="secondary">Tambah Ukuran</x-button>
                </form>
            </div>
        </div>
    </div>
@endsection
