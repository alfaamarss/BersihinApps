@extends('layouts.master')

@section('title','Notifikasi')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Notifikasi</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola semua notifikasi Anda</p>
        </div>
        <div class="flex items-center gap-3">
          @if($notifications->where('read_at', null)->count() > 0)
            <form action="{{ route('admin.notifications.markAllAsRead') }}" method="POST" class="inline">
              @csrf
              <x-button type="submit" variant="secondary" size="md">Tandai Semua Dibaca</x-button>
            </form>
          @endif
          @if($notifications->where('read_at', '!=', null)->count() > 0)
            <form action="{{ route('admin.notifications.clearAll') }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus semua notifikasi yang sudah dibaca?')">
              @csrf
              @method('DELETE')
              <x-button type="submit" variant="danger" size="md">Hapus Yang Dibaca</x-button>
            </form>
          @endif
        </div>
      </div>

      @if($notifications->count() > 0)
        <div class="space-y-3">
          @foreach($notifications as $notification)
            <div class="flex items-start gap-4 p-4 rounded-lg border {{ $notification->read_at ? 'bg-white border-gray-200' : 'bg-blue-50 border-blue-200' }} dark:border-gray-700 dark:{{ $notification->read_at ? 'bg-white/[0.03]' : 'bg-blue-900/20' }}">
              <!-- Icon -->
              <div class="flex-shrink-0 mt-1">
                @php
                  $iconColor = $notification->data['color'] ?? 'blue';
                  $colorClasses = [
                    'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                    'green' => 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
                    'red' => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                    'yellow' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
                  ];
                @endphp
                <div class="w-10 h-10 rounded-full {{ $colorClasses[$iconColor] ?? $colorClasses['blue'] }} flex items-center justify-center">
                  @if(($notification->data['icon'] ?? '') === 'user-plus')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                  @elseif(($notification->data['icon'] ?? '') === 'user-x')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                    </svg>
                  @elseif(($notification->data['icon'] ?? '') === 'map-pin')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                  @endif
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1">
                    <h3 class="text-sm font-semibold text-black dark:text-white">
                      {{ $notification->data['title'] ?? 'Notifikasi' }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ $notification->data['message'] ?? '' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                      {{ $notification->created_at->diffForHumans() }}
                    </p>
                  </div>
                  
                  <!-- Unread Indicator -->
                  @if(!$notification->read_at)
                    <span class="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full"></span>
                  @endif
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 mt-3">
                  @if(isset($notification->data['url']))
                    <x-button as="a" href="{{ $notification->data['url'] }}" variant="secondary" size="sm">
                      Lihat Detail
                    </x-button>
                  @endif
                  @if(!$notification->read_at)
                    <form action="{{ route('admin.notifications.markAsRead', $notification->id) }}" method="POST" class="inline">
                      @csrf
                      <x-button type="submit" variant="secondary" size="sm">Tandai Dibaca</x-button>
                    </form>
                  @endif
                  <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
                  </form>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <!-- Pagination -->
        @if($notifications->hasPages())
          <div class="mt-6">
            {{ $notifications->links() }}
          </div>
        @endif
      @else
        <div class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <p class="mt-2 text-sm text-gray-500">Tidak ada notifikasi</p>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
