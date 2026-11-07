@extends('layouts.master')

@section('title', 'Riwayat Setor Kolektor')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Riwayat Setor Kolektor</h2>
          <p class="text-sm text-gray-500 mt-1">Daftar riwayat penyetoran sampah oleh kolektor ke TPS.</p>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="mb-4">
        <form method="GET" action="{{ route('admin.tps-deposits.index') }}" class="flex flex-wrap gap-3">
          <div class="min-w-[250px]">
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Cari nama kolektor..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <div class="min-w-[200px]">
            <select name="garbage_dump_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
              <option value="">Semua TPS</option>
              @foreach($garbageDumps as $tps)
                <option value="{{ $tps->id }}" {{ request('garbage_dump_id') == $tps->id ? 'selected' : '' }}>
                  {{ $tps->name }}
                </option>
              @endforeach
            </select>
          </div>
          <x-button type="submit" variant="secondary" size="md">Filter</x-button>
          @if(request()->hasAny(['search', 'garbage_dump_id']))
            <x-button as="a" href="{{ route('admin.tps-deposits.index') }}" variant="secondary" size="md">Reset</x-button>
          @endif
        </form>
      </div>

      <!-- Table -->
      <x-table :headers="[
          ['label' => 'Waktu', 'class' => 'min-w-[150px]'],
          ['label' => 'Kolektor', 'class' => 'min-w-[200px]'],
          ['label' => 'TPS Tujuan', 'class' => 'min-w-[200px]'],
          ['label' => 'Lokasi', 'class' => 'min-w-[150px]'],
          ['label' => 'Catatan', 'class' => 'min-w-[200px]'],
          ['label' => 'Aksi', 'class' => 'min-w-[100px]'],
      ]" empty="Tidak ada data riwayat setor.">
        @foreach($deposits as $deposit)
          <x-table.row>
            <x-table.cell>
              <div class="font-medium text-gray-900 dark:text-white">
                {{ $deposit->deposited_at ? $deposit->deposited_at->format('d M Y H:i') : '-' }}
              </div>
            </x-table.cell>
            <x-table.cell>
              <div class="font-medium text-gray-900 dark:text-white">
                {{ $deposit->collector->name ?? 'Unknown' }}
              </div>
              <div class="text-xs text-gray-500">
                {{ $deposit->collector->email ?? '' }}
              </div>
            </x-table.cell>
            <x-table.cell>
              <div class="font-medium text-gray-900 dark:text-white">
                {{ $deposit->garbageDump->name ?? 'Unknown' }}
              </div>
              <div class="text-xs text-gray-500">
                {{ $deposit->garbageDump->address ?? '' }}
              </div>
            </x-table.cell>
            <x-table.cell>
              @if($deposit->latitude && $deposit->longitude)
                <a href="https://www.google.com/maps/search/?api=1&query={{ $deposit->latitude }},{{ $deposit->longitude }}" 
                   target="_blank" 
                   class="text-blue-600 hover:underline flex items-center gap-1">
                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                   Lihat Peta
                </a>
              @else
                <span class="text-gray-400">-</span>
              @endif
            </x-table.cell>
            <x-table.cell>
              <span class="text-gray-600 dark:text-gray-400">{{ $deposit->notes ?? '-' }}</span>
            </x-table.cell>
            <x-table.cell>
              <x-button as="a" href="{{ route('admin.tps-deposits.show', $deposit->id) }}" variant="secondary" size="sm">
                Lihat
              </x-button>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">
        {{ $deposits->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
