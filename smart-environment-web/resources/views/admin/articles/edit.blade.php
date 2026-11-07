@extends('layouts.master')

@section('title','Edit Artikel')

@section('content')
@php
    $publishedValue = $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : null;
@endphp
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12 lg:col-span-9 xl:col-span-8">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Artikel</h2>
          <p class="text-sm text-gray-500 mt-1">Perbarui konten artikel berikut dan atur status publikasinya.</p>
        </div>
        <div class="flex gap-2">
          <x-button as="a" href="{{ route('admin.articles.preview', $article) }}" variant="secondary" size="sm">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            Preview
          </x-button>
          <x-button as="a" href="{{ route('admin.articles.index') }}" variant="secondary" size="sm">Kembali</x-button>
        </div>
      </div>

      <form action="{{ route('admin.articles.update', $article) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')
        <x-form.input name="title" label="Judul" required>{{ old('title', $article->title) }}</x-form.input>
        <x-form.input name="slug" label="Slug (opsional)" help="Biarkan kosong untuk dibuat otomatis.">{{ old('slug', $article->slug) }}</x-form.input>
        
        <x-form.input 
          name="author" 
          label="Penulis" 
          placeholder="Masukkan nama penulis..."
          help="Nama penulis artikel."
        >{{ old('author', $article->author) }}</x-form.input>
        
        <x-form.richtext 
          name="summary" 
          label="Ringkasan" 
          editorType="summary"
          placeholder="Tulis ringkasan artikel (opsional)..."
          help="Opsional, maksimal beberapa kalimat."
        >{{ old('summary', $article->summary) }}</x-form.richtext>
        <x-form.richtext 
          name="content" 
          label="Konten" 
          editorType="full"
          placeholder="Tulis konten artikel di sini..."
          required
        >{{ old('content', $article->content) }}</x-form.richtext>

        <div x-data="{ preview: null, currentImage: {{ $article->featured_image ? 'true' : 'false' }}, removeImage: false }">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gambar Utama</label>

          @if($article->featured_image)
            <div x-show="currentImage && !removeImage" class="mb-4">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gambar saat ini:</p>
              <img src="{{ $article->featured_image_url }}" alt="Current image" class="max-w-xs rounded-lg shadow-md mb-2">
              <button
                type="button"
                @click="removeImage = true; currentImage = false"
                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400"
              >
                Hapus gambar
              </button>
            </div>
            <input type="hidden" name="remove_image" x-bind:value="removeImage ? '1' : '0'">
          @endif

          <input
            type="file"
            name="featured_image"
            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
            @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null; removeImage = false"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-300"
          >
          <p class="mt-1 text-xs text-gray-500">Format: JPEG, PNG, JPG, GIF, WEBP. Maksimal 2MB.</p>

          <div x-show="preview" x-cloak class="mt-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview gambar baru:</p>
            <img :src="preview" alt="Preview" class="max-w-xs rounded-lg shadow-md">
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <x-form.select name="status" label="Status" :options="$statusOptions" :value="old('status', $article->status)" />
          <x-form.input type="datetime-local" name="published_at" label="Tanggal Publikasi (opsional)">{{ old('published_at', $publishedValue) }}</x-form.input>
        </div>
        <div class="flex justify-end gap-2">
          <x-button as="a" href="{{ route('admin.articles.index') }}" variant="secondary" size="md">Batal</x-button>
          <x-button type="submit" variant="primary" size="md">Simpan Perubahan</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
