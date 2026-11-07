@extends('layouts.master')

@section('title', 'Riwayat Angkut TPA')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Riwayat Angkut TPA</h2>
          <p class="text-sm text-gray-500 mt-1">Log pengangkutan sampah keluar dari TPS menuju TPA.</p>
        </div>
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.tpa-transports.create') }}" variant="primary" size="md">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Data
          </x-button>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="mb-4">
        <form method="GET" action="{{ route('admin.tpa-transports.index') }}" class="flex flex-wrap gap-3">
          <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari TPS atau Collector..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <div class="min-w-[180px]">
            <input type="date" name="date" value="{{ request('date') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <x-button type="submit" variant="secondary" size="md">Filter</x-button>
          @if(request()->hasAny(['search', 'date']))
            <x-button as="a" href="{{ route('admin.tpa-transports.index') }}" variant="secondary" size="md">Reset</x-button>
          @endif
        </form>
      </div>

      <!-- Flash Messages -->

      <!-- Table -->
      <x-table :headers="[
          ['label' => 'Tanggal & Waktu', 'class' => 'min-w-[180px]'],
          ['label' => 'Asal TPS', 'class' => 'min-w-[200px]'],
          ['label' => 'Collector', 'class' => 'min-w-[180px]'],
          ['label' => 'Catatan', 'class' => 'min-w-[200px]'],
          ['label' => 'Aksi', 'class' => 'min-w-[120px]']
      ]" empty="Tidak ada data riwayat angkut.">
        @foreach($transports as $transport)
          <x-table.row>
            <x-table.cell>
              <div class="font-medium text-gray-900 dark:text-white">
                {{ $transport->transported_at->format('d M Y') }}
              </div>
              <div class="text-xs text-gray-500 mt-1">
                🕒 {{ $transport->transported_at->format('H:i') }} WIB
              </div>
            </x-table.cell>
            <x-table.cell>
              <div class="font-medium text-gray-900 dark:text-white">
                {{ $transport->garbageDump->name ?? '-' }}
              </div>
              @if($transport->garbageDump && $transport->garbageDump->address)
                <div class="text-xs text-gray-500 mt-1 max-w-[200px] truncate" title="{{ $transport->garbageDump->address }}">
                  📍 {{ $transport->garbageDump->address }}
                </div>
              @endif
            </x-table.cell>
            <x-table.cell>
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 text-sm font-medium">
                  {{ substr($transport->collector->name ?? 'N', 0, 1) }}
                </div>
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ $transport->collector->name ?? '-' }}
                </div>
              </div>
            </x-table.cell>
            <x-table.cell muted>
              <div class="max-w-[200px]">
                @if($transport->notes)
                  <span class="text-gray-700 dark:text-gray-300" title="{{ $transport->notes }}">
                    {{ Str::limit($transport->notes, 50) }}
                  </span>
                @else
                  <span class="text-gray-400">-</span>
                @endif
              </div>
            </x-table.cell>
            <x-table.cell>
              <div class="flex flex-wrap items-center gap-2">
                <x-button as="a" href="{{ route('admin.tpa-transports.show', $transport->id) }}" variant="secondary" size="sm">
                  Lihat
                </x-button>
                <x-button as="a" href="{{ route('admin.tpa-transports.edit', $transport->id) }}" variant="secondary" size="sm">
                  Edit
                </x-button>
                <form action="{{ route('admin.tpa-transports.destroy', $transport->id) }}" method="POST" class="inline delete-form">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm">
                    Hapus
                  </x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $transports->links() }}</div>
    </div>
  </div>
</div>
@endsection

@push('body:end')
<script>
  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Hapus Riwayat Angkut?',
        text: 'Data riwayat angkut TPA akan dihapus permanen!',
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
</script>
@endpush
