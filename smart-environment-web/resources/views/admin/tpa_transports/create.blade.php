@extends('layouts.master')

@section('title', 'Tambah Riwayat Angkut TPA')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
          <x-button as="a" href="{{ route('admin.tpa-transports.index') }}" variant="secondary" size="sm">
            ← Kembali
          </x-button>
        </div>
        <h2 class="text-2xl font-bold text-black dark:text-white">Tambah Riwayat Angkut TPA</h2>
        <p class="text-sm text-gray-500 mt-1">Isi form untuk mencatat pengangkutan sampah dari TPS ke TPA.</p>
      </div>

      <form action="{{ route('admin.tpa-transports.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Tanggal & Waktu -->
          <div>
            <label for="transported_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tanggal & Waktu <span class="text-red-500">*</span>
            </label>
            <input type="datetime-local" name="transported_at" id="transported_at" 
                   value="{{ old('transported_at', now()->format('Y-m-d\TH:i')) }}" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('transported_at') border-red-500 @enderror">
            @error('transported_at')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Asal TPS -->
          <div>
            <label for="garbage_dump_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Asal TPS <span class="text-red-500">*</span>
            </label>
            <select name="garbage_dump_id" id="garbage_dump_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('garbage_dump_id') border-red-500 @enderror">
              <option value="">Pilih TPS</option>
              @foreach($garbageDumps as $dump)
                <option value="{{ $dump->id }}" {{ old('garbage_dump_id') == $dump->id ? 'selected' : '' }}>{{ $dump->name }}</option>
              @endforeach
            </select>
            @error('garbage_dump_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Collector -->
          <div>
            <label for="collector_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Collector <span class="text-red-500">*</span>
            </label>
            <select name="collector_id" id="collector_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('collector_id') border-red-500 @enderror">
              <option value="">Pilih Collector</option>
              @foreach($collectors as $collector)
                <option value="{{ $collector->id }}" {{ old('collector_id') == $collector->id ? 'selected' : '' }}>{{ $collector->name }}</option>
              @endforeach
            </select>
            @error('collector_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Catatan -->
          <div class="md:col-span-2">
            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Catatan <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('notes') border-red-500 @enderror"
                      placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
            @error('notes')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <x-button as="a" href="{{ route('admin.tpa-transports.index') }}" variant="secondary">
            Batal
          </x-button>
          <x-button type="submit" variant="primary">
            Simpan
          </x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
