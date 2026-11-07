@extends('layouts.master')

@section('title','Area')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Area</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola data area (Kecamatan/Kelurahan).</p>
        </div>
        <!-- <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.areas.create') }}" variant="primary" size="md">Tambah Area</x-button>
        </div> -->
      </div>

      <x-table :headers="[
          ['label' => 'Nama','class'=>'min-w-[160px]'],
          ['label' => 'Level','class'=>'min-w-[120px]'],
          ['label' => 'Parent','class'=>'min-w-[160px]'],
         // ['label' => 'Actions']
      ]" empty="Tidak ada data area.">
        @foreach($areas as $area)
          <x-table.row>
            <x-table.cell>{{ $area->name }}</x-table.cell>
            <x-table.cell muted>{{ $area->level }}</x-table.cell>
            <x-table.cell muted>{{ $area->parent?->name }}</x-table.cell>
            <!-- <x-table.cell>
              <div class="flex flex-wrap items-center gap-2">
                <x-button as="a" href="{{ route('admin.areas.edit', $area->id) }}" variant="secondary" size="sm" class="dark:border-gray-600 dark:hover:border-gray-500">Edit</x-button>
                <form action="{{ route('admin.areas.destroy', $area->id) }}" method="POST" onsubmit="return confirm('Hapus area ini?')">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm" class="dark:bg-red-600 dark:hover:bg-red-500">Hapus</x-button>
                </form>
              </div>
            </x-table.cell> -->
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $areas->links() }}</div>
    </div>
  </div>
</div>
@endsection
