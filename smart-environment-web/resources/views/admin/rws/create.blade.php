@extends('layouts.admin')

@section('title', 'Tambah RW')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah RW Baru</h1>
        <a href="{{ route('admin.rws.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Tambah RW</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.rws.store') }}" id="rwForm">
                        @csrf

                        <!-- Kecamatan -->
                        <div class="form-group">
                            <label for="kecamatan_id">Kecamatan <span class="text-danger">*</span></label>
                            <select name="kecamatan_id" id="kecamatan_id" 
                                    class="form-control @error('kecamatan_id') is-invalid @enderror" 
                                    required>
                                <option value="">Pilih Kecamatan</option>
                                @foreach($kecamatans as $kecamatan)
                                    <option value="{{ $kecamatan->id }}" {{ old('kecamatan_id') == $kecamatan->id ? 'selected' : '' }}>
                                        {{ $kecamatan->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('kecamatan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kelurahan -->
                        <div class="form-group">
                            <label for="kelurahan_id">Kelurahan <span class="text-danger">*</span></label>
                            <select name="kelurahan_id" id="kelurahan_id" 
                                    class="form-control @error('kelurahan_id') is-invalid @enderror" 
                                    required>
                                <option value="">Pilih Kelurahan</option>
                            </select>
                            @error('kelurahan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Pilih kecamatan terlebih dahulu
                            </small>
                        </div>

                        <!-- RW Name -->
                        <div class="form-group">
                            <label for="name">Nama RW <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" 
                                   placeholder="Contoh: RW 05"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Format: "RW XX" (contoh: RW 01, RW 05, RW 15). Sistem akan otomatis menambahkan zero-padding.
                            </small>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('admin.rws.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle"></i> Panduan
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">Format RW yang Benar:</h6>
                    <ul class="mb-3">
                        <li><code>RW 01</code> ✅</li>
                        <li><code>RW 05</code> ✅</li>
                        <li><code>RW 15</code> ✅</li>
                        <li><code>rw 5</code> ✅ (akan dinormalisasi menjadi RW 05)</li>
                    </ul>

                    <h6 class="font-weight-bold">Format Salah:</h6>
                    <ul class="mb-3">
                        <li><code>RW1</code> ❌ (tanpa spasi)</li>
                        <li><code>RT 01</code> ❌ (bukan RW)</li>
                        <li><code>05</code> ❌ (tanpa prefix RW)</li>
                    </ul>

                    <hr>

                    <h6 class="font-weight-bold">Catatan:</h6>
                    <ul class="mb-0">
                        <li>Setiap RW harus unik per kelurahan</li>
                        <li>RW akan digunakan untuk penugasan collector</li>
                        <li>RW bisa juga dibuat otomatis saat user mendaftar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Load kelurahans when kecamatan changes
    document.getElementById('kecamatan_id').addEventListener('change', function() {
        const kecamatanId = this.value;
        const kelurahanSelect = document.getElementById('kelurahan_id');
        
        // Clear options
        kelurahanSelect.innerHTML = '<option value="">Loading...</option>';
        kelurahanSelect.disabled = true;

        if (!kecamatanId) {
            kelurahanSelect.innerHTML = '<option value="">Pilih Kelurahan</option>';
            return;
        }

        // Fetch kelurahans
        fetch(`/api/v1/areas?level=Kelurahan&parent_id=${kecamatanId}`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer {{ auth()->user()->createToken("admin")->plainTextToken ?? "" }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            kelurahanSelect.innerHTML = '<option value="">Pilih Kelurahan</option>';
            
            if (data.data && data.data.items) {
                data.data.items.forEach(kelurahan => {
                    const option = document.createElement('option');
                    option.value = kelurahan.id;
                    option.textContent = kelurahan.name;
                    kelurahanSelect.appendChild(option);
                });
            }
            
            kelurahanSelect.disabled = false;

            // Restore old value if exists
            const oldValue = '{{ old("kelurahan_id") }}';
            if (oldValue) {
                kelurahanSelect.value = oldValue;
            }
        })
        .catch(error => {
            console.error('Error loading kelurahans:', error);
            kelurahanSelect.innerHTML = '<option value="">Error loading data</option>';
            kelurahanSelect.disabled = false;
        });
    });

    // Auto-format RW name
    document.getElementById('name').addEventListener('blur', function() {
        let value = this.value.trim().toUpperCase();
        
        // If user just typed number, add RW prefix
        if (/^\d+$/.test(value)) {
            value = 'RW ' + value;
        }
        
        // Normalize spacing
        value = value.replace(/\s+/g, ' ');
        
        this.value = value;
    });

    // Trigger kecamatan change if there's old value
    @if(old('kecamatan_id'))
        document.getElementById('kecamatan_id').dispatchEvent(new Event('change'));
    @endif
</script>
@endpush
@endsection
