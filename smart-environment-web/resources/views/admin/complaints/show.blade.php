@extends('layouts.master')

@section('title','Detail Pengaduan')

@push('styles')
<style>
  [x-cloak] { display: none !important; }
</style>
@endpush

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
        'pending_confirmation' => 'Menunggu Konfirmasi Warga',
        'resolved' => 'Selesai',
        'rejected' => 'Ditolak',
    ];

    $typeLabels = [
        'sampah_tidak_diangkut' => 'Sampah Tidak Diangkut',
        'sampah_menumpuk' => 'Sampah Menumpuk',
        'jadwal_tidak_sesuai' => 'Jadwal Tidak Sesuai',
        'pelayanan_buruk' => 'Pelayanan Buruk',
        'petugas_tidak_datang' => 'Petugas Tidak Datang',
        'lainnya' => 'Lainnya',
        'illegal_dumping' => 'Pembuangan Sampah Sembarangan',
        'uncollected_waste' => 'Sampah Tidak Diangkut',
        'damaged_facility' => 'Fasilitas Rusak',
        'other' => 'Lainnya',
    ];
@endphp

<!-- Header -->
<div class="mb-6 flex items-start justify-between">
  <div>
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Pengaduan #{{ $complaint->id }}</h1>
    <div class="flex items-center gap-3 flex-wrap">
      <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $statusBadges[$complaint->status] ?? 'bg-gray-100 text-gray-700' }}">
        {{ $statusLabels[$complaint->status] ?? ucfirst($complaint->status) }}
      </span>
      <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
        {{ $typeLabels[$complaint->type] ?? ucfirst(str_replace('_', ' ', $complaint->type)) }}
      </span>
      <span class="text-sm text-gray-500">
        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        {{ $complaint->created_at->translatedFormat('d F Y, H:i') }}
      </span>
    </div>
  </div>
  <div class="flex gap-2">
    <x-button as="a" href="{{ route('admin.complaints.index') }}" variant="secondary" size="sm">
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
      </svg>
      Kembali
    </x-button>
  </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
  <!-- Left Column - Main Info (2/3) -->
  <div class="lg:col-span-2 space-y-6">
    <!-- Informasi Pengaduan Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Informasi Pengaduan</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Pelapor -->
      <div class="md:col-span-2">
        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-3">Pelapor</label>
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
          </div>
          <div>
            <p class="font-semibold text-gray-900 dark:text-white text-lg">{{ $complaint->user->name }}</p>
            <p class="text-sm text-gray-500">{{ $complaint->user->email }}</p>
          </div>
        </div>
      </div>

          <!-- Tipe Pengaduan -->
          <div>
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-3">Kategori</label>
            <div class="px-4 py-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
              <p class="font-semibold text-purple-900 dark:text-purple-200">{{ $typeLabels[$complaint->type] ?? ucfirst(str_replace('_', ' ', $complaint->type)) }}</p>
            </div>
          </div>

          <!-- Status -->
          <div>
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-3">Status</label>
            <div class="px-4 py-3 rounded-lg {{ $statusBadges[$complaint->status] ?? 'bg-gray-100 text-gray-700' }}">
              <p class="font-semibold">{{ $statusLabels[$complaint->status] ?? ucfirst($complaint->status) }}</p>
            </div>
          </div>

          <!-- Timeline -->
          <div class="md:col-span-2">
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-3">Timeline</label>
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
              @if($complaint->histories->count() > 0)
                <div class="relative">
                  @foreach($complaint->histories as $index => $history)
                    @php
                      $isLast = $loop->last;
                      $borderColors = [
                        'blue' => 'border-blue-500',
                        'yellow' => 'border-yellow-500',
                        'purple' => 'border-purple-500',
                        'green' => 'border-green-500',
                        'red' => 'border-red-500',
                        'gray' => 'border-gray-500',
                      ];
                      $iconColors = [
                        'blue' => 'text-blue-500',
                        'yellow' => 'text-yellow-500',
                        'purple' => 'text-purple-500',
                        'green' => 'text-green-500',
                        'red' => 'text-red-500',
                        'gray' => 'text-gray-500',
                      ];
                      $borderColor = $borderColors[$history->status_color] ?? $borderColors['gray'];
                      $iconColor = $iconColors[$history->status_color] ?? $iconColors['gray'];
                    @endphp
                    <div class="flex items-start gap-4 relative">
                      <!-- Vertical Line (behind icon) -->
                      @if(!$isLast)
                        <div class="absolute left-5 top-10 w-0.5 h-full bg-blue-400" style="transform: translateX(-50%);"></div>
                      @endif
                      
                      <!-- Icon -->
                      <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-2 {{ $borderColor }} flex items-center justify-center flex-shrink-0 z-10">
                        @if($history->status === 'open')
                          <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                        @elseif($history->status === 'in_progress')
                          <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                        @elseif($history->status === 'pending_confirmation')
                          <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                        @elseif($history->status === 'resolved')
                          <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                        @elseif($history->status === 'rejected')
                          <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                        @else
                          <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                        @endif
                      </div>
                      
                      <!-- Content -->
                      <div class="flex-1 {{ !$isLast ? 'pb-8' : '' }}">
                        <h4 class="font-semibold text-gray-900 dark:text-white text-base">{{ $history->status_label }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $history->status_description }}</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">{{ $history->created_at->translatedFormat('d M Y H:i') }}</p>
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <!-- Fallback jika belum ada history -->
                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-2 border-blue-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 dark:text-white text-base">Status Menunggu</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Pengaduan dibuat oleh warga</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">{{ $complaint->created_at->translatedFormat('d M Y H:i') }}</p>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Deskripsi Section -->
      <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Deskripsi Pengaduan</h2>
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
          <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ $complaint->description }}</p>
        </div>
      </div>

      <!-- Lokasi Section -->
      @if($complaint->address || $complaint->latitude)
      <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Lokasi Kejadian</h2>
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Alamat</h3>
              <p class="text-gray-700 dark:text-gray-300 mb-3">{{ $complaint->address ?: 'Tidak ada alamat' }}</p>
              
              @if($complaint->latitude && $complaint->longitude)
                <div class="flex items-center gap-4 text-sm">
                  <span class="text-gray-600 dark:text-gray-400">
                    <strong>Koordinat:</strong> {{ number_format($complaint->latitude, 6) }}, {{ number_format($complaint->longitude, 6) }}
                  </span>
                  <a href="https://www.google.com/maps?q={{ $complaint->latitude }},{{ $complaint->longitude }}" 
                     target="_blank"
                     class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    Buka di Google Maps
                  </a>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
      @endif

      <!-- Foto Bukti Section -->
      @if($complaint->photos->count() > 0)
        <div class="p-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Foto Bukti ({{ $complaint->photos->count() }})</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($complaint->photos as $photo)
              <a href="{{ asset('storage/' . $photo->photo_url) }}" target="_blank" class="group relative overflow-hidden rounded-lg">
                <img src="{{ asset('storage/' . $photo->photo_url) }}" 
                     alt="Foto {{ $photo->order + 1 }}" 
                     class="w-full h-32 object-cover rounded-lg shadow-md group-hover:shadow-lg transition-all group-hover:scale-105">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all rounded-lg flex items-center justify-center">
                  <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                  </svg>
                </div>
                <span class="absolute bottom-2 left-2 px-2 py-1 bg-black/60 text-white text-xs font-medium rounded">Foto {{ $photo->order + 1 }}</span>
              </a>
            @endforeach
          </div>
        </div>
      @endif

      <!-- Foto Penyelesaian -->
      @if($complaint->resolution_photo)
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Foto Penyelesaian</h2>
          <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
            <a href="{{ asset('storage/' . $complaint->resolution_photo) }}" target="_blank" class="block max-w-md">
              <img src="{{ asset('storage/' . $complaint->resolution_photo) }}" 
                   alt="Foto Penyelesaian" 
                   class="w-full rounded-lg shadow-md hover:shadow-lg transition-all">
            </a>
          </div>
        </div>
      @endif

      @if($complaint->rejection_reason)
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Alasan Penolakan</h2>
          <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border border-red-200 dark:border-red-700">
            <p class="text-red-700 dark:text-red-300">{{ $complaint->rejection_reason }}</p>
          </div>
        </div>
      @endif

      @if($complaint->resolution_description)
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Deskripsi Penyelesaian</h2>
          <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
            <p class="text-green-700 dark:text-green-300 whitespace-pre-wrap">{{ $complaint->resolution_description }}</p>
          </div>
        </div>
      @endif
    </div>
  </div>

  <!-- Right Column - Sidebar (1/3) -->
  <div class="lg:col-span-1 space-y-6">
    <!-- Update Status Card -->
    @php
      // Untuk kategori sampah_tidak_diangkut, konfirmasi penyelesaian harus dari warga
      $isUserConfirmationType = $complaint->type === 'sampah_tidak_diangkut';
      // Kategori yang tidak memerlukan resolution_description (karena di-handle oleh collector)
      $noResolutionDescriptionTypes = ['sampah_tidak_diangkut', 'sampah_menumpuk'];
      $requiresResolutionDescription = !in_array($complaint->type, $noResolutionDescriptionTypes);
    @endphp
    @if(!in_array($complaint->status, ['resolved', 'rejected']))
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] p-6" x-data="{ showResolutionModal: false, selectedStatus: '{{ $complaint->status }}' }">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Ubah Status</h3>
      
      @if($complaint->status === 'pending_confirmation')
        {{-- Status pending_confirmation: menunggu konfirmasi warga --}}
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <h4 class="font-semibold text-purple-900 dark:text-purple-200">Menunggu Konfirmasi Warga</h4>
              <p class="text-sm text-purple-700 dark:text-purple-300 mt-1">
                Petugas sudah menyelesaikan tugas dan mengupload bukti. Menunggu konfirmasi dari warga melalui aplikasi mobile.
              </p>
            </div>
          </div>
        </div>
      @elseif($isUserConfirmationType && $complaint->status === 'in_progress')
        {{-- Untuk sampah_tidak_diangkut yang sudah in_progress, info konfirmasi dari warga --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <h4 class="font-semibold text-blue-900 dark:text-blue-200">Menunggu Penyelesaian Petugas</h4>
              <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                Untuk kategori "Sampah Tidak Diangkut", setelah petugas mengirim bukti, warga akan mengkonfirmasi penyelesaian melalui aplikasi mobile.
              </p>
            </div>
          </div>
        </div>
      @else
        <form action="{{ route('admin.complaints.update-status', $complaint) }}" method="POST" class="space-y-3" 
              @if($requiresResolutionDescription)
              @submit.prevent="selectedStatus === 'resolved' ? showResolutionModal = true : $el.submit()"
              @endif
        >
          @csrf
          <div class="space-y-2">
            @php
              // Jika status sudah in_progress (sudah assign collector), hanya bisa selesaikan
              // Untuk kategori sampah_tidak_diangkut, opsi resolved tidak ditampilkan karena harus dari warga
              if ($complaint->status === 'in_progress') {
                $statusOptions = $isUserConfirmationType 
                  ? [
                      ['value' => 'in_progress', 'label' => 'Dalam Proses', 'color' => 'yellow'],
                    ]
                  : [
                      ['value' => 'in_progress', 'label' => 'Dalam Proses', 'color' => 'yellow'],
                      ['value' => 'resolved', 'label' => 'Selesai', 'color' => 'green'],
                    ];
              } else {
                $statusOptions = $isUserConfirmationType 
                  ? [
                      ['value' => 'open', 'label' => 'Menunggu', 'color' => 'blue'],
                      ['value' => 'in_progress', 'label' => 'Dalam Proses', 'color' => 'yellow'],
                    ]
                  : [
                      ['value' => 'open', 'label' => 'Menunggu', 'color' => 'blue'],
                      ['value' => 'in_progress', 'label' => 'Dalam Proses', 'color' => 'yellow'],
                      ['value' => 'resolved', 'label' => 'Selesai', 'color' => 'green'],
                    ];
              }
            @endphp
            @foreach($statusOptions as $option)
              <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors {{ $complaint->status === $option['value'] ? 'border-'.$option['color'].'-500 bg-'.$option['color'].'-50 dark:bg-'.$option['color'].'-900/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                <input type="radio" name="status" value="{{ $option['value'] }}" {{ $complaint->status === $option['value'] ? 'checked' : '' }} 
                       @change="selectedStatus = '{{ $option['value'] }}'"
                       class="w-4 h-4 text-{{ $option['color'] }}-600">
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $option['label'] }}</span>
                @if($complaint->status === $option['value'])
                  <svg class="w-4 h-4 ml-auto text-{{ $option['color'] }}-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                @endif
              </label>
            @endforeach
          </div>
          <x-button type="submit" variant="primary" class="w-full">Simpan Status</x-button>
        </form>
        
        @if($isUserConfirmationType)
          <p class="text-xs text-amber-600 dark:text-amber-400 mt-3 flex items-start gap-1">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span>Untuk kategori ini, status "Selesai" hanya dapat dikonfirmasi oleh warga melalui aplikasi mobile.</span>
          </p>
        @endif

        <!-- Modal Deskripsi Penyelesaian untuk kategori non-sampah -->
        @if($requiresResolutionDescription)
        <div x-show="showResolutionModal" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.self="showResolutionModal = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             style="margin: 0;">
          <div @click.away="showResolutionModal = false" 
               x-show="showResolutionModal"
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-200"
               x-transition:leave-start="opacity-100 scale-100"
               x-transition:leave-end="opacity-0 scale-95"
               class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                  <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Selesaikan Pengaduan #{{ $complaint->id }}</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Pengaduan akan ditandai sebagai selesai</p>
                </div>
              </div>
            </div>
            
            <form action="{{ route('admin.complaints.update-status', $complaint) }}" method="POST" x-data="{ description: '' }">
              @csrf
              <input type="hidden" name="status" value="resolved">
              
              <div class="p-6">
                <label for="resolution_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Deskripsi Penyelesaian <span class="text-red-500">*</span>
                </label>
                <textarea 
                  name="resolution_description" 
                  id="resolution_description"
                  x-model="description"
                  rows="4"
                  required
                  class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:focus:border-green-400"
                  placeholder="Jelaskan bagaimana pengaduan ini diselesaikan..."
                ></textarea>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                  <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  Deskripsi ini akan ditampilkan kepada pelapor sebagai penjelasan penyelesaian.
                </p>
              </div>
              
              <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button @click="showResolutionModal = false" type="button" class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  Batal
                </button>
                <button type="submit" 
                        :disabled="!description.trim()"
                        :class="description.trim() ? 'bg-green-600 hover:bg-green-700 cursor-pointer' : 'bg-green-300 dark:bg-green-800 cursor-not-allowed'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white rounded-lg transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  Selesaikan Pengaduan
                </button>
              </div>
            </form>
          </div>
        </div>
        @endif
      @endif
    </div>
    @endif

    <!-- Assign Collector Card -->
    @php
      // Hanya tampilkan form penugasan untuk kategori sampah_menumpuk dan sampah_tidak_diangkut
      $showAssignForm = in_array($complaint->type, ['sampah_menumpuk', 'sampah_tidak_diangkut']);
      // Disable tombol jika sudah ada collector yang ditugaskan
      $collectorAssigned = !is_null($complaint->assignee_id);
    @endphp
    @if(!in_array($complaint->status, ['resolved', 'rejected', 'pending_confirmation']) && $showAssignForm)
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Tugaskan Petugas</h3>
      @if($collectorAssigned)
        {{-- Jika collector sudah ditugaskan, tampilkan info dan disable form --}}
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700 mb-3">
          <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm font-medium">Petugas sudah ditugaskan</span>
          </div>
          <p class="text-sm text-green-600 dark:text-green-400 mt-1">{{ $complaint->assignee->name }}</p>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 italic">
          <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Petugas tidak dapat diubah setelah ditugaskan.
        </p>
      @else
        <form action="{{ route('admin.complaints.assign-collector', $complaint) }}" method="POST" class="space-y-3">
          @csrf
          <div class="space-y-1">
            <label for="assignee_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Collector</label>
            <select name="assignee_id" id="assignee_id" class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
              <option value="">-- Pilih Collector --</option>
              @foreach($collectors as $collector)
                <option value="{{ $collector->id }}">
                  {{ $collector->name }}
                </option>
              @endforeach
            </select>
          </div>
          <x-button type="submit" variant="secondary" class="w-full">
            Tugaskan
          </x-button>
        </form>
      @endif
    </div>
    @endif

    <!-- Pelapor Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Pelapor</h3>
      <div class="flex items-start gap-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center flex-shrink-0">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 dark:text-white text-base truncate">{{ $complaint->user->name }}</p>
          <p class="text-sm text-gray-500 truncate">{{ $complaint->user->email }}</p>
        </div>
      </div>
      @if($complaint->user->phone)
        <a href="tel:{{ $complaint->user->phone }}" 
           class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
          </svg>
          Hubungi Pelapor
        </a>
      @endif
    </div>

    <!-- Kolektor Card - Hanya tampil untuk kategori sampah_menumpuk dan sampah_tidak_diangkut -->
    @if($showAssignForm)
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Petugas yang Ditugaskan</h3>
      @if($complaint->assignee)
        <div class="flex items-start gap-3 mb-4">
          <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-900 dark:text-white text-base truncate">{{ $complaint->assignee->name }}</p>
            <p class="text-sm text-gray-500 truncate">{{ $complaint->assignee->email }}</p>
          </div>
        </div>
        @if($complaint->assignee->phone)
          <a href="tel:{{ $complaint->assignee->phone }}" 
             class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
            </svg>
            Hubungi Petugas
          </a>
        @endif
      @else
        <div class="text-center py-8">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
          </svg>
          <p class="text-sm text-gray-500 italic">Belum ditugaskan</p>
        </div>
      @endif
    </div>
    @endif

    <!-- Service Account Card -->
    @if($complaint->serviceAccount)
      <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Akun Layanan</h3>
        <div class="space-y-3">
          <div>
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-1">Nomor Akun</label>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $complaint->serviceAccount->account_number }}</p>
          </div>
          <div>
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-1">Nama</label>
            <p class="text-gray-900 dark:text-white">{{ $complaint->serviceAccount->name }}</p>
          </div>
          @if($complaint->serviceAccount->area)
            <div>
              <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-1">Area</label>
              <p class="text-gray-900 dark:text-white">{{ $complaint->serviceAccount->area->name }}</p>
            </div>
          @endif
          @if($complaint->serviceAccount->address)
            <div>
              <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-1">Alamat Service Account</label>
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ $complaint->serviceAccount->address }}</p>
              @if($complaint->serviceAccount->latitude && $complaint->serviceAccount->longitude)
                <a href="https://www.google.com/maps?q={{ $complaint->serviceAccount->latitude }},{{ $complaint->serviceAccount->longitude }}" 
                   target="_blank"
                   class="mt-2 inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  Lihat Lokasi
                </a>
              @endif
            </div>
          @endif
          @if($complaint->serviceAccount->contact_phone)
            <div>
              <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-1">Kontak</label>
              <a href="tel:{{ $complaint->serviceAccount->contact_phone }}" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                {{ $complaint->serviceAccount->contact_phone }}
              </a>
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>

<!-- Actions Footer -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]" x-data="{ showRejectModal: false }">
  <div class="p-6">

    <div class="flex justify-between items-center gap-4">
      <div class="flex gap-3">
        <x-button as="a" href="{{ route('admin.complaints.index') }}" variant="secondary" size="md">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Kembali ke Daftar
        </x-button>
      </div>
      
      <div class="flex gap-3">
        @if($complaint->status === 'open')
          <!-- Tombol Tolak Pengaduan - Hanya bisa dilakukan sebelum assign collector -->
          <button @click="showRejectModal = true" type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-medium text-red-700 shadow-theme-xs transition-colors hover:bg-red-50 hover:text-red-800 dark:border-red-700 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
            </svg>
            Tolak Pengaduan
          </button>
        @endif
        
        <form action="{{ route('admin.complaints.destroy', $complaint) }}" method="POST" onsubmit="return confirm('⚠️ Yakin ingin menghapus pengaduan ini?\n\nData yang akan dihapus:\n- Informasi pengaduan\n- Semua foto bukti\n- Riwayat pengaduan\n\nTindakan ini tidak dapat dibatalkan!')" class="inline-block">
          @csrf
          @method('DELETE')
          <x-button type="submit" variant="danger" size="md">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Hapus Pengaduan
          </x-button>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Tolak Pengaduan -->
  <div x-show="showRejectModal" 
       x-cloak
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       @click.self="showRejectModal = false"
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
       style="margin: 0;">
    <div @click.away="showRejectModal = false" 
         x-show="showRejectModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tolak Pengaduan #{{ $complaint->id }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pengaduan akan ditandai sebagai ditolak</p>
          </div>
        </div>
      </div>
      
      <form action="{{ route('admin.complaints.update-status', $complaint) }}" method="POST" x-data="{ reason: '' }">
        @csrf
        <input type="hidden" name="status" value="rejected">
        
        <div class="p-6">
          <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Alasan Penolakan <span class="text-red-500">*</span>
          </label>
          <textarea 
            name="rejection_reason" 
            id="rejection_reason"
            x-model="reason"
            rows="4"
            required
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:focus:border-red-400"
            placeholder="Jelaskan alasan mengapa pengaduan ini ditolak..."
          ></textarea>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Alasan ini akan ditampilkan kepada pelapor. Mohon berikan penjelasan yang jelas.
          </p>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
          <button @click="showRejectModal = false" type="button" class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            Batal
          </button>
          <button type="submit" 
                  :disabled="!reason.trim()"
                  :class="reason.trim() ? 'bg-red-600 hover:bg-red-700 cursor-pointer' : 'bg-red-300 dark:bg-red-800 cursor-not-allowed'"
                  class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
            </svg>
            Tolak Pengaduan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection
