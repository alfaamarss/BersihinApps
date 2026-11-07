@extends('layouts.master')

@section('title','Artikel')

@section('content')
@php
    $statusBadges = [
        'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        'published' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'archived' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
    ];
@endphp
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Manajemen Artikel</h2>
          <p class="text-sm text-gray-500 mt-1">Buat, kelola, dan arsipkan artikel informasi.</p>
        </div>
        <x-button as="a" href="{{ route('admin.articles.create') }}" variant="primary" size="md">Tambah Artikel</x-button>
      </div>

      <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="w-full md:w-[260px]">
          <x-form.input name="search" label="Cari Judul">{{ request('search') }}</x-form.input>
        </div>
        <div class="w-full md:w-[220px]">
          <x-form.select name="status" label="Status" :options="$statusOptions" placeholder="Semua Status" :value="request('status')" />
        </div>
        <div class="flex gap-2">
          <x-button type="submit" variant="secondary">Terapkan</x-button>
          <x-button as="a" href="{{ route('admin.articles.index') }}" variant="link">Reset</x-button>
        </div>
      </form>

      <x-table :headers="[
          ['label' => 'Gambar','class'=>'w-[100px]'],
          ['label' => 'Judul & Ringkasan','class'=>'min-w-[240px]'],
          ['label' => 'Status','class'=>'min-w-[120px]'],
          ['label' => 'Diperbarui','class'=>'min-w-[150px]'],
          ['label' => 'Penulis','class'=>'min-w-[140px]'],
          ['label' => 'Actions','class'=>'w-[140px] text-right']
      ]" empty="Belum ada artikel.">
        @foreach($articles as $article)
          <x-table.row>
            <x-table.cell class="align-top">
              @if($article->featured_image)
                <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="w-20 h-20 object-cover rounded-lg">
              @else
                <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                </div>
              @endif
            </x-table.cell>
            <x-table.cell class="align-top">
              <span class="block text-base font-semibold text-gray-900 dark:text-white">{{ $article->title }}</span>
              @if($article->summary)
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                  {!! \Illuminate\Support\Str::limit(strip_tags($article->summary), 120) !!}
                </div>
              @endif
              <span class="mt-1 block text-xs text-gray-400">Slug: {{ $article->slug }}</span>
            </x-table.cell>
            <x-table.cell muted>
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadges[$article->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                {{ ucfirst($article->status) }}
              </span>
            </x-table.cell>
            <x-table.cell muted>
              {{ optional($article->updated_at)->translatedFormat('d M Y H:i') ?? '—' }}
              @if($article->published_at)
                <span class="block text-xs text-gray-400">Publikasi: {{ $article->published_at->translatedFormat('d M Y H:i') }}</span>
              @endif
            </x-table.cell>
            <x-table.cell muted>{{ $article->author ?? '—' }}</x-table.cell>
            <x-table.cell class="text-right" muted>
              <div class="flex justify-end gap-2">
                <x-button as="a" href="{{ route('admin.articles.preview', $article) }}" variant="secondary" size="sm" title="Preview">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </x-button>
                <x-button as="a" href="{{ route('admin.articles.edit', $article) }}" variant="secondary" size="sm">Edit</x-button>
                <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <div class="mt-4">{{ $articles->links() }}</div>
    </div>
  </div>
</div>
@endsection

@push('body:end')
<script>
  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Hapus Artikel?',
        text: 'Data artikel akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
@endpush
