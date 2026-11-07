@extends('layouts.master')

@section('title','Penugasan Collector')

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
          <h2 class="text-2xl font-bold text-black dark:text-white">Penugasan Collector</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola penugasan collector ke area berdasarkan jadwal pengangkutan.</p>
        </div>
        <x-button as="a" href="{{ route('admin.collector-assignments.create') }}" variant="primary" size="md">Tugaskan Collector</x-button>
      </div>

      <form method="GET" class="mb-6 flex flex-wrap items-end gap-3" x-data="filterForm()">
        <div class="w-full md:w-[200px]">
          <x-form.select
            name="user_id"
            label="Collector"
            :options="$collectors->map(fn($c) => ['value' => $c->id, 'label' => $c->name])->toArray()"
            placeholder="Semua Collector"
            :value="request('user_id')"
          />
        </div>
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
        <div class="w-full md:w-[150px]">
          <x-form.select name="day_of_week" label="Hari" :options="$dayOptions" placeholder="Semua Hari" :value="request('day_of_week')" />
        </div>
        <div class="w-full md:w-[150px]">
          <x-form.select name="status" label="Status" :options="$statusOptions" placeholder="Semua Status" :value="request('status')" />
        </div>
        <div class="flex gap-2">
          <x-button type="submit" variant="secondary">Terapkan</x-button>
          <x-button as="a" href="{{ route('admin.collector-assignments.index') }}" variant="link">Reset</x-button>
        </div>
      </form>

      <x-table :headers="[
          ['label' => 'Collector','class'=>'min-w-[180px]'],
          ['label' => 'Area','class'=>'min-w-[200px]'],
          ['label' => 'Jadwal','class'=>'min-w-[180px]'],
          ['label' => 'Periode Tugas','class'=>'min-w-[200px]'],
          ['label' => 'Status','class'=>'min-w-[100px]'],
          ['label' => 'Actions','class'=>'w-[140px] text-right']
      ]" empty="Belum ada penugasan collector.">
        @foreach($assignments as $assignment)
          @php
            $today = now()->startOfDay();
            $startDate = \Carbon\Carbon::parse($assignment->start_date)->startOfDay();
            $endDate = $assignment->end_date ? \Carbon\Carbon::parse($assignment->end_date)->startOfDay() : null;
            $isActive = $startDate <= $today && (is_null($endDate) || $endDate >= $today);
          @endphp
          <x-table.row>
            <x-table.cell class="align-top">
              <span class="block text-base font-semibold text-gray-900 dark:text-white">{{ $assignment->user->name ?? '—' }}</span>
              <span class="mt-1 block text-xs text-gray-400">{{ $assignment->user->email ?? '' }}</span>
            </x-table.cell>
            <x-table.cell class="align-top">
              @if($assignment->schedule && $assignment->schedule->area)
                <span class="block text-base font-semibold text-gray-900 dark:text-white">{{ $assignment->schedule->area->name }}</span>
                @if($assignment->rw)
                  <span class="mt-1 block text-xs font-medium text-blue-600 dark:text-blue-400">{{ $assignment->rw->name }}</span>
                @endif
                @if($assignment->schedule->area->parent)
                  <span class="mt-1 block text-xs text-gray-400">KECAMATAN {{ $assignment->schedule->area->parent->name }}</span>
                @endif
              @else
                <span class="text-gray-400">—</span>
              @endif
            </x-table.cell>
            <x-table.cell muted class="align-top">
              @if($assignment->schedule)
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                  {{ $dayLabels[$assignment->schedule->day_of_week] ?? $assignment->schedule->day_of_week }} • {{ \Carbon\Carbon::parse($assignment->schedule->pickup_time)->format('H:i') }}
                </span>
              @else
                <span class="text-xs text-gray-400">—</span>
              @endif
            </x-table.cell>
            <x-table.cell muted class="align-top">
              <div class="text-sm">
                <div class="font-medium text-gray-900 dark:text-white">{{ $assignment->start_date->format('d M Y') }}</div>
                @if($assignment->end_date)
                  <div class="text-gray-500">s/d {{ $assignment->end_date->format('d M Y') }}</div>
                @else
                  <div class="text-gray-500">Tidak terbatas</div>
                @endif
              </div>
            </x-table.cell>
            <x-table.cell muted class="align-top">
              @if($isActive)
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                  Aktif
                </span>
              @else
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                  Nonaktif
                </span>
              @endif
            </x-table.cell>
            <x-table.cell class="text-right align-top" muted>
              <div class="flex justify-end gap-2">
                <x-button as="a" href="{{ route('admin.collector-assignments.edit', $assignment) }}" variant="secondary" size="sm">Edit</x-button>
                <form action="{{ route('admin.collector-assignments.destroy', $assignment) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <x-button type="button" variant="danger" size="sm" class="delete-button">Hapus</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $assignments->links() }}</div>
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
          const response = await fetch(`{{ route('admin.collector-assignments.kelurahans') }}?kecamatan_id=${this.kecamatanId}`);
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

  // SweetAlert2 for delete confirmation
  document.addEventListener('DOMContentLoaded', function() {
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-button');
    
    deleteButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        
        const form = this.closest('.delete-form');
        
        Swal.fire({
          title: 'Hapus Penugasan?',
          text: 'Penugasan collector ini akan dihapus. Tindakan ini tidak dapat dibatalkan!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#EF4444',
          cancelButtonColor: '#6B7280',
          confirmButtonText: 'Ya, Hapus!',
          cancelButtonText: 'Batal',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });

    // Show success message

    // Show error message

    // Show warning message
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
