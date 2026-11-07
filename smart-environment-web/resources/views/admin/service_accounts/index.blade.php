@extends('layouts.master')

@section('title','Akun Layanan')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Akun Layanan</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola data akun layanan pelanggan.</p>
        </div>
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.service-accounts.create') }}" variant="primary" size="md">Tambah Akun</x-button>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="mb-4" x-data="filterForm()">
        <form method="GET" action="{{ route('admin.service-accounts.index') }}" class="flex flex-wrap gap-3">
          <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama atau alamat..."
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
            <select name="area_id" x-model="areaId"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                    :disabled="!kecamatanId">
              <option value="">Semua Kelurahan</option>
              <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
                <option :value="kelurahan.id" x-text="kelurahan.name"></option>
              </template>
            </select>
          </div>
          <x-button type="submit" variant="secondary" size="md">Filter</x-button>
          @if(request()->hasAny(['search', 'status', 'kecamatan_id', 'area_id']))
            <x-button as="a" href="{{ route('admin.service-accounts.index') }}" variant="secondary" size="md">Reset</x-button>
          @endif
        </form>
      </div>

      <x-table :headers="[
          ['label' => 'Nama','class'=>'min-w-[160px]'],
          ['label' => 'User','class'=>'min-w-[140px]'],
          ['label' => 'Kelurahan','class'=>'min-w-[140px]'],
          ['label' => 'Alamat','class'=>'min-w-[180px]'],
          ['label' => 'Kontak','class'=>'min-w-[120px]'],
          ['label' => 'Status','class'=>'min-w-[100px]'],
          ['label' => 'Actions']
      ]" empty="Tidak ada data akun layanan.">
        @foreach($serviceAccounts as $account)
          <x-table.row>
            <x-table.cell>
              <div class="font-medium">{{ $account->name }}</div>
              @if($account->note)
                <div class="text-xs text-gray-500 mt-1">{{ Str::limit($account->note, 30) }}</div>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              {{ $account->user?->name ?? '-' }}
              @if($account->user)
                <div class="text-xs text-gray-500">{{ $account->user->email }}</div>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              {{ $account->area?->name ?? '-' }}
              @if($account->rw)
                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                  {{ $account->rw->name }}
                </div>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              <div class="max-w-[200px] truncate" title="{{ $account->address }}">
                {{ $account->address ?? '-' }}
              </div>
              @if($account->latitude && $account->longitude)
                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                  📍 {{ number_format($account->latitude, 6) }}, {{ number_format($account->longitude, 6) }}
                </div>
              @endif
            </x-table.cell>
            <x-table.cell muted>{{ $account->contact_phone ?? '-' }}</x-table.cell>
            <x-table.cell>
              @if($account->status === 'active')
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
                <x-button as="a" href="{{ route('admin.service-accounts.show', $account->id) }}" variant="secondary" size="sm" class="dark:border-gray-600 dark:hover:border-gray-500">Detail</x-button>
                <form action="{{ route('admin.service-accounts.destroy', $account->id) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm" class="dark:bg-red-600 dark:hover:bg-red-500">Hapus</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $serviceAccounts->links() }}</div>
    </div>
  </div>
</div>
@endsection

@push('body:end')
<script>
  // SweetAlert2 delete confirmation
  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Hapus Akun Layanan?',
        text: 'Data akun layanan akan dihapus permanen!',
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
      areaId: '{{ request('area_id') }}',
      kelurahans: [],

      async init() {
        // Load kelurahans if kecamatan is already selected
        if (this.kecamatanId) {
          await this.loadKelurahans();
        }
      },

      async loadKelurahans() {
        this.kelurahans = [];
        
        // Reset areaId if kecamatan changes (but not on init)
        if (!this.kecamatanId) {
          this.areaId = '';
          return;
        }

        try {
          const response = await fetch(`{{ route('admin.service-accounts.kelurahans') }}?kecamatan_id=${this.kecamatanId}`);
          const data = await response.json();
          this.kelurahans = data;
          
          // Keep selected kelurahan if it's in the list
          if (this.areaId && !this.kelurahans.find(k => k.id == this.areaId)) {
            this.areaId = '';
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
