@extends('layouts.master')

@section('title','Edit Jadwal')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-black dark:text-white">Edit Jadwal Pengangkutan</h2>
        <p class="text-sm text-gray-500 mt-1">Perbarui jadwal pengambilan sampah.</p>
      </div>

      {{-- Warning for active collector assignments --}}
      @if(isset($activeAssignments) && $activeAssignments->isNotEmpty())
        <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
          <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1">
              <h4 class="font-semibold text-yellow-800 dark:text-yellow-200">Perhatian: Jadwal ini memiliki penugasan collector aktif</h4>
              <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                Mengubah <strong>area</strong> atau <strong>hari</strong> jadwal ini dapat menyebabkan konflik dengan jadwal lain.
                Collector hanya boleh ditugaskan ke kelurahan-kelurahan dalam <strong>1 kecamatan yang sama</strong> pada hari yang sama.
              </p>
              <div class="mt-3">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Collector yang ditugaskan:</p>
                <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                  @foreach($activeAssignments as $assignment)
                    <li class="flex items-center gap-2">
                      <span class="inline-block w-2 h-2 rounded-full bg-yellow-500"></span>
                      <span>
                        <strong>{{ $assignment->user->name ?? '-' }}</strong>
                        @if($assignment->rw)
                          <span class="text-xs">(RW {{ $assignment->rw->name }})</span>
                        @endif
                        - {{ $assignment->start_date->format('d M Y') }} s/d {{ $assignment->end_date ? $assignment->end_date->format('d M Y') : 'Tidak terbatas' }}
                      </span>
                    </li>
                  @endforeach
                </ul>
              </div>
            </div>
          </div>
        </div>
      @endif

      {{-- Conflict warning from session --}}
      @if(session('schedule_conflict_warning'))
        <div class="mb-6 rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20" x-data="{ showOptions: true }">
          <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-orange-600 dark:text-orange-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="flex-1">
              <h4 class="font-semibold text-orange-800 dark:text-orange-200">Konfirmasi Perubahan Diperlukan</h4>
              <p class="mt-1 text-sm text-orange-700 dark:text-orange-300 whitespace-pre-line">{{ session('schedule_conflict_warning') }}</p>
              
              <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-orange-200 dark:border-orange-700" x-show="showOptions">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Pilih tindakan:</p>
                <div class="space-y-3">
                  <label class="flex items-start gap-3 cursor-pointer">
                    <input type="radio" name="conflict_action" value="cancel" class="mt-1" checked onclick="document.getElementById('force_update').value='0'; document.getElementById('end_assignments').value='0';">
                    <div>
                      <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Batalkan perubahan</span>
                      <p class="text-xs text-gray-500 dark:text-gray-400">Kembali dan tidak mengubah jadwal</p>
                    </div>
                  </label>
                  <label class="flex items-start gap-3 cursor-pointer">
                    <input type="radio" name="conflict_action" value="force" class="mt-1" onclick="document.getElementById('force_update').value='1'; document.getElementById('end_assignments').value='0';">
                    <div>
                      <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Tetap ubah jadwal (pertahankan penugasan)</span>
                      <p class="text-xs text-gray-500 dark:text-gray-400">Jadwal akan diubah, penugasan collector tetap aktif. Pastikan tidak ada konflik kecamatan.</p>
                    </div>
                  </label>
                  <label class="flex items-start gap-3 cursor-pointer">
                    <input type="radio" name="conflict_action" value="end" class="mt-1" onclick="document.getElementById('force_update').value='1'; document.getElementById('end_assignments').value='1';">
                    <div>
                      <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Ubah jadwal dan akhiri semua penugasan</span>
                      <p class="text-xs text-gray-500 dark:text-gray-400">Jadwal akan diubah dan semua penugasan collector aktif akan diakhiri</p>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endif

      <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST" class="space-y-6" x-data="scheduleForm()" @submit="handleSubmit($event)">
        @csrf
        @method('PUT')
        
        {{-- Hidden fields for conflict handling --}}
        <input type="hidden" name="force_update" id="force_update" x-model="forceUpdate">
        <input type="hidden" name="end_assignments" id="end_assignments" x-model="endAssignments">

        {{-- Real-time conflict warning --}}
        <div x-show="conflictInfo && conflictInfo.has_active_assignments && (conflictInfo.area_changed || conflictInfo.day_changed)" 
             x-cloak
             class="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20">
          <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-orange-600 dark:text-orange-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1">
              <h4 class="font-semibold text-orange-800 dark:text-orange-200">Peringatan Perubahan</h4>
              <p class="mt-1 text-sm text-orange-700 dark:text-orange-300">
                Jadwal ini memiliki <span x-text="conflictInfo?.assignment_count"></span> penugasan collector aktif.
                <template x-if="conflictInfo?.has_kecamatan_conflict">
                  <span class="font-semibold text-red-600 dark:text-red-400"> Perubahan ini akan menyebabkan konflik kecamatan!</span>
                </template>
              </p>
              
              {{-- Show kecamatan conflicts if any --}}
              <template x-if="conflictInfo?.has_kecamatan_conflict">
                <div class="mt-3 p-3 bg-red-100 dark:bg-red-900/30 rounded border border-red-200 dark:border-red-700">
                  <p class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Konflik kecamatan terdeteksi:</p>
                  <template x-for="conflict in conflictInfo?.kecamatan_conflicts" :key="conflict.collector_name">
                    <p class="text-sm text-red-700 dark:text-red-300">
                      Collector <span x-text="conflict.collector_name" class="font-semibold"></span> 
                      sudah ditugaskan di kecamatan <span x-text="conflict.conflicting_kecamatan" class="font-semibold"></span> 
                      (kelurahan <span x-text="conflict.conflicting_kelurahan"></span>) 
                      pada hari <span x-text="conflict.day" class="font-semibold"></span>.
                    </p>
                  </template>
                </div>
              </template>

              {{-- Action options when no hard conflict --}}
              <template x-if="!conflictInfo?.has_kecamatan_conflict">
                <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-orange-200 dark:border-orange-700">
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Pilih tindakan saat menyimpan:</p>
                  <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                      <input type="radio" name="conflict_action" value="force" class="mt-1" x-model="conflictAction">
                      <div>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Tetap ubah jadwal (pertahankan penugasan)</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Jadwal akan diubah, penugasan collector tetap aktif</p>
                      </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                      <input type="radio" name="conflict_action" value="end" class="mt-1" x-model="conflictAction">
                      <div>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Ubah jadwal dan akhiri semua penugasan</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Jadwal akan diubah dan semua penugasan collector aktif akan diakhiri</p>
                      </div>
                    </label>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <x-form.select
              name="kecamatan_id"
              label="Kecamatan"
              :options="$kecamatans->map(fn($k) => ['value' => $k->id, 'label' => $k->name])->toArray()"
              placeholder="Pilih Kecamatan"
              :value="old('kecamatan_id', $selectedKecamatanId)"
              x-model="kecamatanId"
              @change="loadKelurahans(); checkConflict();"
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
              @change="checkConflict()"
              :disabled="!kecamatanId || loading"
            >
              <option value="">Pilih Kelurahan</option>
              <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
                <option :value="kelurahan.id" x-text="kelurahan.name" :selected="kelurahan.id == kelurahanId"></option>
              </template>
            </select>
            <p class="mt-1 text-xs text-gray-500" x-show="!kecamatanId">Pilih kecamatan terlebih dahulu</p>
            <p class="mt-1 text-xs text-gray-500" x-show="loading">Memuat kelurahan...</p>
            @error('area_id')
              <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="day_of_week" class="mb-1.5 block text-sm font-medium text-black dark:text-white">
              Hari <span class="text-red-500">*</span>
            </label>
            <select
              id="day_of_week"
              name="day_of_week"
              required
              class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 pr-10 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
              x-model="dayOfWeek"
              @change="checkConflict()"
            >
              @foreach($dayOptions as $option)
                <option value="{{ $option['value'] }}" {{ old('day_of_week', $schedule->day_of_week) == $option['value'] ? 'selected' : '' }}>
                  {{ $option['label'] }}
                </option>
              @endforeach
            </select>
            @error('day_of_week')
              <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <x-form.input
              type="time"
              name="pickup_time"
              label="Waktu Pengambilan"
              :value="old('pickup_time', \Carbon\Carbon::parse($schedule->pickup_time)->format('H:i'))"
              required
            />
            <p class="mt-1 text-xs text-gray-500">Format: HH:MM (contoh: 07:00)</p>
          </div>
        </div>

        <div class="flex items-center gap-3 pt-4">
          <x-button type="submit" variant="primary" size="md" 
                    x-bind:disabled="conflictInfo?.has_kecamatan_conflict"
                    x-bind:class="{'opacity-50 cursor-not-allowed': conflictInfo?.has_kecamatan_conflict}">
            Perbarui Jadwal
          </x-button>
          <x-button as="a" href="{{ route('admin.schedules.index') }}" variant="secondary" size="md">Batal</x-button>
          <span x-show="conflictInfo?.has_kecamatan_conflict" class="text-sm text-red-600 dark:text-red-400">
            Tidak dapat menyimpan karena ada konflik kecamatan
          </span>
        </div>
      </form>
    </div>
  </div>
</div>

@push('body:end')
<script>
  function scheduleForm() {
    return {
      kecamatanId: '{{ old('kecamatan_id', $selectedKecamatanId ?? '') }}',
      kelurahanId: '{{ old('area_id', $schedule->area_id) }}',
      dayOfWeek: '{{ old('day_of_week', $schedule->day_of_week) }}',
      kelurahans: @json($kelurahans ?? []),
      loading: false,
      conflictInfo: null,
      conflictAction: 'force',
      forceUpdate: '0',
      endAssignments: '0',
      originalAreaId: '{{ $schedule->area_id }}',
      originalDayOfWeek: '{{ $schedule->day_of_week }}',

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

      async checkConflict() {
        // Only check if area or day has changed
        if (this.kelurahanId == this.originalAreaId && this.dayOfWeek == this.originalDayOfWeek) {
          this.conflictInfo = null;
          return;
        }

        try {
          const params = new URLSearchParams({
            area_id: this.kelurahanId,
            day_of_week: this.dayOfWeek
          });
          
          const response = await fetch(`{{ route('admin.schedules.check-conflict', $schedule) }}?${params}`);
          this.conflictInfo = await response.json();
          
          // Reset conflict action if there's a hard conflict
          if (this.conflictInfo?.has_kecamatan_conflict) {
            this.conflictAction = '';
          } else if (!this.conflictAction) {
            this.conflictAction = 'force';
          }
        } catch (error) {
          console.error('Error checking conflict:', error);
          this.conflictInfo = null;
        }
      },

      handleSubmit(event) {
        // If there's a kecamatan conflict, prevent submission
        if (this.conflictInfo?.has_kecamatan_conflict) {
          event.preventDefault();
          alert('Tidak dapat menyimpan karena ada konflik kecamatan. Silakan pilih area atau hari yang berbeda.');
          return false;
        }

        // If there are active assignments and changes were made
        if (this.conflictInfo?.has_active_assignments && (this.conflictInfo?.area_changed || this.conflictInfo?.day_changed)) {
          if (this.conflictAction === 'force') {
            this.forceUpdate = '1';
            this.endAssignments = '0';
          } else if (this.conflictAction === 'end') {
            this.forceUpdate = '1';
            this.endAssignments = '1';
          }
        }

        return true;
      },

      init() {
        // Kelurahans already loaded from server on edit page
        // No need to fetch again unless kecamatan changes
      }
    }
  }
</script>
@endpush
@endsection
