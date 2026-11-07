@extends('layouts.master')

@section('title', 'Kalender Jadwal')

@push('styles')
<style>
  /* Calendar Container */
  .fc {
    font-family: inherit;
    --fc-border-color: #E5E7EB;
    --fc-button-text-color: #374151;
    --fc-button-bg-color: #FFFFFF;
    --fc-button-border-color: #D1D5DB;
    --fc-button-hover-bg-color: #F3F4F6;
    --fc-button-hover-border-color: #D1D5DB;
    --fc-button-active-bg-color: #E5E7EB;
    --fc-button-active-border-color: #D1D5DB;
    --fc-event-bg-color: #3B82F6;
    --fc-event-border-color: #3B82F6;
    --fc-today-bg-color: #F9FAFB;
  }

  .dark .fc {
    --fc-border-color: #374151;
    --fc-button-text-color: #D1D5DB;
    --fc-button-bg-color: #1F2937;
    --fc-button-border-color: #4B5563;
    --fc-button-hover-bg-color: #374151;
    --fc-button-hover-border-color: #4B5563;
    --fc-button-active-bg-color: #374151;
    --fc-button-active-border-color: #4B5563;
    --fc-today-bg-color: #1F2937;
  }

  /* Toolbar */
  .fc .fc-toolbar-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
  }
  .dark .fc .fc-toolbar-title {
    color: #F9FAFB;
  }

  .fc .fc-button {
    padding: 0.5rem 1rem;
    font-weight: 500;
    text-transform: capitalize;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.2s;
  }
  
  .fc .fc-button-primary {
    background-color: var(--fc-button-bg-color);
    border-color: var(--fc-button-border-color);
    color: var(--fc-button-text-color);
  }
  
  .fc .fc-button-primary:hover {
    background-color: var(--fc-button-hover-bg-color);
    border-color: var(--fc-button-hover-border-color);
  }

  .fc .fc-button-primary:not(:disabled).fc-button-active,
  .fc .fc-button-primary:not(:disabled):active {
    background-color: var(--fc-button-active-bg-color);
    border-color: var(--fc-button-active-border-color);
    color: var(--fc-button-text-color);
  }

  /* Events */
  .fc-event {
    border: none;
    padding: 2px 4px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    transition: transform 0.1s;
  }
  .fc-event:hover {
    transform: scale(1.02);
    z-index: 5;
  }
  
  /* Grid */
  .fc-theme-standard td, .fc-theme-standard th {
    border-color: var(--fc-border-color);
  }
  
  .fc .fc-col-header-cell-cushion {
    padding: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #4B5563;
  }
  .dark .fc .fc-col-header-cell-cushion {
    color: #9CA3AF;
  }

  .fc .fc-daygrid-day-number {
    padding: 8px 12px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6B7280;
  }
  .dark .fc .fc-daygrid-day-number {
    color: #9CA3AF;
  }

  .fc-day-today {
    background-color: var(--fc-today-bg-color) !important;
  }

  /* Custom Event Styles */
  .event-assigned {
    background-color: #EFF6FF;
    color: #1D4ED8;
    border-left: 3px solid #2563EB;
  }
  .dark .event-assigned {
    background-color: rgba(30, 58, 138, 0.2);
    color: #93C5FD;
    border-left: 3px solid #3B82F6;
  }

  .event-unassigned {
    background-color: #F3F4F6;
    color: #4B5563;
    border-left: 3px solid #9CA3AF;
  }
  .dark .event-unassigned {
    background-color: rgba(55, 65, 81, 0.2);
    color: #D1D5DB;
    border-left: 3px solid #6B7280;
  }
</style>
@endpush

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kalender Jadwal Pengangkutan</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kelola dan pantau jadwal pengambilan sampah.</p>
        </div>
        <x-button as="a" href="{{ route('admin.schedules.index') }}" variant="secondary" size="md" class="flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
          Tampilan List
        </x-button>
      </div>

      <!-- Filter Section -->
      <div class="mb-8 rounded-xl bg-gray-50 p-4 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700" x-data="calendarFilter()">
        <div class="flex flex-col md:flex-row md:items-end gap-4">
          <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                <span class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                  Kecamatan
                </span>
              </label>
              <select 
                id="filter-kecamatan"
                x-model="kecamatanId" 
                @change="loadKelurahans(); refreshCalendar()"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
              >
                <option value="">Semua Kecamatan</option>
                @foreach($kecamatans as $kecamatan)
                  <option value="{{ $kecamatan->id }}">{{ $kecamatan->name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                <span class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  Kelurahan
                </span>
              </label>
              <select 
                id="filter-area"
                x-model="areaId" 
                @change="refreshCalendar()"
                :disabled="!kecamatanId"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <option value="">Semua Kelurahan</option>
                <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
                  <option :value="kelurahan.id" x-text="kelurahan.name"></option>
                </template>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <x-button type="button" variant="secondary" size="md" @click="resetFilter()" class="h-[42px]">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              Reset Filter
            </x-button>
          </div>
        </div>

        <!-- Legend -->
        <div class="mt-4 flex flex-wrap gap-6 text-sm border-t border-gray-200 dark:border-gray-700 pt-4">
          <div class="flex items-center gap-2">
            <span class="flex h-3 w-3 rounded-full bg-blue-500 ring-2 ring-blue-100 dark:ring-blue-900"></span>
            <span class="text-gray-600 dark:text-gray-400 font-medium">Ada Collector</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="flex h-3 w-3 rounded-full bg-gray-400 ring-2 ring-gray-100 dark:ring-gray-700"></span>
            <span class="text-gray-600 dark:text-gray-400 font-medium">Belum Ada Collector</span>
          </div>
        </div>
      </div>

      <!-- Calendar Container -->
      <div id="schedule-calendar" class="min-h-[700px]"></div>
    </div>
  </div>
</div>

<!-- Event Detail Modal -->
<div 
  id="eventModal" 
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm hidden transition-opacity duration-300"
  onclick="if(event.target === this) closeEventModal()"
>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all duration-300 scale-100">
    <div id="modalHeader" class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detail Jadwal</h3>
      <button onclick="closeEventModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    
    <div class="p-6">
      <div id="modalContent" class="space-y-5">
        <!-- Content will be injected here -->
      </div>

      <!-- Quick Assign Form (shown when no collector) -->
      <div id="quickAssignForm" class="hidden mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
          Tambah Collector
        </h4>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Collector</label>
            <select id="quickAssignCollector" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
              <option value="">-- Pilih Collector --</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai</label>
              <input type="date" id="quickAssignStartDate" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai</label>
              <input type="date" id="quickAssignEndDate" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
            </div>
          </div>
          <div id="quickAssignError" class="hidden text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg"></div>
          <button type="button" id="quickAssignBtn" onclick="submitQuickAssign()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-semibold flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Tugaskan Collector
          </button>
        </div>
      </div>

      <div class="mt-6 flex gap-3">
        <a id="modalEditLink" href="#" class="flex-1 inline-flex justify-center items-center px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-semibold shadow-lg shadow-blue-500/30">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Edit Jadwal
        </a>
        <button onclick="closeEventModal()" class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
          Tutup
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
  let calendar;
  let currentScheduleId = null;
  let collectorsLoaded = false;

  function calendarFilter() {
    return {
      kecamatanId: '',
      areaId: '',
      kelurahans: [],

      async loadKelurahans() {
        this.areaId = '';
        if (!this.kecamatanId) {
          this.kelurahans = [];
          return;
        }
        try {
          const response = await fetch(`{{ route('admin.schedules.kelurahans') }}?kecamatan_id=${this.kecamatanId}`);
          this.kelurahans = await response.json();
        } catch (error) {
          console.error('Failed to load kelurahans:', error);
          this.kelurahans = [];
        }
      },

      refreshCalendar() {
        if (calendar) {
          setTimeout(() => {
            calendar.refetchEvents();
          }, 50);
        }
      },

      resetFilter() {
        this.kecamatanId = '';
        this.areaId = '';
        this.kelurahans = [];
        this.refreshCalendar();
      }
    }
  }

  async function loadCollectors() {
    if (collectorsLoaded) return;
    
    try {
      const response = await fetch(`{{ route('admin.collector-assignments.collectors') }}`);
      const collectors = await response.json();
      
      const select = document.getElementById('quickAssignCollector');
      select.innerHTML = '<option value="">-- Pilih Collector --</option>';
      
      collectors.forEach(collector => {
        const option = document.createElement('option');
        option.value = collector.id;
        option.textContent = `${collector.name} (${collector.email})`;
        select.appendChild(option);
      });
      
      collectorsLoaded = true;
    } catch (error) {
      console.error('Failed to load collectors:', error);
    }
  }

  function openEventModal(event) {
    const props = event.extendedProps;
    const modal = document.getElementById('eventModal');
    const content = document.getElementById('modalContent');
    const editLink = document.getElementById('modalEditLink');
    const modalHeader = document.getElementById('modalHeader');
    const quickAssignForm = document.getElementById('quickAssignForm');
    const quickAssignError = document.getElementById('quickAssignError');

    currentScheduleId = props.schedule_id;

    const statusClass = props.has_collector 
      ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 ring-blue-600/20'
      : 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-gray-600/20';
    const statusText = props.has_collector ? 'Sudah Ada Collector' : 'Belum Ada Collector';
    const statusIcon = props.has_collector 
      ? '<svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
      : '<svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

    content.innerHTML = `
      <div class="flex items-center justify-between">
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset ${statusClass}">
          ${statusIcon}
          ${statusText}
        </span>
      </div>
      
      <div class="grid grid-cols-1 gap-4">
        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700">
          <div class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm text-blue-600 dark:text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lokasi</span>
            <p class="font-semibold text-gray-900 dark:text-white">${props.area_name}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">${props.kecamatan_name}</p>
          </div>
        </div>

        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700">
          <div class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm text-orange-600 dark:text-orange-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Waktu Pengangkutan</span>
            <p class="font-semibold text-gray-900 dark:text-white">${props.day_label}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pukul ${props.pickup_time}</p>
          </div>
        </div>

        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700">
          <div class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm text-green-600 dark:text-green-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Collector Bertugas (${props.collector_count})</span>
            <p class="font-semibold text-gray-900 dark:text-white text-sm mt-0.5">${props.collectors}</p>
          </div>
        </div>
      </div>
    `;

    // Show/hide quick assign form based on collector status
    if (!props.has_collector) {
      loadCollectors();
      quickAssignForm.classList.remove('hidden');
      
      // Set default dates
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('quickAssignStartDate').value = today;
      document.getElementById('quickAssignStartDate').min = today;
      
      // Set end date to 30 days from today
      const endDate = new Date();
      endDate.setDate(endDate.getDate() + 30);
      document.getElementById('quickAssignEndDate').value = endDate.toISOString().split('T')[0];
      document.getElementById('quickAssignEndDate').min = today;
      
      // Reset form state
      document.getElementById('quickAssignCollector').value = '';
      quickAssignError.classList.add('hidden');
    } else {
      quickAssignForm.classList.add('hidden');
    }

    editLink.href = `{{ url('admin/schedules') }}/${props.schedule_id}/edit`;
    modal.classList.remove('hidden');
  }

  async function submitQuickAssign() {
    const collectorId = document.getElementById('quickAssignCollector').value;
    const startDate = document.getElementById('quickAssignStartDate').value;
    const endDate = document.getElementById('quickAssignEndDate').value;
    const errorDiv = document.getElementById('quickAssignError');
    const submitBtn = document.getElementById('quickAssignBtn');

    // Validation
    if (!collectorId) {
      errorDiv.textContent = 'Silakan pilih collector terlebih dahulu.';
      errorDiv.classList.remove('hidden');
      return;
    }

    if (!startDate) {
      errorDiv.textContent = 'Tanggal mulai harus diisi.';
      errorDiv.classList.remove('hidden');
      return;
    }

    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...';

    try {
      const response = await fetch(`{{ route('admin.collector-assignments.quick-assign') }}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          user_id: collectorId,
          schedule_id: currentScheduleId,
          start_date: startDate,
          end_date: endDate || null,
        }),
      });

      const result = await response.json();

      if (result.success) {
        // Close modal and refresh calendar
        closeEventModal();
        if (calendar) {
          calendar.refetchEvents();
        }
        // Show success message (you can use a toast notification here)
        alert(result.message);
      } else {
        errorDiv.textContent = result.message || 'Terjadi kesalahan saat menugaskan collector.';
        errorDiv.classList.remove('hidden');
      }
    } catch (error) {
      console.error('Quick assign error:', error);
      errorDiv.textContent = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
      errorDiv.classList.remove('hidden');
    } finally {
      // Re-enable button
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Tugaskan Collector';
    }
  }

  function closeEventModal() {
    document.getElementById('eventModal').classList.add('hidden');
    currentScheduleId = null;
  }

  document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('schedule-calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'id',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listWeek'
      },
      buttonText: {
        today: 'Hari Ini',
        month: 'Bulan',
        week: 'Minggu',
        list: 'Daftar'
      },
      firstDay: 1, // Monday
      height: 'auto',
      eventTimeFormat: {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      },
      eventClassNames: function(arg) {
        if (arg.event.extendedProps.has_collector) {
          return [ 'event-assigned' ];
        } else {
          return [ 'event-unassigned' ];
        }
      },
      eventContent: function(arg) {
        let timeText = arg.timeText;
        let title = arg.event.title;
        
        // Custom rendering for events
        let html = `
          <div class="fc-event-main-frame flex flex-col gap-0.5 overflow-hidden">
            <div class="fc-event-time text-[10px] opacity-75 font-medium truncate">${timeText}</div>
            <div class="fc-event-title font-semibold truncate">${title}</div>
          </div>
        `;
        
        return { html: html };
      },
      events: function(info, successCallback, failureCallback) {
        const kecamatanId = document.getElementById('filter-kecamatan').value;
        const areaId = document.getElementById('filter-area').value;
        
        let url = `{{ route('admin.schedules.calendar-events') }}?start=${info.startStr}&end=${info.endStr}`;
        
        if (kecamatanId) {
          url += `&kecamatan_id=${kecamatanId}`;
        }
        if (areaId) {
          url += `&area_id=${areaId}`;
        }

        fetch(url)
          .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(events => {
            successCallback(events);
          })
          .catch(error => {
            console.error('Error fetching events:', error);
            failureCallback(error);
          });
      },
      eventClick: function(info) {
        openEventModal(info.event);
      },
      eventDidMount: function(info) {
        // Add tooltip on hover
        const props = info.event.extendedProps;
        info.el.title = `${props.area_name}\n${props.day_label} ${props.pickup_time}\nCollector: ${props.collectors}`;
      },
      eventDisplay: 'block',
      dayMaxEvents: 3, // Limit events per day
      moreLinkText: function(num) {
        return '+' + num + ' lainnya';
      },
      moreLinkClick: 'popover',
      noEventsText: 'Tidak ada jadwal',
      loading: function(isLoading) {
        if (isLoading) {
          calendarEl.classList.add('opacity-50', 'pointer-events-none');
        } else {
          calendarEl.classList.remove('opacity-50', 'pointer-events-none');
        }
      }
    });

    calendar.render();

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeEventModal();
      }
    });
  });
</script>
@endpush
