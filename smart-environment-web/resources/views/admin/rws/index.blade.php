@extends('layouts.admin')

@section('title', 'Manajemen RW')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen RW (Rukun Warga)</h1>
        <a href="{{ route('admin.rws.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah RW
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.rws.index') }}" class="form-inline">
                <div class="form-group mr-3 mb-2">
                    <label for="kecamatan_id" class="mr-2">Kecamatan:</label>
                    <select name="kecamatan_id" id="kecamatan_id" class="form-control">
                        <option value="">Semua Kecamatan</option>
                        @foreach($kecamatans as $kecamatan)
                            <option value="{{ $kecamatan->id }}" {{ request('kecamatan_id') == $kecamatan->id ? 'selected' : '' }}>
                                {{ $kecamatan->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mr-3 mb-2">
                    <label for="kelurahan_id" class="mr-2">Kelurahan:</label>
                    <select name="kelurahan_id" id="kelurahan_id" class="form-control">
                        <option value="">Semua Kelurahan</option>
                        @foreach($kelurahans as $kelurahan)
                            <option value="{{ $kelurahan->id }}" {{ request('kelurahan_id') == $kelurahan->id ? 'selected' : '' }}>
                                {{ $kelurahan->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mr-3 mb-2">
                    <label for="search" class="mr-2">Cari:</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Cari RW..." value="{{ request('search') }}">
                </div>

                <button type="submit" class="btn btn-primary mr-2 mb-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="{{ route('admin.rws.index') }}" class="btn btn-secondary mb-2">
                    <i class="fas fa-sync"></i> Reset
                </a>
            </form>
        </div>
    </div>

    <!-- RW List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Daftar RW
                <span class="badge badge-primary ml-2">{{ $rws->total() }}</span>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">RW</th>
                            <th width="25%">Kelurahan</th>
                            <th width="20%">Kecamatan</th>
                            <th width="15%">Service Accounts</th>
                            <th width="10%">Penugasan</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rws as $index => $rw)
                            <tr>
                                <td>{{ $rws->firstItem() + $index }}</td>
                                <td>
                                    <span class="badge badge-info badge-lg">{{ $rw->name }}</span>
                                </td>
                                <td>{{ $rw->kelurahan->name ?? '-' }}</td>
                                <td>{{ $rw->kelurahan->parent->name ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $rw->service_accounts_count > 0 ? 'success' : 'secondary' }}">
                                        {{ $rw->service_accounts_count }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $rw->collector_assignments_count > 0 ? 'success' : 'secondary' }}">
                                        {{ $rw->collector_assignments_count }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.rws.edit', $rw) }}" 
                                           class="btn btn-sm btn-warning" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.rws.destroy', $rw) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus RW ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Tidak ada data RW
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Menampilkan {{ $rws->firstItem() ?? 0 }} - {{ $rws->lastItem() ?? 0 }} dari {{ $rws->total() }} data
                </div>
                <div>
                    {{ $rws->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-filter when kecamatan changes
    document.getElementById('kecamatan_id').addEventListener('change', function() {
        const kecamatanId = this.value;
        const kelurahanSelect = document.getElementById('kelurahan_id');
        
        if (!kecamatanId) {
            return;
        }

        // Auto-submit form to refresh kelurahans
        this.form.submit();
    });
</script>
@endpush
@endsection
