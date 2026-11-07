@extends('layouts.master')

@section('title','Tambah Jadwal')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-black dark:text-white">Tambah Jadwal Pengangkutan</h2>
        <p class="text-sm text-gray-500 mt-1">Buat jadwal pengambilan sampah baru.</p>
      </div>

      <form action="{{ route('admin.schedules.store') }}" method="POST" class="space-y-6" x-data="scheduleForm()">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <x-form.select
              name="kecamatan_id"
              label="Kecamatan"
              :options="$kecamatans->map(fn($k) => ['value' => $k->id, 'label' => $k->name])->toArray()"
              placeholder="Pilih Kecamatan"
              :value="old('kecamatan_id')"
              x-model="kecamatanId"
              @change="loadKelurahans()"
              required
            />
          </div>

          <div>
            <label for="area_id" class="mb-1.5 block text-sm font-medium text-black dark:text-white">
              Kelurahan <span class="text-red-500">*</span>
            </label>
            <select
              id="area_id"
              name="area_id"
              required
              class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 pr-10 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
              x-model="kelurahanId"
              :disabled="!kecamatanId || loading"
            >
              <option value="">Pilih Kelurahan</option>
              <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
                <option :value="kelurahan.id" x-text="kelurahan.name"></option>
              </template>
            </select>
            <p class="mt-1 text-xs text-gray-500" x-show="!kecamatanId">Pilih kecamatan terlebih dahulu</p>
            <p class="mt-1 text-xs text-gray-500" x-show="loading">Memuat kelurahan...</p>
            @error('area_id')
              <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <x-form.select
              name="day_of_week"
              label="Hari"
              :options="$dayOptions"
              :value="old('day_of_week', 'Monday')"
              required
            />
          </div>

          <div>
            <x-form.input
              type="time"
              name="pickup_time"
              label="Waktu Pengambilan"
              :value="old('pickup_time', '07:00')"
              required
            />
            <p class="mt-1 text-xs text-gray-500">Format: HH:MM (contoh: 07:00 AM/PM)</p>
          </div>
        </div>

        <div class="flex items-center gap-3 pt-4">
          <x-button type="submit" variant="primary" size="md">Simpan Jadwal</x-button>
          <x-button as="a" href="{{ route('admin.schedules.index') }}" variant="secondary" size="md">Batal</x-button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('body:end')
<script>
  function scheduleForm() {
    return {
      kecamatanId: '{{ old('kecamatan_id', '') }}',
      kelurahanId: '{{ old('area_id', '') }}',
      kelurahans: [],
      loading: false,

      async loadKelurahans() {
        if (!this.kecamatanId) {
          this.kelurahans = [];
          this.kelurahanId = '';
          return;
        }

        this.loading = true;
        try {
          const response = await fetch(`{{ route('admin.schedules.kelurahans') }}?kecamatan_id=${this.kecamatanId}`);
          this.kelurahans = await response.json();

          // Reset kelurahan selection if not in new list
          if (this.kelurahanId) {
            const exists = this.kelurahans.find(k => k.id == this.kelurahanId);
            if (!exists) {
              this.kelurahanId = '';
            }
          }
        } catch (error) {
          console.error('Error loading kelurahans:', error);
          this.kelurahans = [];
        } finally {
          this.loading = false;
        }
      },

      init() {
        // Load kelurahans if kecamatan is already selected (for old() values)
        if (this.kecamatanId) {
          this.loadKelurahans();
        }
      }
    }
  }
</script>
@endpush
@endsection
