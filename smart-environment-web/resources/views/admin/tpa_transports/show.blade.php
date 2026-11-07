@extends('layouts.master')

@section('title', 'Detail Angkut TPA')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <!-- Header -->
    <div class="mb-6 flex items-start justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Detail Angkut TPA #{{ $tpaTransport->id }}</h1>
        <div class="flex items-center gap-3 flex-wrap">
          <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Terkirim
          </span>
          <span class="text-sm text-gray-500">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ $tpaTransport->transported_at->translatedFormat('d F Y, H:i') }} WIB
          </span>
        </div>
      </div>
      <div class="flex gap-2">
        <x-button as="a" href="{{ route('admin.tpa-transports.index') }}" variant="secondary" size="sm">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Kembali
        </x-button>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Main Info (2/3) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Informasi Pengangkutan -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Informasi Pengangkutan</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Tanggal & Waktu -->
              <div>
                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-3">Tanggal & Waktu</label>
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="font-semibold text-gray-900 dark:text-white text-lg">{{ $tpaTransport->transported_at->format('d M Y') }}</p>
                    <p class="text-sm text-gray-500">{{ $tpaTransport->transported_at->format('H:i') }} WIB</p>
                  </div>
                </div>
              </div>

              <!-- Status -->
              <div>
                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-3">Status</label>
                <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                  <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="font-semibold text-green-900 dark:text-green-200">Selesai Diangkut</p>
                  </div>
                </div>
              </div>

              <!-- Dibuat Pada -->
              <div>
                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-3">Dibuat Pada</label>
                <p class="text-gray-900 dark:text-white">{{ $tpaTransport->created_at->translatedFormat('d F Y, H:i') }}</p>
              </div>

              <!-- Terakhir Diupdate -->
              <div>
                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-3">Terakhir Diupdate</label>
                <p class="text-gray-900 dark:text-white">{{ $tpaTransport->updated_at->translatedFormat('d F Y, H:i') }}</p>
              </div>
            </div>
          </div>

          <!-- Catatan Section -->
          <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Catatan</h2>
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
              @if($tpaTransport->notes)
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ $tpaTransport->notes }}</p>
              @else
                <p class="text-gray-400 dark:text-gray-500 italic">Tidak ada catatan</p>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Sidebar (1/3) -->
      <div class="lg:col-span-1 space-y-6">
        <!-- TPS Asal Card -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] p-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">TPS Asal</h3>
          @if($tpaTransport->garbageDump)
            <div class="flex items-start gap-3 mb-4">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white text-base">{{ $tpaTransport->garbageDump->name }}</p>
                @if($tpaTransport->garbageDump->address)
                  <p class="text-sm text-gray-500 mt-1">{{ $tpaTransport->garbageDump->address }}</p>
                @endif
              </div>
            </div>
            @if($tpaTransport->garbageDump->latitude && $tpaTransport->garbageDump->longitude)
              <a href="https://www.google.com/maps?q={{ $tpaTransport->garbageDump->latitude }},{{ $tpaTransport->garbageDump->longitude }}" 
                 target="_blank"
                 class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Lihat di Peta
              </a>
            @endif
          @else
            <div class="text-center py-8">
              <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
              <p class="text-sm text-gray-500 italic">Data TPS tidak tersedia</p>
            </div>
          @endif
        </div>

        <!-- Kolektor Card -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] p-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kolektor</h3>
          @if($tpaTransport->collector)
            <div class="flex items-start gap-3 mb-4">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-lg">{{ substr($tpaTransport->collector->name, 0, 1) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white text-base truncate">{{ $tpaTransport->collector->name }}</p>
                <p class="text-sm text-gray-500 truncate">{{ $tpaTransport->collector->email }}</p>
              </div>
            </div>
            @if($tpaTransport->collector->phone)
              <a href="tel:{{ $tpaTransport->collector->phone }}" 
                 class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                Hubungi Kolektor
              </a>
            @endif
          @else
            <div class="text-center py-8">
              <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              <p class="text-sm text-gray-500 italic">Data kolektor tidak tersedia</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Actions Footer -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] mt-6">
      <div class="p-6">
        <div class="flex justify-between items-center gap-4">
          <div class="flex gap-3">
            <x-button as="a" href="{{ route('admin.tpa-transports.index') }}" variant="secondary" size="md">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Kembali ke Daftar
            </x-button>
          </div>
          
          <div class="flex gap-3">
            <x-button as="a" href="{{ route('admin.tpa-transports.edit', $tpaTransport->id) }}" variant="primary" size="md">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Edit Data
            </x-button>
            <form action="{{ route('admin.tpa-transports.destroy', $tpaTransport->id) }}" method="POST" 
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')" class="inline-block">
              @csrf
              @method('DELETE')
              <x-button type="submit" variant="danger" size="md">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Hapus Data
              </x-button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
