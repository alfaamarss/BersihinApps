@extends('layouts.master')

@section('title','Daftar Harga Sampah')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Daftar Harga Sampah</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola harga sampah berdasarkan jenis dan ukurannya.</p>
        </div>
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.wastes.create') }}" variant="primary" size="md">Tambah Data Sampah</x-button>
        </div>
      </div>

      <x-table :headers="[
          ['label' => 'Jenis Sampah','class'=>'min-w-[160px]'],
          ['label' => 'Ukuran','class'=>'min-w-[120px]'],
          ['label' => 'Harga','class'=>'min-w-[120px]'],
          ['label' => 'Berlaku Dari','class'=>'min-w-[140px]'],
          ['label' => 'Actions']
      ]" empty="Belum ada data harga sampah.">
        @foreach($wastePricings as $pricing)
          <x-table.row>
            <x-table.cell>
              <div class="flex flex-col">
                <span class="font-medium capitalize">{{ $pricing->waste->category }}</span>
                @if($pricing->waste->code)
                  <span class="text-xs text-gray-500 dark:text-gray-400">Kode: {{ $pricing->waste->code }}</span>
                @endif
              </div>
            </x-table.cell>
            <x-table.cell muted>{{ $pricing->size->name }}</x-table.cell>
            <x-table.cell muted>Rp {{ number_format($pricing->price, 0, ',', '.') }}</x-table.cell>
            <x-table.cell muted>{{ \Carbon\Carbon::parse($pricing->effective_from)->translatedFormat('d M Y') }}</x-table.cell>
            <x-table.cell>
              <div class="flex flex-wrap items-center gap-2">
                <x-button as="a" href="{{ route('admin.wastes.edit', $pricing->id) }}" variant="secondary" size="sm" class="dark:border-gray-600 dark:hover:border-gray-500">Edit</x-button>
                <form action="{{ route('admin.wastes.destroy', $pricing->id) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm" class="dark:bg-red-600 dark:hover:bg-red-500">Hapus</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $wastePricings->links() }}</div>
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
        title: 'Hapus Harga Sampah?',
        text: 'Data harga sampah akan dihapus permanen!',
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
