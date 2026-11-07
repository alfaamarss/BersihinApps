@extends('layouts.master')

@section('title', 'Preview Artikel')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12 lg:col-span-10 lg:col-start-2 xl:col-span-8 xl:col-start-3">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <!-- Header Actions -->
      <div class="border-b border-gray-200 dark:border-gray-800 p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Preview Artikel</h2>
            <p class="text-sm text-gray-500 mt-1">Tampilan artikel seperti yang akan dilihat oleh pengguna</p>
          </div>
          <div class="flex gap-2">
            <x-button as="a" href="{{ route('admin.articles.edit', $article) }}" variant="secondary" size="sm">
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Edit
            </x-button>
            <x-button as="a" href="{{ route('admin.articles.index') }}" variant="secondary" size="sm">
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Kembali
            </x-button>
          </div>
        </div>
      </div>

      <!-- Status Badge -->
      @php
          $statusConfig = [
              'draft' => ['bg' => 'bg-gray-100 dark:bg-gray-800', 'text' => 'text-gray-700 dark:text-gray-300', 'label' => 'Draft'],
              'published' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-300', 'label' => 'Published'],
              'archived' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-300', 'label' => 'Archived'],
          ];
          $status = $statusConfig[$article->status] ?? $statusConfig['draft'];
      @endphp
      
      <div class="px-6 pt-6">
        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $status['bg'] }} {{ $status['text'] }}">
          {{ $status['label'] }}
        </span>
      </div>

      <!-- Article Content -->
      <article class="p-6 md:p-8">
        <!-- Featured Image -->
        @if($article->featured_image)
          <div class="mb-8 -mx-6 md:-mx-8">
            <img 
              src="{{ $article->featured_image_url }}" 
              alt="{{ $article->title }}"
              class="w-full h-auto max-h-[500px] object-cover"
            >
          </div>
        @endif

        <!-- Article Header -->
        <header class="mb-8">
          <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">
            {{ $article->title }}
          </h1>

          <!-- Meta Information -->
          <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600 dark:text-gray-400">
            @if($article->author)
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>{{ $article->author }}</span>
              </div>
            @endif

            @if($article->published_at)
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <time datetime="{{ $article->published_at->toIso8601String() }}">
                  {{ $article->published_at->translatedFormat('d F Y') }}
                </time>
              </div>
            @endif

            <div class="flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>{{ $article->estimated_reading_time ?? '5' }} menit baca</span>
            </div>
          </div>
        </header>

        <!-- Summary -->
        @if($article->summary)
          <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-r-lg">
            <div class="text-gray-700 dark:text-gray-300 leading-relaxed">
              {!! $article->summary !!}
            </div>
          </div>
        @endif

        <!-- Article Content -->
        <div class="prose prose-lg dark:prose-invert max-w-none">
          <div class="article-content text-gray-800 dark:text-gray-200 leading-relaxed">
            {!! $article->content !!}
          </div>
        </div>

        <!-- Article Footer -->
        <footer class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-800">
          <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div>
              Terakhir diperbarui: {{ $article->updated_at->translatedFormat('d F Y H:i') }}
            </div>
          </div>
        </footer>
      </article>
    </div>
  </div>
</div>

<style>
  /* Article Content Styling */
  .article-content {
    font-size: 1.125rem;
    line-height: 1.8;
  }

  .article-content h1,
  .article-content h2,
  .article-content h3,
  .article-content h4,
  .article-content h5,
  .article-content h6 {
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: inherit;
  }

  .article-content h1 { font-size: 2rem; }
  .article-content h2 { font-size: 1.75rem; }
  .article-content h3 { font-size: 1.5rem; }
  .article-content h4 { font-size: 1.25rem; }
  .article-content h5 { font-size: 1.125rem; }
  .article-content h6 { font-size: 1rem; }

  .article-content p {
    margin-bottom: 1.5rem;
  }

  .article-content ul,
  .article-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
  }

  .article-content ul {
    list-style-type: disc;
  }

  .article-content ol {
    list-style-type: decimal;
  }

  .article-content li {
    margin-bottom: 0.5rem;
  }

  .article-content a {
    color: #3b82f6;
    text-decoration: underline;
  }

  .article-content a:hover {
    color: #2563eb;
  }

  .article-content strong {
    font-weight: 700;
  }

  .article-content em {
    font-style: italic;
  }

  .article-content blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1.5rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #6b7280;
  }

  .dark .article-content blockquote {
    border-left-color: #374151;
    color: #9ca3af;
  }

  .article-content code {
    background-color: #f3f4f6;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.9em;
    font-family: 'Courier New', monospace;
  }

  .dark .article-content code {
    background-color: #1f2937;
  }

  .article-content pre {
    background-color: #f3f4f6;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1.5rem 0;
  }

  .dark .article-content pre {
    background-color: #1f2937;
  }

  .article-content pre code {
    background-color: transparent;
    padding: 0;
  }

  .article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5rem 0;
  }

  .article-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
  }

  .article-content table th,
  .article-content table td {
    border: 1px solid #e5e7eb;
    padding: 0.75rem;
    text-align: left;
  }

  .dark .article-content table th,
  .dark .article-content table td {
    border-color: #374151;
  }

  .article-content table th {
    background-color: #f3f4f6;
    font-weight: 600;
  }

  .dark .article-content table th {
    background-color: #1f2937;
  }

  .article-content hr {
    border: 0;
    border-top: 1px solid #e5e7eb;
    margin: 2rem 0;
  }

  .dark .article-content hr {
    border-top-color: #374151;
  }
</style>
@endsection
