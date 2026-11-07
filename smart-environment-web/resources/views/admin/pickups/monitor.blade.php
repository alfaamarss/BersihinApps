@extends('layouts.master')

@section('title', 'Monitor Pengambilan Sampah')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
    <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-black dark:text-white">Monitor Pengambilan Sampah</h2>
                    <p class="text-sm text-gray-500 mt-1">Pantau status pengambilan sampah real-time</p>
                </div>
                <div class="flex gap-2">
                    <button id="btnGenerateDeliveries" class="rounded-lg border border-green-500 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-100 dark:border-green-600 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30">
                        <i class="fas fa-magic mr-2"></i>Generate Deliveries
                    </button>
                    <button id="btnAutoRefresh" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <i class="fas fa-sync-alt mr-2"></i>Auto: <span id="autoRefreshStatus">OFF</span>
                    </button>
                    <button id="btnRefresh" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">
                        <i class="fas fa-sync mr-2"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" id="statisticsCards">
                <!-- Total Pickups -->
                <div class="rounded-xl border border-l-4 border-l-primary border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Total Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white" id="statTotalPickups">0</p>
                        </div>
                        <div class="text-3xl text-gray-300">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>

                <!-- Selesai -->
                <div class="rounded-xl border border-l-4 border-l-green-500 border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Selesai</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white" id="statCollected">0</p>
                        </div>
                        <div class="text-3xl text-gray-300">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <!-- Completion Rate -->
                <div class="rounded-xl border border-l-4 border-l-blue-500 border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Completion Rate</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white mb-2" id="statCompletionRate">0.0%</p>
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-blue-500 h-2 rounded-full transition-all" id="progressCompletionRate" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="text-3xl text-gray-300 ml-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" id="filterForm" class="mb-6 grid grid-cols-1 md:grid-cols-6 gap-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-black dark:text-white">Tanggal</label>
                    <input type="date" name="date" id="filterDate" value="{{ date('Y-m-d') }}"
                        class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-black dark:text-white">Kecamatan</label>
                    <select name="kecamatan_id" id="filterKecamatan"
                        class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <option value="">Semua Kecamatan</option>
                        @foreach($kecamatans as $kecamatan)
                            <option value="{{ $kecamatan->id }}">{{ $kecamatan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-black dark:text-white">Kelurahan</label>
                    <select name="area_id" id="filterKelurahan"
                        class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        disabled>
                        <option value="">Pilih Kecamatan dulu</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-black dark:text-white">Collector</label>
                    <select name="collector_id" id="filterCollector"
                        class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <option value="">Semua Collector</option>
                        @foreach($collectors as $collector)
                            <option value="{{ $collector->id }}">{{ $collector->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-black dark:text-white">Status</label>
                    <select name="status" id="filterStatus"
                        class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="skipped">Skipped</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-black dark:text-white">Cari</label>
                    <input type="text" name="search" id="filterSearch" placeholder="Cari nama warga atau alamat..."
                        class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                </div>
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Area</th>
                            <th class="px-4 py-3">Collector</th>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Konfirmasi</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pickupsTableBody" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Memuat data...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="paginationContainer" class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-500" id="paginationInfo">-</div>
                <div id="paginationLinks"></div>
            </div>
        </div>
    </div>
</div>

<!-- Generate Deliveries Modal -->
<div id="generateModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 backdrop-blur-sm">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl dark:bg-gray-800 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-500 dark:from-green-700 dark:to-green-600 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-magic text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Generate Deliveries</h3>
            </div>
            <button id="closeGenerateModal" class="text-white/80 hover:text-white hover:bg-white/10 rounded-lg p-2 transition-all">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Generate waste deliveries untuk beberapa hari ke depan berdasarkan jadwal yang aktif.
            </p>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Jumlah Hari Ke Depan
                </label>
                <input type="number" id="daysAheadInput" value="7" min="1" max="30" 
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-white focus:border-green-500 focus:outline-none">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>Maksimal 30 hari
                </p>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <div class="flex gap-3">
                    <div class="text-blue-600 dark:text-blue-400 mt-0.5">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="text-sm text-blue-800 dark:text-blue-300">
                        <strong>Info:</strong> Sistem akan membuat deliveries baru untuk semua service account yang aktif, berdasarkan jadwal yang memiliki auto-generate aktif.
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button id="btnCancelGenerate" class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Batal
                </button>
                <button id="btnConfirmGenerate" class="flex-1 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    <i class="fas fa-magic mr-2"></i>Generate
                </button>
            </div>
        </div>
    </div>
</div>

@push('body:end')
<script>
let autoRefreshInterval = null;
let autoRefreshEnabled = false;

document.addEventListener('DOMContentLoaded', function() {
    // Load data immediately
    loadStatistics();
    loadPickups();

    // Kecamatan change event - load Kelurahan dynamically
    document.getElementById('filterKecamatan').addEventListener('change', function() {
        const kecamatanId = this.value;
        const kelurahanSelect = document.getElementById('filterKelurahan');
        
        if (!kecamatanId) {
            kelurahanSelect.innerHTML = '<option value="">Pilih Kecamatan dulu</option>';
            kelurahanSelect.disabled = true;
            kelurahanSelect.value = '';
            loadStatistics();
            loadPickups();
            return;
        }
        
        // Show loading state
        kelurahanSelect.innerHTML = '<option value="">Memuat...</option>';
        kelurahanSelect.disabled = true;
        
        // Fetch Kelurahan data
        fetch(`{{ route('admin.pickups.kelurahan') }}?kecamatan_id=${kecamatanId}`)
            .then(response => response.json())
            .then(response => {
                const kelurahans = response.data || [];
                let options = '<option value="">Semua Kelurahan</option>';
                
                kelurahans.forEach(kelurahan => {
                    options += `<option value="${kelurahan.id}">${kelurahan.name}</option>`;
                });
                
                kelurahanSelect.innerHTML = options;
                kelurahanSelect.disabled = false;
                
                // Reload data with kecamatan filter
                loadStatistics();
                loadPickups();
            })
            .catch(error => {
                kelurahanSelect.innerHTML = '<option value="">Gagal memuat data</option>';
                kelurahanSelect.disabled = false;
            });
    });

    // Filter changes
    ['filterDate', 'filterKelurahan', 'filterCollector', 'filterStatus'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            loadStatistics();
            loadPickups();
        });
    });

    document.getElementById('filterSearch').addEventListener('keyup', debounce(function() {
        loadPickups();
    }, 500));

    // Manual refresh
    document.getElementById('btnRefresh').addEventListener('click', function() {
        loadStatistics();
        loadPickups();
    });

    // Auto refresh toggle
    document.getElementById('btnAutoRefresh').addEventListener('click', function() {
        const statusSpan = document.getElementById('autoRefreshStatus');
        autoRefreshEnabled = !autoRefreshEnabled;

        if (autoRefreshEnabled) {
            statusSpan.textContent = 'ON';
            statusSpan.classList.remove('text-gray-700');
            statusSpan.classList.add('text-green-600', 'font-bold');
            this.classList.remove('bg-white', 'border-gray-300', 'text-gray-700');
            this.classList.add('bg-green-50', 'border-green-500', 'text-green-700');

            autoRefreshInterval = setInterval(function() {
                loadStatistics();
                loadPickups();
            }, 30000); // 30 seconds
        } else {
            statusSpan.textContent = 'OFF';
            statusSpan.classList.remove('text-green-600', 'font-bold');
            statusSpan.classList.add('text-gray-700');
            this.classList.remove('bg-green-50', 'border-green-500', 'text-green-700');
            this.classList.add('bg-white', 'border-gray-300', 'text-gray-700');

            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }
    });
    
    // Generate deliveries
    document.getElementById('btnGenerateDeliveries').addEventListener('click', function() {
        // Show modal
        const modal = document.getElementById('generateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
    
    // Close generate modal
    document.getElementById('closeGenerateModal').addEventListener('click', function() {
        closeGenerateModal();
    });
    
    document.getElementById('btnCancelGenerate').addEventListener('click', function() {
        closeGenerateModal();
    });
    
    document.getElementById('generateModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeGenerateModal();
        }
    });
    
    // Confirm generate
    document.getElementById('btnConfirmGenerate').addEventListener('click', function() {
        const daysAhead = parseInt(document.getElementById('daysAheadInput').value) || 7;
        const btn = this;
        const originalHtml = btn.innerHTML;
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
        document.getElementById('btnCancelGenerate').disabled = true;
        
        fetch('{{ route('admin.pickups.generate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                days_ahead: daysAhead
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal first
                closeGenerateModal();
                
                // Build HTML message
                let htmlMessage = `
                    <div class="text-left">
                        <div class="mb-4">
                            <p class="text-lg font-semibold text-gray-800">${data.message}</p>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2 p-2 bg-blue-50 rounded">
                                <span class="text-2xl">📋</span>
                                <span><strong>Schedules Processed:</strong> ${data.data.schedules_processed}</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-green-50 rounded">
                                <span class="text-2xl">📦</span>
                                <span><strong>Deliveries Created:</strong> ${data.data.deliveries_created}</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                                <span class="text-2xl">⏭️</span>
                                <span><strong>Deliveries Skipped:</strong> ${data.data.deliveries_skipped}</span>
                            </div>
                `;
                
                if (data.data.errors.length > 0) {
                    htmlMessage += `
                            <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-2xl">⚠️</span>
                                    <strong class="text-yellow-800">Errors: ${data.data.errors.length}</strong>
                                </div>
                                <ul class="list-disc list-inside text-xs text-yellow-700 space-y-1">
                    `;
                    data.data.errors.forEach(e => {
                        htmlMessage += `<li>Schedule ${e.schedule_id}: ${e.error}</li>`;
                    });
                    htmlMessage += `
                                </ul>
                            </div>
                    `;
                }
                
                htmlMessage += `
                        </div>
                    </div>
                `;
                
                // Show SweetAlert
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    html: htmlMessage,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#10b981',
                    width: '600px'
                }).then(() => {
                    // Reload data
                    loadStatistics();
                    loadPickups();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: data.message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Gagal generate deliveries. Silakan coba lagi.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444'
            });
        })
        .finally(() => {
            // Re-enable buttons
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            document.getElementById('btnCancelGenerate').disabled = false;
        });
    });
    
    // Handle ESC key to close generate modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGenerateModal();
        }
    });
});

function closeGenerateModal() {
    const modal = document.getElementById('generateModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    // Reset input
    document.getElementById('daysAheadInput').value = 7;
}

function loadStatistics() {
    const params = new URLSearchParams(getFilterParams());
    
    // Show loading state
    document.getElementById('statTotalPickups').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    document.getElementById('statCollected').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    document.getElementById('statCompletionRate').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    // Add cache busting
    params.append('_', Date.now());
    
    fetch('{{ route('admin.pickups.statistics') }}?' + params.toString(), {
        cache: 'no-cache',
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(response => {
            if (response && response.success && response.data) {
                const data = response.data;
                
                // Safely update each element
                const totalEl = document.getElementById('statTotalPickups');
                const collectedEl = document.getElementById('statCollected');
                const rateEl = document.getElementById('statCompletionRate');
                const progressEl = document.getElementById('progressCompletionRate');
                
                if (totalEl) {
                    totalEl.textContent = (data.total_pickups !== undefined && data.total_pickups !== null) ? data.total_pickups : 0;
                }
                
                if (collectedEl) {
                    // Sum both 'collected' (scheduled) and 'completed' (request) as completed
                    const collected = (data.by_status && data.by_status.collected !== undefined) ? data.by_status.collected : 0;
                    const completed = (data.by_status && data.by_status.completed !== undefined) ? data.by_status.completed : 0;
                    collectedEl.textContent = collected + completed;
                }
                
                if (rateEl && progressEl) {
                    const rate = parseFloat(data.completion_rate || 0).toFixed(1);
                    rateEl.textContent = rate + '%';
                    progressEl.style.width = rate + '%';
                }
            } else {
                console.error('Invalid response structure:', response);
                throw new Error('Invalid response');
            }
        })
        .catch(error => {
            // Set default values on error
            const totalEl = document.getElementById('statTotalPickups');
            const collectedEl = document.getElementById('statCollected');
            const rateEl = document.getElementById('statCompletionRate');
            const progressEl = document.getElementById('progressCompletionRate');
            
            if (totalEl) totalEl.textContent = '0';
            if (collectedEl) collectedEl.textContent = '0';
            if (rateEl) rateEl.textContent = '0.0%';
            if (progressEl) progressEl.style.width = '0%';
        });
}

function loadPickups(page = 1) {
    const params = getFilterParams();
    params.page = page;
    params._ = Date.now(); // Cache busting
    const queryString = new URLSearchParams(params).toString();

    document.getElementById('pickupsTableBody').innerHTML = `
        <tr>
            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Memuat data...</p>
            </td>
        </tr>
    `;

    fetch('{{ route('admin.pickups.monitor.data') }}?' + queryString, {
        cache: 'no-cache'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal memuat data');
            }
            return response.json();
        })
        .then(response => {
            if (response.success) {
                renderTable(response.data);
                renderPagination(response.pagination);
            } else {
                throw new Error(response.message || 'Terjadi kesalahan');
            }
        })
        .catch(error => {
            document.getElementById('pickupsTableBody').innerHTML = `
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-red-500">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Gagal memuat data</p>
                    </td>
                </tr>
            `;
        });
}

function renderTable(pickups) {
    const tbody = document.getElementById('pickupsTableBody');
    if (!pickups || pickups.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-inbox text-2xl mb-2 text-gray-400 dark:text-gray-600"></i>
                    <p>Tidak ada data</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    pickups.forEach(pickup => {
        const statusBadge = getStatusBadge(pickup.status);
        const confirmBadge = getConfirmBadge(pickup.confirmation_status);
        const collector = pickup.collector?.name || '-';
        
        // Service account name and user name
        const serviceAccountName = pickup.service_account?.name || '-';
        const userName = pickup.resident?.name || pickup.service_account?.user?.name || '-';
        const area = pickup.area?.name || '-';
        
        // Badge untuk pickup type
        const pickupTypeBadge = pickup.pickup_type === 'request' 
            ? '<span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">Luar Jadwal</span>'
            : '';
        
        // Untuk off-schedule pickups, tidak perlu tombol approve/reject
        const isOffSchedule = pickup.pickup_type === 'request';

        html += `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">#${pickup.id}${pickupTypeBadge}</td>
                <td class="px-4 py-3">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900 dark:text-gray-100">${serviceAccountName}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">${userName}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">${area}</td>
                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">${collector}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">${formatDate(pickup.pickup_date)}</td>
                <td class="px-4 py-3">${statusBadge}</td>
                <td class="px-4 py-3">${confirmBadge}</td>
                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">Rp ${parseFloat(pickup.total_amount || 0).toLocaleString('id-ID')}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        ${isOffSchedule 
                            ? `<a href="/admin/off-schedule-pickups/${pickup.id}" class="text-primary hover:text-primary/80 dark:text-primary-400 dark:hover:text-primary-300" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>`
                            : `<a href="/admin/pickups/${pickup.id}" class="text-primary hover:text-primary/80 dark:text-primary-400 dark:hover:text-primary-300" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            ${pickup.confirmation_status === 'pending' ? `
                                <button onclick="approvePickup(${pickup.id})" class="text-green-600 hover:text-green-700 dark:text-green-400" title="Approve">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button onclick="rejectPickup(${pickup.id})" class="text-red-600 hover:text-red-700 dark:text-red-400" title="Reject">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            ` : ''}`
                        }
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderPagination(response) {
    const { current_page, last_page, from, to, total } = response;

    document.getElementById('paginationInfo').textContent = `Menampilkan ${from || 0} - ${to || 0} dari ${total || 0} data`;

    if (last_page <= 1) {
        document.getElementById('paginationLinks').innerHTML = '';
        return;
    }

    let html = '<div class="flex gap-1">';

    // Previous
    if (current_page > 1) {
        html += `<button onclick="loadPickups(${current_page - 1})" class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">‹</button>`;
    }

    // Pages
    for (let i = 1; i <= last_page; i++) {
        if (i === current_page) {
            html += `<button class="px-3 py-1 rounded bg-primary text-white">${i}</button>`;
        } else if (i === 1 || i === last_page || (i >= current_page - 2 && i <= current_page + 2)) {
            html += `<button onclick="loadPickups(${i})" class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">${i}</button>`;
        } else if (i === current_page - 3 || i === current_page + 3) {
            html += `<span class="px-2">...</span>`;
        }
    }

    // Next
    if (current_page < last_page) {
        html += `<button onclick="loadPickups(${current_page + 1})" class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">›</button>`;
    }

    html += '</div>';
    document.getElementById('paginationLinks').innerHTML = html;
}

function getFilterParams() {
    const dateEl = document.getElementById('filterDate');
    const areaEl = document.getElementById('filterKelurahan');
    const collectorEl = document.getElementById('filterCollector');
    const statusEl = document.getElementById('filterStatus');
    const searchEl = document.getElementById('filterSearch');
    
    return {
        date: dateEl ? dateEl.value : '',
        area_id: areaEl ? areaEl.value : '',
        collector_id: collectorEl ? collectorEl.value : '',
        status: statusEl ? statusEl.value : '',
        search: searchEl ? searchEl.value : ''
    };
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">Pending</span>',
        'in_progress': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">In Progress</span>',
        'completed': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">Completed</span>',
        'skipped': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">Skipped</span>'
    };
    return badges[status] || status;
}

function getConfirmBadge(status) {
    const badges = {
        'pending': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">Pending</span>',
        'confirmed': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">Confirmed</span>',
        'rejected': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">Rejected</span>'
    };
    return badges[status] || status;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}

function approvePickup(id) {
    Swal.fire({
        title: 'Approve Pengambilan?',
        text: 'Konfirmasi bahwa pengambilan sampah ini sudah sesuai',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Approve!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'dark:bg-gray-800 dark:text-white',
            title: 'dark:text-white',
            htmlContainer: 'dark:text-gray-300'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                html: 'Mohon tunggu',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/admin/pickups/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Pengambilan sampah telah diapprove',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadPickups();
                    loadStatistics();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message || 'Terjadi kesalahan'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal approve pengambilan'
                });
            });
        }
    });
}

function rejectPickup(id) {
    Swal.fire({
        title: 'Reject Pengambilan?',
        html: `
            <p class="mb-3">Berikan alasan penolakan:</p>
            <textarea id="rejectReason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="3" placeholder="Contoh: Sampah tidak sesuai kategori"></textarea>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Reject!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'dark:bg-gray-800 dark:text-white',
            title: 'dark:text-white',
            htmlContainer: 'dark:text-gray-300'
        },
        preConfirm: () => {
            const reason = document.getElementById('rejectReason').value;
            if (!reason) {
                Swal.showValidationMessage('Alasan penolakan harus diisi');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                html: 'Mohon tunggu',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/admin/pickups/${id}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    reason: result.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Pengambilan sampah telah direject',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadPickups();
                    loadStatistics();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message || 'Terjadi kesalahan'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal reject pengambilan'
                });
            });
        }
    });
}
</script>
@endpush
@endsection