@extends('layouts.master')

@section('title','Pengaduan')

@section('content')
@php
    $statusBadges = [
        'open' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        'in_progress' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        'pending_confirmation' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
        'resolved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    ];
    
    $statusLabels = [
        'open' => 'Menunggu',
        'in_progress' => 'Dalam Proses',
        'pending_confirmation' => 'Menunggu Konfirmasi',
        'resolved' => 'Selesai',
        'rejected' => 'Ditolak',
    ];
@endphp
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Manajemen Pengaduan</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola pengaduan dari warga</p>
        </div>
        <!-- <x-button as="a" href="{{ route('admin.complaints.create') }}" variant="primary" size="md">Tambah Pengaduan</x-button> -->
      </div>

      <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="w-full md:w-[260px]">
          <x-form.input name="search" label="Cari Nama User" placeholder="Cari nama warga">{{ request('search') }}</x-form.input>
        </div>
        <div class="w-full md:w-[200px]">
          <x-form.select name="status" label="Status" :options="$statusOptions" placeholder="Semua Status" :value="request('status')" />
        </div>
        <div class="w-full md:w-[200px]">
          <x-form.select name="type" label="Kategori" :options="$typeOptions" placeholder="Semua Kategori" :value="request('type')" />
        </div>
        <div class="flex gap-2">
          <x-button type="submit" variant="secondary">Terapkan</x-button>
          <x-button as="a" href="{{ route('admin.complaints.index') }}" variant="link">Reset</x-button>
        </div>
      </form>

      <x-table :headers="[
          ['label' => 'ID','class'=>'w-[60px]'],
          ['label' => 'Pelapor','class'=>'min-w-[180px]'],
          ['label' => 'Kategori','class'=>'min-w-[120px]'],
          ['label' => 'Status','class'=>'min-w-[130px]'],
          ['label' => 'Petugas','class'=>'min-w-[150px]'],
          ['label' => 'Tanggal','class'=>'min-w-[140px]'],
          ['label' => 'Aksi','class'=>'w-[120px]']
      ]" empty="Belum ada pengaduan.">
        @foreach($complaints as $complaint)
          <x-table.row>
            <x-table.cell class="font-mono text-sm">#{{ $complaint->id }}</x-table.cell>
            <x-table.cell>
              <div class="flex items-center gap-3">
                @if($complaint->photos->count() > 0)
                  <img src="{{ asset('storage/' . $complaint->photos->first()->photo_url) }}" 
                       alt="Photo" 
                       class="w-10 h-10 object-cover rounded-lg flex-shrink-0">
                @else
                  <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                  </div>
                @endif
                <div class="flex-1 min-w-0">
                  <span class="block font-semibold text-gray-900 dark:text-white">{{ $complaint->user->name }}</span>
                  @if($complaint->serviceAccount)
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $complaint->serviceAccount->account_number }}</span>
                  @endif
                </div>
              </div>
            </x-table.cell>
            <x-table.cell muted>
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                {{ ucfirst($complaint->type) }}
              </span>
            </x-table.cell>
            <x-table.cell muted>
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadges[$complaint->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $statusLabels[$complaint->status] ?? ucfirst($complaint->status) }}
              </span>
            </x-table.cell>
            <x-table.cell muted>
              @if($complaint->assignee)
                <span class="text-sm text-gray-900 dark:text-white">{{ $complaint->assignee->name }}</span>
              @else
                <span class="text-sm text-gray-400 dark:text-gray-500">Belum ditugaskan</span>
              @endif
            </x-table.cell>
            <x-table.cell muted>{{ $complaint->created_at->translatedFormat('d M Y H:i') }}</x-table.cell>
            <x-table.cell muted>
              <div class="flex items-center gap-2">
                <x-button as="a" href="{{ route('admin.complaints.show', $complaint) }}" variant="secondary" size="sm" title="Lihat">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </x-button>
                <form action="{{ route('admin.complaints.destroy', $complaint) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $complaints->links() }}</div>
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
        title: 'Hapus Pengaduan?',
        text: 'Data pengaduan akan dihapus permanen!',
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
