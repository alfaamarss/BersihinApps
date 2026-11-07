@extends('layouts.master')

@section('title','Jadwal Pengangkutan')

@section('content')
@php
    $dayLabels = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu',
    ];
@endphp
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Jadwal Pengangkutan Sampah</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola jadwal pengambilan sampah per area dan hari.</p>
        </div>
        <x-button as="a" href="{{ route('admin.schedules.create') }}" variant="primary" size="md">Tambah Jadwal</x-button>
      </div>

      <form method="GET" class="mb-6 flex flex-wrap items-end gap-3" x-data="filterForm()">
        <div class="w-full md:w-[200px]">
          <x-form.select
            name="kecamatan_id"
            label="Kecamatan"
            :options="$kecamatans->map(fn($k) => ['value' => $k->id, 'label' => $k->name])->toArray()"
            placeholder="Semua Kecamatan"
            :value="request('kecamatan_id')"
            x-model="kecamatanId"
            @change="loadKelurahans()"
          />
        </div>
        <div class="w-full md:w-[200px]">
          <label for="filter_area_id" class="mb-1.5 block text-sm font-medium text-black dark:text-white">Kelurahan</label>
          <select
            id="filter_area_id"
            name="area_id"
            class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 pr-10 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            x-model="kelurahanId"
            :disabled="!kecamatanId && kelurahans.length === 0"
          >
            <option value="">Semua Kelurahan</option>
            <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
              <option :value="kelurahan.id" x-text="kelurahan.name"></option>
            </template>
          </select>
        </div>
        <div class="w-full md:w-[180px]">
          <x-form.select name="day_of_week" label="Hari" :options="$dayOptions" placeholder="Semua Hari" :value="request('day_of_week')" />
        </div>
        <div class="flex gap-2">
          <x-button type="submit" variant="secondary">Terapkan</x-button>
          <x-button as="a" href="{{ route('admin.schedules.index') }}" variant="link">Reset</x-button>
        </div>
      </form>

      <x-table :headers="[
          ['label' => 'Area','class'=>'min-w-[200px]'],
          ['label' => 'Hari','class'=>'min-w-[120px]'],
          ['label' => 'Waktu Pengambilan','class'=>'min-w-[150px]'],
          ['label' => 'Penugasan Aktif','class'=>'min-w-[130px] text-center'],
          ['label' => 'Dibuat','class'=>'min-w-[140px]'],
          ['label' => 'Actions','class'=>'w-[140px] text-right']
      ]" empty="Belum ada jadwal.">
        @foreach($schedules as $schedule)
          <x-table.row>
            <x-table.cell class="align-top">
              <span class="block text-base font-semibold text-gray-900 dark:text-white">{{ $schedule->area->name ?? '—' }}</span>
              @if($schedule->area->parent)
                <span class="mt-1 block text-xs text-gray-400">KECAMATAN {{ $schedule->area->parent->name }}</span>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                {{ $dayLabels[$schedule->day_of_week] ?? $schedule->day_of_week }}
              </span>
            </x-table.cell>
            <x-table.cell muted>
              <span class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($schedule->pickup_time)->format('H:i') }}</span>
            </x-table.cell>
            <x-table.cell class="text-center">
              @if($schedule->active_assignments_count > 0)
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300" title="Ada collector yang ditugaskan">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                  {{ $schedule->active_assignments_count }}
                </span>
              @else
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400" title="Belum ada collector ditugaskan">
                  0
                </span>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              {{ optional($schedule->created_at)->translatedFormat('d M Y H:i') ?? '—' }}
            </x-table.cell>
            <x-table.cell class="text-right" muted>
              <div class="flex justify-end gap-2">
                <x-button as="a" href="{{ route('admin.schedules.edit', $schedule) }}" variant="secondary" size="sm">Edit</x-button>
                <form action="{{ route('admin.schedules.destroy', $schedule) }}" method="POST" class="delete-form" 
                      data-area-name="{{ $schedule->area->name ?? 'jadwal ini' }}" 
                      data-day="{{ $dayLabels[$schedule->day_of_week] ?? $schedule->day_of_week }}"
                      data-has-assignments="{{ $schedule->active_assignments_count > 0 ? 'true' : 'false' }}"
                      data-assignment-count="{{ $schedule->active_assignments_count }}">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $schedules->links() }}</div>
    </div>
  </div>
</div>

@push('body:end')
<script>
  function filterForm() {
    return {
      kecamatanId: '{{ request('kecamatan_id', '') }}',
      kelurahanId: '{{ request('area_id', '') }}',
      kelurahans: [],

      async loadKelurahans() {
        if (!this.kecamatanId) {
          this.kelurahans = [];
          this.kelurahanId = '';
          return;
        }

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
        }
      },

      init() {
        // Load kelurahans if kecamatan is already selected (from query params)
        if (this.kecamatanId) {
          this.loadKelurahans();
        }
      }
    }
  }

  // SweetAlert2 for success message

  @if(session('error'))
    Swal.fire({
      icon: 'error',
      title: 'Gagal!',
      text: '{{ session('error') }}',
      customClass: {
        popup: 'dark:bg-gray-800 dark:text-white',
        title: 'dark:text-white',
        htmlContainer: 'dark:text-gray-300'
      }
    });
  @endif

  // SweetAlert2 confirmation for delete
  document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    
    deleteForms.forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const areaName = this.dataset.areaName;
        const day = this.dataset.day;
        const hasAssignments = this.dataset.hasAssignments === 'true';
        const assignmentCount = parseInt(this.dataset.assignmentCount) || 0;

        // If schedule has active assignments, show warning and prevent deletion
        if (hasAssignments) {
          Swal.fire({
            title: 'Tidak Dapat Menghapus',
            html: `Jadwal untuk <strong>${areaName}</strong> (${day}) tidak dapat dihapus karena masih memiliki <strong>${assignmentCount}</strong> penugasan collector aktif.<br><br>Silakan akhiri penugasan terlebih dahulu melalui menu <strong>Penugasan Collector</strong>.`,
            icon: 'error',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Mengerti',
            customClass: {
              popup: 'dark:bg-gray-800 dark:text-white',
              title: 'dark:text-white',
              htmlContainer: 'dark:text-gray-300',
              confirmButton: 'font-semibold'
            }
          });
          return;
        }
        
        Swal.fire({
          title: 'Hapus jadwal ini?',
          html: `Anda akan menghapus jadwal untuk:<br><br><strong>${areaName}</strong><br>Hari: <strong>${day}</strong>`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Ya, Hapus!',
          cancelButtonText: 'Batal',
          reverseButtons: true,
          customClass: {
            popup: 'dark:bg-gray-800 dark:text-white',
            title: 'dark:text-white',
            htmlContainer: 'dark:text-gray-300',
            confirmButton: 'font-semibold',
            cancelButton: 'font-semibold'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            // Show loading
            Swal.fire({
              title: 'Menghapus...',
              html: 'Mohon tunggu',
              allowOutsideClick: false,
              allowEscapeKey: false,
              didOpen: () => {
                Swal.showLoading();
              },
              customClass: {
                popup: 'dark:bg-gray-800 dark:text-white',
                title: 'dark:text-white',
                htmlContainer: 'dark:text-gray-300'
              }
            });
            
            // Submit the form
            this.submit();
          }
        });
      });
    });
  });
</script>
@endpush
@endsection
