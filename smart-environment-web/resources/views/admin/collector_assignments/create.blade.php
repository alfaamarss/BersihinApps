@extends('layouts.master')

@section('title','Tugaskan Collector')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12 lg:col-span-8">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-black dark:text-white">Tugaskan Collector</h2>
        <p class="text-sm text-gray-500 mt-1">Tentukan collector dan area untuk penugasan pengangkutan sampah.</p>
      </div>

      <form method="POST" action="{{ route('admin.collector-assignments.store') }}" x-data="assignmentForm()">
        @csrf
        
        <!-- Hidden inputs to persist values on validation error -->
        <input type="hidden" name="kecamatan_id_select" x-model="kecamatanId">
        <input type="hidden" name="area_id_select" x-model="areaId">

        <div class="space-y-5">
          <!-- Collector -->
          <div>
            <x-form.select
              name="user_id"
              label="Collector"
              :options="$collectors->map(fn($c) => ['value' => $c->id, 'label' => $c->name . ' (' . $c->email . ')'])->toArray()"
              placeholder="Pilih Collector"
              :value="old('user_id')"
              required
            />
            @error('user_id')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <!-- Kecamatan -->
          <div>
            <x-form.select
              name="kecamatan_id_select"
              label="Kecamatan"
              :options="$kecamatans->map(fn($k) => ['value' => $k->id, 'label' => $k->name])->toArray()"
              placeholder="Pilih Kecamatan"
              x-model="kecamatanId"
              @change="loadKelurahans()"
            />
          </div>

          <!-- Kelurahan -->
          <div>
            <label for="area_id" class="mb-1.5 block text-sm font-medium text-black dark:text-white">
              Kelurahan <span class="text-red-500">*</span>
            </label>
            <select
              id="area_id"
              name="area_id_select"
              class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 pr-10 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
              x-model="areaId"
              @change="loadSchedules(); loadRws();"
              :disabled="!kecamatanId || kelurahans.length === 0"
            >
              <option value="">Pilih Kelurahan</option>
              <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
                <option :value="kelurahan.id" x-text="kelurahan.name"></option>
              </template>
            </select>
          </div>

          <!-- RW (Optional) -->
          <div>
            <label for="rw_id" class="mb-1.5 block text-sm font-medium text-black dark:text-white">
              RW (Rukun Warga) <span class="text-gray-400 text-xs font-normal">- Opsional</span>
            </label>
            <select
              id="rw_id"
              name="rw_id"
              class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 pr-10 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
              x-model="rwId"
              :disabled="!areaId || rws.length === 0"
            >
              <option value="">Semua RW (tidak spesifik)</option>
              <template x-for="rw in rws" :key="rw.id">
                <option 
                  :value="rw.id" 
                  :disabled="rw.assigned"
                  x-text="rw.name + (rw.assigned ? ' (Sudah ditugaskan ke ' + rw.assigned_to + ')' : '')"
                ></option>
              </template>
            </select>
            @error('rw_id')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-gray-500" x-show="!areaId">
              Pilih kelurahan terlebih dahulu untuk melihat RW
            </p>
            <p class="mt-1.5 text-xs text-gray-500" x-show="areaId && rws.length === 0 && !loadingRws">
              Tidak ada RW terdaftar untuk kelurahan ini
            </p>
            <p class="mt-1.5 text-xs text-gray-500" x-show="loadingRws">
              Memuat data RW...
            </p>
            <p class="mt-1.5 text-xs text-gray-500" x-show="areaId && rws.length > 0 && !entireKelurahanAssigned">
              Kosongkan jika collector menangani semua RW dalam kelurahan ini. <span class="text-amber-600">RW yang sudah ditugaskan ke collector lain tidak dapat dipilih.</span>
            </p>
            <p class="mt-1.5 text-xs text-red-600 font-medium" x-show="entireKelurahanAssigned">
              ⚠️ Seluruh kelurahan ini sudah ditugaskan ke <span x-text="entireKelurahanAssignedTo"></span> untuk jadwal ini.
            </p>
          </div>

          <!-- Jadwal -->
          <div>
            <label for="schedule_id" class="mb-1.5 block text-sm font-medium text-black dark:text-white">
              Pilih Jadwal Pengangkutan <span class="text-red-500">*</span>
            </label>
            <select
              id="schedule_id"
              name="schedule_id"
              class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 pr-10 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
              x-model="scheduleId"
              @change="onScheduleChange()"
              :disabled="!areaId || schedules.length === 0"
              required
            >
              <option value="" x-text="!areaId ? 'Pilih kelurahan terlebih dahulu' : (areaId && schedules.length === 0 ? 'Memuat jadwal...' : 'Pilih Jadwal')"></option>
              <template x-for="schedule in schedules" :key="schedule.id">
                <option :value="schedule.id" x-text="schedule.label"></option>
              </template>
            </select>
            @error('schedule_id')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-gray-500" x-show="!areaId">
              Pilih kelurahan terlebih dahulu untuk melihat jadwal yang tersedia
            </p>
            <p class="mt-1.5 text-xs text-gray-500" x-show="areaId && schedules.length === 0">
              Belum ada jadwal untuk kelurahan ini. Buat jadwal terlebih dahulu.
            </p>
            <p class="mt-1.5 text-xs text-gray-500" x-show="areaId && schedules.length > 0">
              Pilih jadwal pengangkutan spesifik yang akan ditugaskan ke collector
            </p>
          </div>

          <!-- Periode Tugas -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="start_date" class="mb-1.5 block text-sm font-medium text-black dark:text-white">
                Tanggal Mulai <span class="text-red-500">*</span>
              </label>
              <input
                type="date"
                id="start_date"
                name="start_date"
                x-model="startDate"
                :min="'{{ now()->format('Y-m-d') }}'"
                required
                class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
              />
              @error('start_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
              <p class="mt-1 text-xs text-gray-500">Tanggal mulai harus hari ini atau setelahnya</p>
            </div>
            <div>
              <label for="end_date" class="mb-1.5 block text-sm font-medium text-black dark:text-white">
                Tanggal Selesai
              </label>
              <input
                type="date"
                id="end_date"
                name="end_date"
                x-model="endDate"
                :min="startDate"
                class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
              />
              @error('end_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
              <p class="mt-1 text-xs text-gray-500">Kosongkan jika penugasan tidak memiliki batas waktu</p>
            </div>
          </div>

          <!-- Generate Deliveries Option -->
          <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
            <label class="flex items-start gap-3 cursor-pointer">
              <input 
                type="checkbox" 
                name="generate_deliveries" 
                value="1" 
                checked
                class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
              />
              <div class="flex-1">
                <span class="font-medium text-gray-900 dark:text-white">
                  Generate Waste Deliveries Otomatis
                </span>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                  Sistem akan otomatis membuat tugas pengambilan sampah untuk 30 hari ke depan, 
                  sesuai dengan jadwal area yang dipilih dan service accounts yang sudah terdaftar di area/RW tersebut.
                </p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                  💡 <strong>Tips:</strong> Pastikan sudah ada service accounts terdaftar di area/RW yang dipilih 
                  agar deliveries dapat di-generate dengan benar.
                </p>
              </div>
            </label>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <x-button type="submit" variant="primary">Simpan Penugasan</x-button>
            <x-button as="a" href="{{ route('admin.collector-assignments.index') }}" variant="secondary">Batal</x-button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Info Card -->
  <div class="col-span-12 lg:col-span-4">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Informasi</h3>
      <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
        <p>
          <strong class="text-gray-900 dark:text-white">Penugasan Collector</strong> digunakan untuk menentukan collector mana yang bertanggung jawab mengangkut sampah di area tertentu.
        </p>
        <p>
          Setiap area memiliki jadwal pengangkutan yang sudah ditentukan. Collector yang ditugaskan akan mengikuti jadwal tersebut.
        </p>
        <p>
          Anda dapat menentukan periode penugasan dengan mengisi tanggal mulai dan tanggal selesai. Jika tidak ada batas waktu, kosongkan tanggal selesai.
        </p>
      </div>
    </div>
  </div>
</div>

@push('body:end')
<script>
  function assignmentForm() {
    return {
      kecamatanId: '{{ old('kecamatan_id_select') }}',
      areaId: '{{ old('area_id_select') }}',
      rwId: '{{ old('rw_id') }}',
      scheduleId: '{{ old('schedule_id') }}',
      startDate: '{{ old('start_date', now()->format('Y-m-d')) }}',
      endDate: '{{ old('end_date') }}',
      kelurahans: [],
      rws: [],
      schedules: [],
      entireKelurahanAssigned: false,
      entireKelurahanAssignedTo: '',
      loadingRws: false,

      async init() {
        // If there are old values (validation failed), restore the dependent dropdowns
        if (this.kecamatanId) {
          await this.loadKelurahans();
          
          // After kelurahans loaded, restore areaId if it exists
          if (this.areaId) {
            // Wait a bit for kelurahans to be set
            await this.$nextTick();
            await this.loadSchedules();
            await this.loadRws();
            if (this.scheduleId) {
              await this.loadRwsWithStatus();
            }
          }
        }
        
        // Watch for date changes to reload RW status
        this.$watch('startDate', () => {
          if (this.scheduleId) this.loadRwsWithStatus();
        });
        this.$watch('endDate', () => {
          if (this.scheduleId) this.loadRwsWithStatus();
        });
      },

      async loadKelurahans() {
        this.kelurahans = [];
        // Don't reset areaId if we're restoring from old values
        const shouldResetArea = !('{{ old('area_id_select') }}');
        if (shouldResetArea) {
          this.areaId = '';
          this.rws = [];
          this.rwId = '';
          this.schedules = [];
          this.scheduleId = '';
          this.entireKelurahanAssigned = false;
          this.entireKelurahanAssignedTo = '';
        }

        if (!this.kecamatanId) return;

        try {
          const response = await fetch(`{{ route('admin.collector-assignments.kelurahans') }}?kecamatan_id=${this.kecamatanId}`);
          this.kelurahans = await response.json();
        } catch (error) {
          console.error('Error loading kelurahans:', error);
        }
      },

      async loadRws() {
        this.loadingRws = true;
        this.rws = [];
        this.entireKelurahanAssigned = false;
        this.entireKelurahanAssignedTo = '';
        
        // Don't reset rwId if we're restoring from old values
        const shouldResetRw = !('{{ old('rw_id') }}');
        if (shouldResetRw) {
          this.rwId = '';
        }

        if (!this.areaId) {
          this.loadingRws = false;
          return;
        }

        try {
          const response = await fetch(`{{ route('admin.collector-assignments.rws') }}?kelurahan_id=${this.areaId}`);
          const data = await response.json();
          
          // Format data to match expected structure
          this.rws = data.map(rw => ({
            id: rw.id,
            name: rw.name,
            assigned: false,
            assigned_to: null
          }));
        } catch (error) {
          console.error('❌ Error loading RWs:', error);
          this.rws = [];
        } finally {
          this.loadingRws = false;
        }
      },

      async loadRwsWithStatus() {
        if (!this.areaId || !this.scheduleId) return;

        this.loadingRws = true;
        this.entireKelurahanAssigned = false;
        this.entireKelurahanAssignedTo = '';

        try {
          let url = `{{ route('admin.collector-assignments.rws-with-status') }}?kelurahan_id=${this.areaId}&schedule_id=${this.scheduleId}&start_date=${this.startDate}`;
          if (this.endDate) {
            url += `&end_date=${this.endDate}`;
          }
          
          const response = await fetch(url);
          const data = await response.json();
          
          if (data.entire_kelurahan_assigned) {
            this.entireKelurahanAssigned = true;
            this.entireKelurahanAssignedTo = data.assigned_to;
          }
          
          this.rws = data.rws || [];
        } catch (error) {
          console.error('❌ Error loading RWs with status:', error);
        } finally {
          this.loadingRws = false;
        }
      },

      async loadSchedules() {
        this.schedules = [];
        // Don't reset scheduleId if we're restoring from old values
        const shouldResetSchedule = !('{{ old('schedule_id') }}');
        if (shouldResetSchedule) {
          this.scheduleId = '';
          this.entireKelurahanAssigned = false;
          this.entireKelurahanAssignedTo = '';
        }

        if (!this.areaId) return;

        try {
          const response = await fetch(`{{ route('admin.collector-assignments.schedules') }}?area_id=${this.areaId}`);
          this.schedules = await response.json();
        } catch (error) {
          console.error('Error loading schedules:', error);
        }
      },

      onScheduleChange() {
        this.loadRwsWithStatus();
      }
    }
  }

  // SweetAlert2 for flash messages
  document.addEventListener('DOMContentLoaded', function() {

    @if(session('warning'))
      Swal.fire({
        icon: 'warning',
        title: 'Perhatian!',
        text: '{{ session('warning') }}',
        confirmButtonColor: '#F59E0B'
      });
    @endif
  });
</script>
@endpush
@endsection
