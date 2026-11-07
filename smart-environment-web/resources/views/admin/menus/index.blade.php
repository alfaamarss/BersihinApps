@extends('layouts.master')

@section('title','Menus')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Menus Management</h2>
          <p class="text-sm text-gray-500 mt-1">Struktur navigasi & grouping.</p>
        </div>
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('manage-menu.create') }}" variant="primary">Add Menu</x-button>
        </div>
      </div>

      {{-- Flash handled globally --}}

      <x-table :headers="[
        ['label'=>'Name','class'=>'min-w-[200px]'],
        ['label'=>'URL','class'=>'min-w-[200px]'],
        ['label'=>'Parent','class'=>'min-w-[120px]'],
        ['label'=>'Type','class'=>'min-w-[120px]'],
        ['label'=>'Actions']
      ]" empty="No menus found.">
        @foreach($menus as $menu)
          <x-table.row>
            <x-table.cell>{{ $menu->nama_menu }}</x-table.cell>
            <x-table.cell muted>{{ $menu->url }}</x-table.cell>
            <x-table.cell muted>{{ $menu->parent?->nama_menu ?? '-' }}</x-table.cell>
            <x-table.cell muted class="uppercase">{{ $menu->tipe_menu }}</x-table.cell>
            <x-table.cell>
              <div class="flex items-center gap-2">
                <x-button as="a" href="{{ route('manage-menu.edit', $menu->id) }}" variant="secondary" size="sm" class="dark:border-gray-600 dark:hover:border-gray-500">Edit</x-button>
                <form action="{{ route('manage-menu.destroy', $menu->id) }}" method="POST" onsubmit="return confirm('Delete this menu?')">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm" class="dark:bg-red-600 dark:hover:bg-red-500">Delete</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>
    </div>
  </div>
</div>
@endsection
