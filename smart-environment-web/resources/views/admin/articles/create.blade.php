@extends('layouts.master')

@section('title','Tambah Artikel')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12 lg:col-span-9 xl:col-span-8">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Tambah Artikel Baru</h2>
          <p class="text-sm text-gray-500 mt-1">Isi konten artikel dan atur status publikasinya.</p>
        </div>
        <x-button as="a" href="{{ route('admin.articles.index') }}" variant="secondary" size="sm">Kembali</x-button>
      </div>

      <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <x-form.input name="title" label="Judul" required>{{ old('title') }}</x-form.input>
        <x-form.input name="slug" label="Slug (opsional)" help="Biarkan kosong untuk dibuat otomatis.">{{ old('slug') }}</x-form.input>
        
        <x-form.input 
          name="author" 
          label="Penulis" 
          placeholder="Masukkan nama penulis..."
          help="Biarkan kosong untuk menggunakan nama Anda sebagai penulis."
        >{{ old('author', auth()->user()->name) }}</x-form.input>
        
        <x-form.richtext 
          name="summary" 
          label="Ringkasan" 
          editorType="summary"
          placeholder="Tulis ringkasan artikel (opsional)..."
          help="Opsional, maksimal beberapa kalimat."
        >{{ old('summary') }}</x-form.richtext>
        <x-form.richtext 
          name="content" 
          label="Konten" 
          editorType="full"
          placeholder="Tulis konten artikel di sini..."
          required
        >{{ old('content') }}</x-form.richtext>

        <div x-data="{ preview: null }">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gambar Utama (opsional)</label>
          <input
            type="file"
            name="featured_image"
            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
            @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-300"
          >
          <p class="mt-1 text-xs text-gray-500">Format: JPEG, PNG, JPG, GIF, WEBP. Maksimal 2MB.</p>

          <div x-show="preview" x-cloak class="mt-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview:</p>
            <img :src="preview" alt="Preview" class="max-w-xs rounded-lg shadow-md">
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <x-form.select name="status" label="Status" :options="$statusOptions" :value="old('status','draft')" />
          <x-form.input type="datetime-local" name="published_at" label="Tanggal Publikasi (opsional)">{{ old('published_at') }}</x-form.input>
        </div>
        <div class="flex justify-end gap-2">
          <x-button as="a" href="{{ route('admin.articles.index') }}" variant="secondary" size="md">Batal</x-button>
          <x-button type="submit" variant="primary" size="md">Simpan Artikel</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
