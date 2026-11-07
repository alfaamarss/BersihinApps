@extends('layouts.master')

@section('title','Detail Akun Layanan')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <x-button as="a" href="{{ route('admin.service-accounts.index') }}" variant="secondary" size="sm">
              ← Kembali
            </x-button>
          </div>
          <h2 class="text-2xl font-bold text-black dark:text-white">{{ $serviceAccount->name }}</h2>
          <p class="text-sm text-gray-500 mt-1">Detail informasi akun layanan pelanggan</p>
        </div>
        <!-- <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.service-accounts.edit', $serviceAccount->id) }}" variant="primary" size="md">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Edit Akun
          </x-button>
        </div> -->
      </div>

      <!-- Status Badge -->
      <div class="mb-6">
        @if($serviceAccount->status === 'active')
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
        <!-- User Info -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Informasi Pengguna</h3>
          </div>
          <div class="space-y-3 text-sm">
            <div>
              <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Nama User</span>
              <span class="block mt-1 text-gray-900 dark:text-white font-medium">{{ $serviceAccount->user->name ?? '-' }}</span>
            </div>
            <div>
              <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Email</span>
              <span class="block mt-1 text-gray-900 dark:text-white font-medium">{{ $serviceAccount->user->email ?? '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Kontak -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
              <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kontak</h3>
          </div>
          <div class="space-y-3 text-sm">
            <div>
              <span class="block text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">Nomor Telepon</span>
              <span class="block mt-1 text-gray-900 dark:text-white font-medium">{{ $serviceAccount->contact_phone ?? '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Lokasi -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900 flex items-center justify-center">
              <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Lokasi</h3>
          </div>
          <div class="space-y-2 text-sm">
            <div>
              <span class="text-gray-500 dark:text-gray-400">Kecamatan:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $serviceAccount->area->parent->name ?? '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Kelurahan:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $serviceAccount->area->name ?? '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">RW:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $serviceAccount->rw->name ?? '-' }}</span>
            </div>
            @if($serviceAccount->latitude && $serviceAccount->longitude)
            <div class="pt-2 mt-2 border-t border-gray-200 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">Koordinat:</span>
              <div class="mt-1 font-mono text-xs text-gray-900 dark:text-white">
                {{ number_format($serviceAccount->latitude, 6) }}, {{ number_format($serviceAccount->longitude, 6) }}
              </div>
              <a href="https://www.google.com/maps/search/?api=1&query={{ $serviceAccount->latitude }},{{ $serviceAccount->longitude }}" 
                 target="_blank"
                 class="inline-flex items-center mt-1 text-xs text-blue-600 hover:text-blue-500 dark:text-blue-400">
                Lihat di Maps ↗
              </a>
            </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Full Width Sections -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Alamat Lengkap -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Alamat Lengkap</h3>
          <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
            {{ $serviceAccount->address ?? '-' }}
          </p>
        </div>

        <!-- Catatan -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Catatan</h3>
          <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
            {{ $serviceAccount->note ?? '-' }}
          </p>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
