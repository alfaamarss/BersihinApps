@extends('layouts.master')

@section('title','Tambah Pengaduan')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12 lg:col-span-9 xl:col-span-8">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Tambah Pengaduan</h2>
          <p class="text-sm text-gray-500 mt-1">Tambah pengaduan baru dari warga</p>
        </div>
        <x-button as="a" href="{{ route('admin.complaints.index') }}" variant="secondary" size="sm">Kembali</x-button>
      </div>

      <form action="{{ route('admin.complaints.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        
        <x-form.select 
          name="user_id" 
          label="Pelapor" 
          :options="$users->map(fn($u) => ['value' => $u->id, 'label' => $u->name . ' (' . $u->email . ')'])->toArray()" 
          :value="old('user_id')"
          placeholder="Pilih Pelapor"
          required
        />

        <x-form.select 
          name="service_account_id" 
          label="Akun Layanan (Opsional)" 
          :options="$serviceAccounts->map(fn($sa) => ['value' => $sa->id, 'label' => $sa->account_number . ' - ' . ($sa->area->name ?? 'N/A')])->toArray()" 
          :value="old('service_account_id')"
          placeholder="Pilih Akun Layanan"
          help="Opsional, pilih jika pengaduan terkait akun layanan tertentu"
        />

        <x-form.select 
          name="assignee_id" 
          label="Kolektor yang Ditugaskan (Opsional)" 
          :options="$assignees->map(fn($a) => ['value' => $a->id, 'label' => $a->name])->toArray()" 
          :value="old('assignee_id')"
          placeholder="Pilih Kolektor"
          help="Opsional, pilih kolektor yang akan menangani pengaduan"
        />

        <x-form.select 
          name="type" 
          label="Tipe Pengaduan" 
          :options="$typeOptions" 
          :value="old('type')"
          placeholder="Pilih Tipe"
          required
        />

        <x-form.textarea 
          name="description" 
          label="Deskripsi Pengaduan" 
          rows="5"
          placeholder="Jelaskan detail pengaduan..."
          required
        >{{ old('description') }}</x-form.textarea>

        <x-form.select 
          name="status" 
          label="Status" 
          :options="$statusOptions" 
          :value="old('status', 'open')"
          required
        />

        <div x-data="{ previews: [] }">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Foto Bukti (Opsional)</label>
          <input
            type="file"
            name="photos[]"
            accept="image/jpeg,image/png,image/jpg"
            multiple
            @change="
              previews = [];
              Array.from($event.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => previews.push(e.target.result);
                reader.readAsDataURL(file);
              });
            "
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-300"
          >
          <p class="mt-1 text-xs text-gray-500">Format: JPEG, PNG, JPG. Maksimal 2MB per file. Bisa upload multiple.</p>

          <div x-show="previews.length > 0" class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
            <template x-for="(preview, index) in previews" :key="index">
              <div class="relative">
                <img :src="preview" alt="Preview" class="w-full h-32 object-cover rounded-lg shadow-md">
                <span x-text="'Foto ' + (index + 1)" class="absolute bottom-2 left-2 bg-black/50 text-white text-xs px-2 py-1 rounded"></span>
              </div>
            </template>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <x-button as="a" href="{{ route('admin.complaints.index') }}" variant="secondary" size="md">Batal</x-button>
          <x-button type="submit" variant="primary" size="md">Simpan Pengaduan</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
