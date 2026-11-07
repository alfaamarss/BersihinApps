@extends('layouts.master')

@section('title', 'Detail TPS - ' . $garbageDump->name)

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <x-button as="a" href="{{ route('admin.garbage-dumps.index') }}" variant="secondary" size="sm">
              ← Kembali
            </x-button>
          </div>
          <h2 class="text-2xl font-bold text-black dark:text-white">{{ $garbageDump->name }}</h2>
          <p class="text-sm text-gray-500 mt-1">Detail informasi Tempat Pembuangan Sementara</p>
        </div>
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.garbage-dumps.edit', $garbageDump->id) }}" variant="primary" size="md">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Edit TPS
          </x-button>
          <form action="{{ route('admin.garbage-dumps.toggle-status', $garbageDump->id) }}" method="POST" class="inline">
            @csrf
            <x-button type="submit" variant="{{ $garbageDump->status === 'active' ? 'warning' : 'success' }}" size="md">
              {{ $garbageDump->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
            </x-button>
          </form>
        </div>
      </div>

      <!-- Status Badge -->
      <div class="mb-6">
        @if($garbageDump->status === 'active')
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
            <span class="w-2 h-2 mr-2 bg-green-500 rounded-full"></span>
            Aktif
          </span>
        @else
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
            <span class="w-2 h-2 mr-2 bg-gray-500 rounded-full"></span>
            Nonaktif
          </span>
        @endif
      </div>

      <!-- Info Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Lokasi -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Lokasi</h3>
          </div>
          <div class="space-y-2 text-sm">
            <div>
              <span class="text-gray-500 dark:text-gray-400">Kecamatan:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $garbageDump->kecamatan->name ?? '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Kelurahan:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $garbageDump->kelurahan->name ?? '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">RW:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $garbageDump->rw->name ?? '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Kapasitas -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kapasitas</h3>
          </div>
          <div class="space-y-2 text-sm">
            <div>
              <span class="text-gray-500 dark:text-gray-400">Volume:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">
                {{ $garbageDump->capacity_volume ? number_format($garbageDump->capacity_volume, 1) . ' m³' : '-' }}
              </span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Berat:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">
                {{ $garbageDump->capacity_weight ? number_format($garbageDump->capacity_weight, 0) . ' kg' : '-' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Statistik -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
              <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistik</h3>
          </div>
          <div class="space-y-2 text-sm">
            <div>
              <span class="text-gray-500 dark:text-gray-400">Total Setoran:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $garbageDump->deposits->count() }} kali</span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Dibuat:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $garbageDump->created_at->format('d M Y') }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Alamat Lengkap -->
      <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Alamat Lengkap</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <p class="text-gray-700 dark:text-gray-300">{{ $garbageDump->address ?? 'Alamat tidak tersedia' }}</p>
        </div>
      </div>

      <!-- Koordinat & Peta -->
      @if($garbageDump->latitude && $garbageDump->longitude)
      <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Koordinat & Peta</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <div class="flex flex-wrap items-center gap-4 mb-4">
            <div>
              <span class="text-gray-500 dark:text-gray-400">Latitude:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-mono">{{ $garbageDump->latitude }}</span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Longitude:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-mono">{{ $garbageDump->longitude }}</span>
            </div>
            <a href="https://www.google.com/maps?q={{ $garbageDump->latitude }},{{ $garbageDump->longitude }}" 
               target="_blank"
               class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
              </svg>
              Buka di Google Maps
            </a>
          </div>
          <!-- Embedded Map -->
          <div class="rounded-lg overflow-hidden">
            <iframe 
              width="100%" 
              height="300" 
              frameborder="0" 
              style="border:0"
              src="https://www.google.com/maps/embed/v1/place?key={{ config('services.google.maps_api_key', 'AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8') }}&q={{ $garbageDump->latitude }},{{ $garbageDump->longitude }}&zoom=16"
              allowfullscreen>
            </iframe>
          </div>
        </div>
      </div>
      @endif

      <!-- Collector yang Ditugaskan -->
      @if($garbageDump->collectors && $garbageDump->collectors->count() > 0)
      <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Collector yang Ditugaskan</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($garbageDump->collectors as $collector)
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-700 rounded-lg">
              <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ strtoupper(substr($collector->name, 0, 1)) }}</span>
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ $collector->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $collector->email }}</p>
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      <!-- Riwayat Setoran Terbaru -->
      <div>
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Riwayat Setoran Terbaru</h3>
        </div>
        
        @if($recentDeposits->count() > 0)
        <x-table :headers="[
            ['label' => 'Tanggal', 'class' => 'min-w-[150px]'],
            ['label' => 'Collector', 'class' => 'min-w-[150px]'],
            ['label' => 'Volume', 'class' => 'min-w-[100px]'],
            ['label' => 'Berat', 'class' => 'min-w-[100px]'],
            ['label' => 'Catatan', 'class' => 'min-w-[200px]']
        ]" empty="Belum ada riwayat setoran.">
          @foreach($recentDeposits as $deposit)
            <x-table.row>
              <x-table.cell>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ \Carbon\Carbon::parse($deposit->deposited_at)->format('d M Y') }}
                </span>
                <br>
                <span class="text-xs text-gray-500">
                  {{ \Carbon\Carbon::parse($deposit->deposited_at)->format('H:i') }}
                </span>
              </x-table.cell>
              <x-table.cell>
                <span class="text-gray-900 dark:text-white">{{ $deposit->collector->name ?? '-' }}</span>
              </x-table.cell>
              <x-table.cell muted>
                {{ $deposit->volume ? number_format($deposit->volume, 2) . ' m³' : '-' }}
              </x-table.cell>
              <x-table.cell muted>
                {{ $deposit->weight ? number_format($deposit->weight, 2) . ' kg' : '-' }}
              </x-table.cell>
              <x-table.cell muted>
                <div class="max-w-[200px] truncate" title="{{ $deposit->notes }}">
                  {{ $deposit->notes ?? '-' }}
                </div>
              </x-table.cell>
            </x-table.row>
          @endforeach
        </x-table>
        @else
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-8 text-center">
          <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
          </svg>
          <p class="text-gray-500 dark:text-gray-400">Belum ada riwayat setoran untuk TPS ini.</p>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
