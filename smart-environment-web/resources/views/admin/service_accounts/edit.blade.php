@extends('layouts.master')

@section('title','Edit Akun Layanan')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
          <x-button as="a" href="{{ route('admin.service-accounts.index') }}" variant="secondary" size="sm">
            ← Kembali
          </x-button>
        </div>
        <h2 class="text-2xl font-bold text-black dark:text-white">Edit Akun Layanan</h2>
        <p class="text-sm text-gray-500 mt-1">Perbarui data akun layanan.</p>
      </div>

      <form action="{{ route('admin.service-accounts.update', $serviceAccount->id) }}" method="POST" class="space-y-6" x-data="serviceAccountForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- User -->
          <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              User <span class="text-red-500">*</span>
            </label>
            <select name="user_id" id="user_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('user_id') border-red-500 @enderror">
              <option value="">Pilih User</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" {{ (old('user_id', $serviceAccount->user_id) == $user->id) ? 'selected' : '' }}>
                  {{ $user->name }} ({{ $user->email }})
                </option>
              @endforeach
            </select>
            @error('user_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Kecamatan -->
          <div>
            <label for="kecamatan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kecamatan <span class="text-red-500">*</span>
            </label>
            <select name="kecamatan_id" id="kecamatan_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                    x-model="kecamatanId"
                    @change="loadKelurahans()">
              <option value="">Pilih Kecamatan</option>
              @foreach($kecamatans as $kecamatan)
                <option value="{{ $kecamatan->id }}">{{ $kecamatan->name }}</option>
              @endforeach
            </select>
          </div>

          <!-- Kelurahan -->
          <div>
            <label for="area_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Area (Kelurahan) <span class="text-red-500">*</span>
            </label>
            <select name="area_id" id="area_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('area_id') border-red-500 @enderror"
                    x-model="areaId"
                    :disabled="!kecamatanId || kelurahans.length === 0">
              <option value="">Pilih Kelurahan</option>
              <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
                <option :value="kelurahan.id" x-text="kelurahan.name"></option>
              </template>
            </select>
            @error('area_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- RW -->
          <div>
            <label for="rw" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              RW (Opsional)
            </label>
            <input type="text" name="rw" id="rw" value="{{ old('rw', $serviceAccount->rw->name ?? '') }}"
                   x-model="rw"
                   @blur="formatRW($event)"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('rw') border-red-500 @enderror"
                   placeholder="Contoh: 1, 01, atau RW 01">
            @error('rw')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Ketik nomor RW (akan otomatis diformat menjadi "RW 01")</p>
          </div>

          <!-- Nama -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nama Akun <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" id="name" value="{{ old('name', $serviceAccount->name) }}" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                   placeholder="Masukkan nama akun">
            @error('name')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Kontak Telepon -->
          <div>
            <label for="contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              No. Telepon
            </label>
            <input type="text" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $serviceAccount->contact_phone) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('contact_phone') border-red-500 @enderror"
                   placeholder="Contoh: 081234567890">
            @error('contact_phone')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <!-- Alamat -->
        <div>
          <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Alamat
          </label>
          <textarea name="address" id="address" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('address') border-red-500 @enderror"
                    placeholder="Masukkan alamat lengkap">{{ old('address', $serviceAccount->address) }}</textarea>
          @error('address')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>

        <!-- Koordinat -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Latitude
            </label>
            <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', $serviceAccount->latitude) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('latitude') border-red-500 @enderror"
                   placeholder="Contoh: -6.200000">
            @error('latitude')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Longitude
            </label>
            <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', $serviceAccount->longitude) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('longitude') border-red-500 @enderror"
                   placeholder="Contoh: 106.816666">
            @error('longitude')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <!-- Catatan -->
        <div>
          <label for="note" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Catatan
          </label>
          <textarea name="note" id="note" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('note') border-red-500 @enderror"
                    placeholder="Tambahkan catatan jika diperlukan">{{ old('note', $serviceAccount->note) }}</textarea>
          @error('note')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>

        <!-- Status -->
        <div>
          <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Status <span class="text-red-500">*</span>
          </label>
          <select name="status" id="status" required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('status') border-red-500 @enderror">
            <option value="active" {{ old('status', $serviceAccount->status) == 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ old('status', $serviceAccount->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
          </select>
          @error('status')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <x-button type="submit" variant="primary" size="md">Perbarui</x-button>
          <x-button as="a" href="{{ route('admin.service-accounts.index') }}" variant="secondary" size="md">Batal</x-button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('body:end')
<script>
  function serviceAccountForm() {
    return {
      kecamatanId: '{{ old('kecamatan_id', $serviceAccount->area->parent_id ?? '') }}',
      areaId: '{{ old('area_id', $serviceAccount->area_id) }}',
      rw: '{{ old('rw', $serviceAccount->rw->name ?? '') }}',
      kelurahans: [],

      async init() {
        // Load kelurahans if kecamatan is selected
        if (this.kecamatanId) {
          await this.loadKelurahans();
        }
      },

      async loadKelurahans() {
        this.kelurahans = [];

        if (!this.kecamatanId) {
          this.areaId = '';
          return;
        }

        try {
          const response = await fetch(`{{ route('admin.service-accounts.kelurahans') }}?kecamatan_id=${this.kecamatanId}`);
          this.kelurahans = await response.json();
        } catch (error) {
          console.error('❌ Error loading kelurahans:', error);
          this.kelurahans = [];
        }
      },

      formatRW(event) {
        const input = event.target;
        let value = input.value.trim();
        
        // If empty, do nothing
        if (!value) return;
        
        // If already has "RW " prefix, do nothing
        if (value.toUpperCase().startsWith('RW ')) return;
        
        // If it's purely numeric, format it
        if (!isNaN(value)) {
          const paddedValue = String(value).padStart(2, '0');
          input.value = `RW ${paddedValue}`;
          this.rw = input.value;
        }
      }
    }
  }
</script>
@endpush
@endsection
