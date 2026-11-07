@extends('layouts.master')

@section('title', 'Tambah TPS')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
          <x-button as="a" href="{{ route('admin.garbage-dumps.index') }}" variant="secondary" size="sm">
            ← Kembali
          </x-button>
        </div>
        <h2 class="text-2xl font-bold text-black dark:text-white">Tambah TPS</h2>
        <p class="text-sm text-gray-500 mt-1">Isi form untuk menambahkan Tempat Pembuangan Sementara baru.</p>
      </div>

      <form action="{{ route('admin.garbage-dumps.store') }}" method="POST" class="space-y-6" x-data="tpsForm()">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Nama TPS -->
          <div class="md:col-span-2">
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nama TPS <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                   placeholder="Contoh: TPS Melati, TPS Pasar Minggu">
            @error('name')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Wilayah Utama Section -->
          <div class="md:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
              Wilayah Utama
            </h3>
          </div>

          <!-- Kecamatan (Wajib) -->
          <div>
            <label for="kecamatan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kecamatan <span class="text-red-500">*</span>
            </label>
            <select name="kecamatan_id" id="kecamatan_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('kecamatan_id') border-red-500 @enderror"
                    x-model="kecamatanId"
                    @change="await loadKelurahans(); updateMapLocation()">
              <option value="">Pilih Kecamatan</option>
              @foreach($kecamatans as $kecamatan)
                <option value="{{ $kecamatan->id }}" {{ old('kecamatan_id') == $kecamatan->id ? 'selected' : '' }}>
                  {{ $kecamatan->name }}
                </option>
              @endforeach
            </select>
            @error('kecamatan_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Kelurahan (Opsional) -->
          <div>
            <label for="kelurahan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kelurahan <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <select name="kelurahan_id" id="kelurahan_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('kelurahan_id') border-red-500 @enderror"
                    x-model="kelurahanId"
                    @change="updateMapLocation()"
                    :disabled="!kecamatanId || kelurahans.length === 0">
              <option value="">Pilih Kelurahan</option>
              <template x-for="kelurahan in kelurahans" :key="kelurahan.id">
                <option :value="kelurahan.id" x-text="kelurahan.name"></option>
              </template>
            </select>
            @error('kelurahan_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- RW (Opsional) -->
          <div>
            <label for="rw" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              RW <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <input type="text" name="rw" id="rw" value="{{ old('rw') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('rw') border-red-500 @enderror"
                   placeholder="Contoh: 1, 01, atau RW 01"
                   :disabled="!kelurahanId"
                   @blur="formatRW($event)">
            @error('rw')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Ketik nomor RW (contoh: 1 atau 01), akan otomatis diformat menjadi "RW 01"</p>
          </div>

          <!-- Lokasi Fisik Section -->
          <div class="md:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
              Lokasi Fisik
            </h3>
          </div>

          <!-- Alamat Lengkap -->
          <div class="md:col-span-2">
            <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Alamat Lengkap <span class="text-red-500">*</span>
            </label>
            <textarea name="address" id="address" rows="3" required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('address') border-red-500 @enderror"
                      placeholder="Masukkan alamat lengkap TPS"
                      @blur="updateMapLocation()">{{ old('address') }}</textarea>
            @error('address')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Koordinat -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Pilih Lokasi di Peta
            </label>
            <div id="map" class="w-full h-96 rounded-lg border border-gray-300 dark:border-gray-700 mb-4 z-0"></div>
            <p class="text-sm text-gray-500 mb-4">Klik pada peta untuk menandai lokasi TPS.</p>
          </div>

          <div>
            <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Latitude
            </label>
            <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude') }}"
                   x-model="latitude"
                   @input="updateMapFromInput()"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('latitude') border-red-500 @enderror"
                   placeholder="Contoh: -6.200000">
            @error('latitude')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Longitude
            </label>
            <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude') }}"
                   x-model="longitude"
                   @input="updateMapFromInput()"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('longitude') border-red-500 @enderror"
                   placeholder="Contoh: 106.816666">
            @error('longitude')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Get Current Location Button -->
          <div class="md:col-span-2">
            <button type="button" @click="getCurrentLocation()"
                    class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              Gunakan Lokasi Saat Ini
            </button>
            <span x-show="locationLoading" class="ml-2 text-sm text-gray-500">Mengambil lokasi...</span>
          </div>

          <!-- Kapasitas Section -->
          <div class="md:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
              Kapasitas <span class="text-gray-400 text-sm font-normal">(Indikator)</span>
            </h3>
            <p class="text-sm text-gray-500 mb-4">Angka kapasitas ini hanya sebagai label batas maksimal TPS.</p>
          </div>

          <!-- Kapasitas Volume -->
          <div>
            <label for="capacity_volume" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kapasitas Volume (m³)
            </label>
            <input type="number" step="0.1" min="0" name="capacity_volume" id="capacity_volume" value="{{ old('capacity_volume') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('capacity_volume') border-red-500 @enderror"
                   placeholder="Contoh: 50">
            @error('capacity_volume')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Kapasitas Berat -->
          <div>
            <label for="capacity_weight" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kapasitas Berat (kg)
            </label>
            <input type="number" step="1" min="0" name="capacity_weight" id="capacity_weight" value="{{ old('capacity_weight') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('capacity_weight') border-red-500 @enderror"
                   placeholder="Contoh: 5000">
            @error('capacity_weight')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Status -->
          <div class="md:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
              Status
            </h3>
          </div>

          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Status TPS <span class="text-red-500">*</span>
            </label>
            <select name="status" id="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('status') border-red-500 @enderror">
              <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktif</option>
              <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            @error('status')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Assign Collector Section -->
          <div class="md:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
              Assign Kolektor
            </h3>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kolektor yang Ditugaskan <span class="text-gray-400 text-xs">(Opsional, bisa pilih lebih dari satu)</span>
            </label>
            <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
              1 kolektor hanya bisa di-assign ke 1 TPS. Kolektor yang sudah di-assign ke TPS lain akan ditandai.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4 border border-gray-300 rounded-lg dark:border-gray-700 max-h-64 overflow-y-auto">
              @forelse($collectors as $collector)
                <label class="flex items-center space-x-3 p-2 rounded-lg {{ $collector->assigned_tps ? 'bg-gray-100 dark:bg-gray-800 opacity-60' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }} cursor-pointer">
                  <input type="checkbox" name="collector_ids[]" value="{{ $collector->id }}"
                         class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                         {{ in_array($collector->id, old('collector_ids', [])) ? 'checked' : '' }}
                         {{ $collector->assigned_tps ? 'disabled' : '' }}>
                  <div class="flex flex-col">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $collector->name }}</span>
                    @if($collector->assigned_tps)
                      <span class="text-xs text-red-500">Sudah di TPS: {{ $collector->assigned_tps }}</span>
                    @endif
                  </div>
                </label>
              @empty
                <p class="text-sm text-gray-500 col-span-3">Belum ada kolektor yang terdaftar.</p>
              @endforelse
            </div>
            @error('collector_ids')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Pilih kolektor yang akan ditugaskan untuk TPS ini.</p>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <x-button type="submit" variant="primary" size="md">Simpan</x-button>
          <x-button as="a" href="{{ route('admin.garbage-dumps.index') }}" variant="secondary" size="md">Batal</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('body:end')
<script>
  function tpsForm() {
    return {
      kecamatanId: '{{ old('kecamatan_id') }}',
      kelurahanId: '{{ old('kelurahan_id') }}',
      latitude: '{{ old('latitude') }}',
      longitude: '{{ old('longitude') }}',
      kelurahans: [],
      locationLoading: false,
      map: null,
      marker: null,

      async init() {
        if (this.kecamatanId) {
          await this.loadKelurahans();
        }
        this.initMap();
      },

      initMap() {
        // Default to Jakarta if no coordinates
        const defaultLat = -6.2088;
        const defaultLng = 106.8456;
        
        const lat = this.latitude ? parseFloat(this.latitude) : defaultLat;
        const lng = this.longitude ? parseFloat(this.longitude) : defaultLng;
        
        // Check if L is defined (Leaflet)
        if (typeof L === 'undefined') {
            console.error('Leaflet not loaded');
            return;
        }

        this.map = L.map('map').setView([lat, lng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(this.map);

        if (this.latitude && this.longitude) {
            this.addMarker(lat, lng);
        }

        this.map.on('click', async (e) => {
            const lat = e.latlng.lat.toFixed(8);
            const lng = e.latlng.lng.toFixed(8);
            this.latitude = lat;
            this.longitude = lng;
            this.addMarker(e.latlng.lat, e.latlng.lng);
            await this.getAddressFromCoordinates(lat, lng);
        });
        
        // Fix map size on load
        setTimeout(() => {
            this.map.invalidateSize();
        }, 100);
      },

      addMarker(lat, lng) {
        if (this.marker) {
            this.marker.setLatLng([lat, lng]);
        } else {
            this.marker = L.marker([lat, lng]).addTo(this.map);
        }
      },
      
      updateMapFromInput() {
         if (this.latitude && this.longitude) {
             const lat = parseFloat(this.latitude);
             const lng = parseFloat(this.longitude);
             if (!isNaN(lat) && !isNaN(lng)) {
                 this.addMarker(lat, lng);
                 this.map.setView([lat, lng], 13);
             }
         }
      },

      async updateMapLocation() {
        if (!this.map) return;

        let queryParts = [];
        
        // Address
        const address = document.getElementById('address').value;
        if (address) queryParts.push(address);

        // Kelurahan
        if (this.kelurahanId) {
            const kel = this.kelurahans.find(k => k.id == this.kelurahanId);
            if (kel) queryParts.push(kel.name);
        }

        // Kecamatan
        const kecSelect = document.getElementById('kecamatan_id');
        const kecName = kecSelect.options[kecSelect.selectedIndex]?.text;
        if (this.kecamatanId && kecName && kecName !== 'Pilih Kecamatan') {
            queryParts.push(kecName);
        }
        
        if (queryParts.length === 0) return;
        
        // Add Indonesia context
        queryParts.push('Indonesia');
        
        const query = queryParts.join(', ');
        
        try {
            // Use Nominatim API
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`);
            const data = await response.json();
            
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                this.map.flyTo([lat, lon], 16);
            }
        } catch (error) {
            console.error('Error geocoding location:', error);
        }
      },

      async getAddressFromCoordinates(lat, lng) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
            const data = await response.json();
            if (data && data.display_name) {
                document.getElementById('address').value = data.display_name;
            }
        } catch (error) {
            console.error('Error reverse geocoding:', error);
        }
      },

      async loadKelurahans() {
        this.kelurahans = [];
        this.kelurahanId = '';
        
        if (!this.kecamatanId) return;

        try {
          const response = await fetch(`{{ route('admin.garbage-dumps.kelurahans') }}?kecamatan_id=${this.kecamatanId}`);
          this.kelurahans = await response.json();
        } catch (error) {
          console.error('Error loading kelurahans:', error);
        }
      },

      formatRW(event) {
        const input = event.target;
        let value = input.value.trim();
        
        // If empty, do nothing
        if (!value) return;
        
        // If already has "RW " prefix, do nothing
        if (value.toUpperCase().startsWith('RW ')) return;
        
        // If it's purely numeric, format it
        if (!isNaN(value)) {
          const paddedValue = String(value).padStart(2, '0');
          input.value = `RW ${paddedValue}`;
        }
      },

      getCurrentLocation() {
        if (!navigator.geolocation) {
          alert('Geolocation tidak didukung oleh browser Anda.');
          return;
        }

        this.locationLoading = true;
        navigator.geolocation.getCurrentPosition(
          (position) => {
            this.latitude = position.coords.latitude.toFixed(8);
            this.longitude = position.coords.longitude.toFixed(8);
            this.locationLoading = false;
            this.updateMapFromInput();
          },
          (error) => {
            this.locationLoading = false;
            switch(error.code) {
              case error.PERMISSION_DENIED:
                alert('Izin lokasi ditolak.');
                break;
              case error.POSITION_UNAVAILABLE:
                alert('Informasi lokasi tidak tersedia.');
                break;
              case error.TIMEOUT:
                alert('Permintaan lokasi timeout.');
                break;
              default:
                alert('Terjadi kesalahan saat mengambil lokasi.');
            }
          }
        );
      }
    }
  }
</script>
@endpush
