@extends('layouts.master')

@section('title', 'Daftar TPS')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Daftar TPS</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola data Tempat Pembuangan Sementara (TPS).</p>
        </div>
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.garbage-dumps.create') }}" variant="primary" size="md">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah TPS
          </x-button>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="mb-4" x-data="filterForm()">
        <form method="GET" action="{{ route('admin.garbage-dumps.index') }}" class="flex flex-wrap gap-3">
          <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama atau alamat TPS..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <div class="min-w-[150px]">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
              <option value="">Semua Status</option>
              <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
              <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
          </div>
          <div class="min-w-[150px]">
            <select name="kecamatan_id" x-model="kecamatanId" @change="loadKelurahans"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
              <option value="">Semua Kecamatan</option>
              @foreach($kecamatans ?? [] as $kecamatan)
                <option value="{{ $kecamatan->id }}" {{ request('kecamatan_id') == $kecamatan->id ? 'selected' : '' }}>
                  {{ $kecamatan->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="min-w-[150px]">
            <select name="kelurahan_id" x-model="kelurahanId"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                    :disabled="!kecamatanId">
              <option value="">Semua Kelurahan</option>
              <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
                <option :value="kelurahan.id" x-text="kelurahan.name"></option>
              </template>
            </select>
          </div>
          <x-button type="submit" variant="secondary" size="md">Filter</x-button>
          @if(request()->hasAny(['search', 'status', 'kecamatan_id', 'kelurahan_id']))
            <x-button as="a" href="{{ route('admin.garbage-dumps.index') }}" variant="secondary" size="md">Reset</x-button>
          @endif
        </form>
      </div>

      <!-- Flash Messages -->

      <!-- Table -->
      <x-table :headers="[
          ['label' => 'Nama TPS', 'class' => 'min-w-[180px]'],
          ['label' => 'Lokasi', 'class' => 'min-w-[200px]'],
          ['label' => 'Alamat', 'class' => 'min-w-[200px]'],
          ['label' => 'Kapasitas', 'class' => 'min-w-[140px]'],
          ['label' => 'Koordinat', 'class' => 'min-w-[140px]'],
          ['label' => 'Status', 'class' => 'min-w-[100px]'],
          ['label' => 'Actions', 'class' => 'min-w-[180px]']
      ]" empty="Tidak ada data TPS.">
        @foreach($garbageDumps as $tps)
          <x-table.row>
            <x-table.cell>
              <div class="font-medium text-gray-900 dark:text-white">{{ $tps->name }}</div>
              <div class="text-xs text-gray-500 mt-1">
                {{ $tps->deposits_count ?? 0 }} kali setor
              </div>
            </x-table.cell>
            <x-table.cell muted>
              <div class="text-sm">
                @if($tps->kecamatan)
                  <span class="text-gray-900 dark:text-white">{{ $tps->kecamatan->name }}</span>
                @endif
                @if($tps->kelurahan)
                  <br><span class="text-gray-500">{{ $tps->kelurahan->name }}</span>
                @endif
                @if($tps->rw)
                  <br><span class="text-blue-600 dark:text-blue-400">{{ $tps->rw->name }}</span>
                @endif
              </div>
            </x-table.cell>
            <x-table.cell muted>
              <div class="max-w-[200px] truncate" title="{{ $tps->address }}">
                {{ $tps->address ?? '-' }}
              </div>
            </x-table.cell>
            <x-table.cell muted>
              <div class="text-sm">
                @if($tps->capacity_volume)
                  <div>📦 {{ number_format($tps->capacity_volume, 1) }} m³</div>
                @endif
                @if($tps->capacity_weight)
                  <div>⚖️ {{ number_format($tps->capacity_weight, 0) }} kg</div>
                @endif
                @if(!$tps->capacity_volume && !$tps->capacity_weight)
                  <span class="text-gray-400">-</span>
                @endif
              </div>
            </x-table.cell>
            <x-table.cell muted>
              @if($tps->latitude && $tps->longitude)
                <a href="https://www.google.com/maps?q={{ $tps->latitude }},{{ $tps->longitude }}" 
                   target="_blank"
                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs">
                  📍 {{ number_format($tps->latitude, 5) }}, {{ number_format($tps->longitude, 5) }}
                </a>
              @else
                <span class="text-gray-400">-</span>
              @endif
            </x-table.cell>
            <x-table.cell>
              @if($tps->status === 'active')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                  Aktif
                </span>
              @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                  Nonaktif
                </span>
              @endif
            </x-table.cell>
            <x-table.cell>
              <div class="flex flex-wrap items-center gap-2">
                <x-button as="a" href="{{ route('admin.garbage-dumps.show', $tps->id) }}" variant="info" size="sm">
                  Detail
                </x-button>
                <x-button as="a" href="{{ route('admin.garbage-dumps.edit', $tps->id) }}" variant="secondary" size="sm">
                  Edit
                </x-button>
                <form action="{{ route('admin.garbage-dumps.toggle-status', $tps->id) }}" method="POST" class="inline toggle-status-form" data-status="{{ $tps->status }}">
                  @csrf
                  <x-button type="submit" variant="{{ $tps->status === 'active' ? 'warning' : 'success' }}" size="sm">
                    {{ $tps->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                  </x-button>
                </form>
                <form action="{{ route('admin.garbage-dumps.destroy', $tps->id) }}" method="POST" class="inline delete-form">
                  @csrf
                  @method('DELETE')
                  <!-- <x-button type="submit" variant="danger" size="sm">
                    Hapus
                  </x-button> -->
                </form>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $garbageDumps->links() }}</div>
    </div>
  </div>
</div>
@endsection

@push('body:end')
<script>
  // SweetAlert2 for toggle status
  document.querySelectorAll('.toggle-status-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const isActive = form.dataset.status === 'active';
      
      Swal.fire({
        title: isActive ? 'Nonaktifkan TPS?' : 'Aktifkan TPS?',
        text: isActive ? 'TPS akan dinonaktifkan dan tidak akan muncul dalam daftar aktif.' : 'TPS akan diaktifkan kembali.',
        icon: isActive ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: isActive ? '#d97706' : '#16a34a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: isActive ? 'Ya, Nonaktifkan!' : 'Ya, Aktifkan!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  // SweetAlert2 for delete
  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Hapus TPS?',
        text: 'Data TPS akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  function filterForm() {
    return {
      kecamatanId: '{{ request('kecamatan_id') }}',
      kelurahanId: '{{ request('kelurahan_id') }}',
      kelurahans: [],

      async init() {
        if (this.kecamatanId) {
          await this.loadKelurahans();
        }
      },

      async loadKelurahans() {
        this.kelurahans = [];
        
        if (!this.kecamatanId) {
          this.kelurahanId = '';
          return;
        }

        try {
          const response = await fetch(`{{ route('admin.garbage-dumps.kelurahans') }}?kecamatan_id=${this.kecamatanId}`);
          const data = await response.json();
          this.kelurahans = data;
          
          if (this.kelurahanId && !this.kelurahans.find(k => k.id == this.kelurahanId)) {
            this.kelurahanId = '';
          }
        } catch (error) {
          console.error('Error loading kelurahans:', error);
          this.kelurahans = [];
        }
      }
    }
  }
</script>
@endpush
