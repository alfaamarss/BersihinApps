@extends('layouts.master')

@section('content')
    @if (Auth::user()->hasRole('admin'))
        <div class="grid grid-cols-12 gap-4 md:gap-6">
            <div class="col-span-12">
                <!-- Header Section -->
                <div class="mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard Admin</h2>
                            <p class="mt-1 text-gray-600 dark:text-gray-400">Selamat datang, {{ Auth::user()->name }}! Kelola sistem dengan mudah dan efisien.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('manage-user.index') }}" class="inline-flex items-center space-x-2 rounded-xl bg-blue-500 px-5 py-3 text-white shadow-lg transition-all duration-300 hover:bg-blue-600 hover:scale-105">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z"/>
                                </svg>
                                <span class="font-medium">Kelola Pengguna</span>
                            </a>
                            <a href="{{ route('manage-role.index') }}" class="inline-flex items-center space-x-2 rounded-xl border border-gray-300 bg-white px-5 py-3 text-gray-700 shadow-lg transition-all duration-300 hover:bg-gray-50 hover:scale-105 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                                <span class="font-medium">Kelola Role</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
                    <!-- Total Collector Card -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 p-6 shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
                        <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/5"></div>
                        <div class="relative flex items-center justify-between">
                            <div>
                                <span class="text-sm text-blue-100">Total Collector</span>
                                <h4 class="mt-2 text-3xl font-bold text-white">{{ $dashboardStats['collectors'] ?? 0 }}</h4>
                            </div>
                            <div class="rounded-full bg-white/20 p-3">
                                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Akun Layanan Card -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 p-6 shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
                        <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/5"></div>
                        <div class="relative flex items-center justify-between">
                            <div>
                                <span class="text-sm text-orange-100">Akun Layanan</span>
                                <h4 class="mt-2 text-3xl font-bold text-white">{{ $dashboardStats['serviceAccounts'] ?? 0 }}</h4>
                            </div>
                            <div class="rounded-full bg-white/20 p-3">
                                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Pembayaran Masuk Card -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 p-6 shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
                        <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/5"></div>
                        <div class="relative flex items-center justify-between">
                            <div>
                                <span class="text-sm text-emerald-100">Pembayaran masuk</span>
                                <h4 class="mt-2 text-3xl font-bold text-white">{{ $dashboardStats['paymentsReceived'] ?? 0 }}</h4>
                            </div>
                            <div class="rounded-full bg-white/20 p-3">
                                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total TPS Card -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-400 to-rose-600 p-6 shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
                        <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/5"></div>
                        <div class="relative flex items-center justify-between">
                            <div>
                                <span class="text-sm text-rose-100">Total TPS</span>
                                <h4 class="mt-2 text-3xl font-bold text-white">{{ $dashboardStats['totalTps'] ?? 0 }}</h4>
                            </div>
                            <div class="rounded-full bg-white/20 p-3">
                                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/>
                                    <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Top Areas Section -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
                    <!-- Complaint Status Pie Chart -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white text-center">Presentase Status Laporan Masuk</h3>
                        <div class="relative h-64" x-data="complaintChart()" x-init="init()">
                            <canvas x-ref="canvas" class="h-full w-full"></canvas>
                        </div>
                        <!-- Legend -->
                        <div class="mt-4 flex flex-wrap justify-center gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                                <span class="text-gray-600 dark:text-gray-400">Menunggu</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-blue-500"></span>
                                <span class="text-gray-600 dark:text-gray-400">Diproses</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                                <span class="text-gray-600 dark:text-gray-400">Selesai</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                                <span class="text-gray-600 dark:text-gray-400">Ditolak</span>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap justify-center gap-6 text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ $complaintStats['pending'] ?? 0 }} laporan</span>
                            <span>{{ $complaintStats['in_progress'] ?? 0 }} laporan</span>
                            <span>{{ $complaintStats['resolved'] ?? 0 }} laporan</span>
                            <span>{{ $complaintStats['rejected'] ?? 0 }} laporan</span>
                        </div>
                    </div>

                    <!-- Top 5 Areas -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white text-center">Top 5 Area dengan pengambilan Sampah Terbanyak - {{ $currentMonthName }}</h3>
                        <div class="space-y-5">
                            @php
                                $colors = ['bg-blue-500', 'bg-orange-500', 'bg-green-500', 'bg-rose-500', 'bg-purple-500'];
                                $textColors = ['text-blue-600', 'text-orange-600', 'text-green-600', 'text-rose-600', 'text-purple-600'];
                            @endphp
                            @forelse($topAreas as $index => $area)
                            <div class="flex items-start gap-4">
                                <span class="text-3xl font-bold {{ $textColors[$index] ?? 'text-gray-600' }}">#{{ $index + 1 }}</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <div>
                                            <h4 class="font-semibold text-gray-800 dark:text-white">{{ $area->name }}</h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($area->total_bags) }} kantong</p>
                                        </div>
                                        <span class="text-lg font-bold {{ $textColors[$index] ?? 'text-gray-600' }}">{{ $area->percentage }}%</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-2 rounded-full {{ $colors[$index] ?? 'bg-gray-500' }}" style="width: {{ $area->percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <p>Belum ada data pengambilan sampah</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Data Tables Section -->
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <!-- Request Pengambilan Terbaru -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Request Pengambilan Terbaru</h3>
                            <a href="{{ route('admin.off-schedule-pickups.index') }}" class="text-sm text-blue-600 hover:text-blue-700 transition-colors">Lihat Semua</a>
                        </div>
                        <div class="space-y-3">
                            @forelse($recentPickupRequests as $pickup)
                            <div class="flex items-start gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                <div class="rounded-full p-2
                                    @if($pickup->request_status === 'pending') bg-blue-100 dark:bg-blue-900/30
                                    @elseif($pickup->request_status === 'processing') bg-amber-100 dark:bg-amber-900/30
                                    @elseif($pickup->request_status === 'paid') bg-green-100 dark:bg-green-900/30
                                    @elseif($pickup->request_status === 'rejected') bg-red-100 dark:bg-red-900/30
                                    @else bg-gray-100 dark:bg-gray-700
                                    @endif">
                                    <svg class="h-4 w-4 
                                        @if($pickup->request_status === 'pending') text-blue-600 dark:text-blue-400
                                        @elseif($pickup->request_status === 'processing') text-amber-600 dark:text-amber-400
                                        @elseif($pickup->request_status === 'paid') text-green-600 dark:text-green-400
                                        @elseif($pickup->request_status === 'rejected') text-red-600 dark:text-red-400
                                        @else text-gray-600 dark:text-gray-400
                                        @endif" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/>
                                        <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $pickup->serviceAccount->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pickup->requested_pickup_date?->format('d M Y') ?? '-' }} • {{ $pickup->requested_pickup_time ?? '-' }}</p>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            @if($pickup->request_status === 'pending') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                            @elseif($pickup->request_status === 'processing') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                            @elseif($pickup->request_status === 'paid') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                            @elseif($pickup->request_status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @endif">
                                            @if($pickup->request_status === 'pending') Menunggu
                                            @elseif($pickup->request_status === 'processing') Diproses
                                            @elseif($pickup->request_status === 'paid') Lunas
                                            @elseif($pickup->request_status === 'rejected') Ditolak
                                            @else {{ ucfirst($pickup->request_status ?? 'Unknown') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-800/50">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="mt-1 text-sm text-gray-500">Belum ada request pengambilan</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Service Account Terbaru -->
                    <div class="xl:col-span-2 rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-800 dark:bg-gray-900"
                         x-data="serviceAccountTable(@js($recentServiceAccounts->map(fn($s)=>['name'=>$s->name,'user'=>$s->user?->name ?? '-','area'=>$s->area?->name ?? '-','status'=>$s->status])))" x-cloak>
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Akun Layanan Terbaru</h3>
                            <div class="flex w-full items-center gap-2 sm:w-auto">
                                <div class="relative flex-1 sm:w-64">
                                    <input type="text" x-model.debounce.200ms="q" placeholder="Cari nama/user/area..."
                                           class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" />
                                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.9 14.32a8 8 0 1 1 1.414-1.414l3.387 3.387-1.414 1.414-3.387-3.387ZM14 8a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" clip-rule="evenodd"/></svg>
                                </div>
                                <select x-model.number="perPage" class="rounded-lg border border-gray-200 bg-white px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                </select>
                                <a href="{{ route('admin.service-accounts.index') }}" class="hidden text-sm text-blue-600 hover:text-blue-700 transition-colors sm:inline">Lihat Semua</a>
                            </div>
                        </div>

                        <div class="w-full overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-y border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-800/50">
                                        <th class="py-4 px-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer select-none" @click="setSort('name')">
                                            <div class="inline-flex items-center gap-2 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                                Nama Akun
                                                <svg x-show="sortKey==='name' && sortAsc" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6l-5 6h10l-5-6z"/></svg>
                                                <svg x-show="sortKey==='name' && !sortAsc" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 14l5-6H5l5 6z"/></svg>
                                            </div>
                                        </th>
                                        <th class="py-4 px-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer select-none" @click="setSort('user')">
                                            <div class="inline-flex items-center gap-2 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                                User
                                                <svg x-show="sortKey==='user' && sortAsc" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6l-5 6h10l-5-6z"/></svg>
                                                <svg x-show="sortKey==='user' && !sortAsc" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 14l5-6H5l5 6z"/></svg>
                                            </div>
                                        </th>
                                        <th class="py-4 px-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer select-none" @click="setSort('area')">
                                            <div class="inline-flex items-center gap-2 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                                Area
                                                <svg x-show="sortKey==='area' && sortAsc" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6l-5 6h10l-5-6z"/></svg>
                                                <svg x-show="sortKey==='area' && !sortAsc" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 14l5-6H5l5 6z"/></svg>
                                            </div>
                                        </th>
                                        <th class="py-4 px-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <template x-for="s in paged" :key="s.name + s.user">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                            <td class="py-4 px-2">
                                                <div class="flex items-center space-x-3">
                                                    <div class="h-10 w-10 flex-shrink-0">
                                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center text-white font-semibold text-sm" x-text="s.name.charAt(0).toUpperCase()"></div>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="s.name"></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-2">
                                                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="s.user"></p>
                                            </td>
                                            <td class="py-4 px-2">
                                                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="s.area"></p>
                                            </td>
                                            <td class="py-4 px-2">
                                                <span x-show="s.status === 'active'" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                    <svg class="mr-1.5 h-2 w-2 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                    Aktif
                                                </span>
                                                <span x-show="s.status !== 'active'" class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                    <svg class="mr-1.5 h-2 w-2 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                    Nonaktif
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="paged.length === 0">
                                        <td colspan="4" class="py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada akun layanan ditemukan</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                            <div class="text-xs text-gray-500">Menampilkan <span x-text="start + 1"></span>–<span x-text="end"></span> dari <span x-text="total"></span> akun</div>
                            <div class="flex items-center gap-2">
                                <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300" @click="prev()" :disabled="page===1">Sebelumnya</button>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Hal. <span x-text="page"></span>/<span x-text="totalPages"></span></span>
                                <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300" @click="next()" :disabled="page===totalPages">Berikutnya</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('body:end')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Complaint Status Chart Alpine Component
    window.complaintChart = function() {
        return {
            chart: null,
            init() {
                this.$nextTick(() => {
                    this.createChart();
                });
            },
            createChart() {
                if (this.chart) {
                    this.chart.destroy();
                    this.chart = null;
                }

                if (!this.$refs.canvas) {
                    console.error('Canvas element not found');
                    return;
                }

                const ctx = this.$refs.canvas.getContext('2d');

                const data = {
                    pending: {{ $complaintStats['pending'] ?? 0 }},
                    in_progress: {{ $complaintStats['in_progress'] ?? 0 }},
                    resolved: {{ $complaintStats['resolved'] ?? 0 }},
                    rejected: {{ $complaintStats['rejected'] ?? 0 }}
                };

                this.chart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'],
                        datasets: [{
                            data: [data.pending, data.in_progress, data.resolved, data.rejected],
                            backgroundColor: [
                                'rgba(251, 191, 36, 0.9)',  // Amber - Menunggu
                                'rgba(59, 130, 246, 0.9)',  // Blue - Diproses
                                'rgba(16, 185, 129, 0.9)',  // Emerald - Selesai
                                'rgba(244, 63, 94, 0.9)'    // Rose - Ditolak
                            ],
                            borderColor: [
                                'rgba(251, 191, 36, 1)',
                                'rgba(59, 130, 246, 1)',
                                'rgba(16, 185, 129, 1)',
                                'rgba(244, 63, 94, 1)'
                            ],
                            borderWidth: 2,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                        return `${context.label}: ${context.parsed} laporan (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        animation: {
                            animateRotate: true,
                            animateScale: true,
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
        }
    }

    // Service Account Table Alpine Component
    window.serviceAccountTable = function(rows = []) {
        return {
            rows,
            q: '',
            sortKey: 'name',
            sortAsc: true,
            page: 1,
            perPage: 5,
            get filtered() {
                const q = this.q.toLowerCase().trim();
                if (!q) return this.rows;
                return this.rows.filter(s => [s.name, s.user, s.area].some(v => (v || '').toLowerCase().includes(q)));
            },
            get sorted() {
                return [...this.filtered].sort((a, b) => {
                    const ka = (a[this.sortKey] || '').toString().toLowerCase();
                    const kb = (b[this.sortKey] || '').toString().toLowerCase();
                    if (ka < kb) return this.sortAsc ? -1 : 1;
                    if (ka > kb) return this.sortAsc ? 1 : -1;
                    return 0;
                });
            },
            get total() { return this.sorted.length; },
            get totalPages() { return Math.max(1, Math.ceil(this.total / this.perPage)); },
            get start() { return (this.page - 1) * this.perPage; },
            get end() { return Math.min(this.start + this.perPage, this.total); },
            get paged() { return this.sorted.slice(this.start, this.start + this.perPage); },
            setSort(key) {
                if (this.sortKey === key) {
                    this.sortAsc = !this.sortAsc;
                } else {
                    this.sortKey = key; this.sortAsc = true;
                }
                this.page = 1;
            },
            prev() { if (this.page > 1) this.page--; },
            next() { if (this.page < this.totalPages) this.page++; },
            reset() { this.q = ''; this.perPage = 5; this.page = 1; this.sortKey = 'name'; this.sortAsc = true; }
        }
    }

    // Add smooth scrolling and page transition effects
    document.addEventListener('DOMContentLoaded', function() {
        // Add loading animation to cards
        const cards = document.querySelectorAll('.group');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>

<style>
    /* Custom animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out;
    }
</style>
@endpush
