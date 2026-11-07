@extends('layouts.admin')

@section('title', 'Edit RW')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit RW</h1>
        <a href="{{ route('admin.rws.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Edit RW</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.rws.update', $rw) }}" id="rwForm">
                        @csrf
                        @method('PUT')

                        <!-- Kecamatan -->
                        <div class="form-group">
                            <label for="kecamatan_id">Kecamatan <span class="text-danger">*</span></label>
                            <select name="kecamatan_id" id="kecamatan_id" 
                                    class="form-control @error('kecamatan_id') is-invalid @enderror" 
                                    required>
                                <option value="">Pilih Kecamatan</option>
                                @foreach($kecamatans as $kecamatan)
                                    <option value="{{ $kecamatan->id }}" 
                                        {{ (old('kecamatan_id', $rw->kelurahan->parent_id ?? null) == $kecamatan->id) ? 'selected' : '' }}>
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
                                <!-- Will be populated by JS -->
                            </select>
                            @error('kelurahan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- RW Name -->
                        <div class="form-group">
                            <label for="name">Nama RW <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $rw->name) }}" 
                                   placeholder="Contoh: RW 05"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Format: "RW XX" (contoh: RW 01, RW 05, RW 15)
                            </small>
                        </div>

                        <!-- Usage Info -->
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> Info Penggunaan:</strong><br>
                            RW ini digunakan oleh:
                            <ul class="mb-0 mt-2">
                                <li><strong>{{ $rw->service_accounts_count }}</strong> Service Account</li>
                                <li><strong>{{ $rw->collector_assignments_count }}</strong> Penugasan Collector</li>
                            </ul>
                            @if($rw->service_accounts_count > 0 || $rw->collector_assignments_count > 0)
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Perubahan kelurahan akan mempengaruhi data yang sudah ada.
                                </small>
                            @endif
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update
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
                        <i class="fas fa-info-circle"></i> Informasi RW
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="font-weight-bold">Nama RW:</td>
                            <td>{{ $rw->name }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Kelurahan:</td>
                            <td>{{ $rw->kelurahan->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Kecamatan:</td>
                            <td>{{ $rw->kelurahan->parent->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Dibuat:</td>
                            <td>{{ $rw->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Diupdate:</td>
                            <td>{{ $rw->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-exclamation-triangle"></i> Peringatan
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Sebelum mengubah:</strong></p>
                    <ul class="mb-0">
                        <li>Pastikan tidak ada service account aktif</li>
                        <li>Cek penugasan collector terkait</li>
                        <li>Perubahan kelurahan akan mempengaruhi data lain</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const selectedKelurahanId = '{{ old("kelurahan_id", $rw->kelurahan_id) }}';

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
                    if (kelurahan.id == selectedKelurahanId) {
                        option.selected = true;
                    }
                    kelurahanSelect.appendChild(option);
                });
            }
            
            kelurahanSelect.disabled = false;
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

    // Trigger kecamatan change on page load
    document.getElementById('kecamatan_id').dispatchEvent(new Event('change'));
</script>
@endpush
@endsection
