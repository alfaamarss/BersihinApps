@props([
  'variant' => 'primary', // primary, secondary, danger, link
  'type' => 'button',
  'size' => 'md', // sm, md
  'as' => 'button', // button or a
  'href' => null,
  'icon' => null,
])

@php
  $base = 'inline-flex items-center justify-center font-medium rounded-md transition focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-primary/50 disabled:opacity-60 disabled:cursor-not-allowed';
  $sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-5 py-2.5 text-sm',
  ];
  $variants = [
    // Use brand palette classes that already exist in compiled CSS (brand-500 / brand-600)
    'primary' => 'bg-brand-500 text-white hover:bg-brand-600',
    'secondary' => 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
    'danger' => 'bg-red-600 text-white hover:bg-red-500',
    'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600',
    'success' => 'bg-green-600 text-white hover:bg-green-500',
    'info' => 'bg-blue-600 text-white hover:bg-blue-500',
    'link' => 'text-primary hover:underline px-0 py-0',
  ];
  $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if($as === 'a')
  <a {{ $href ? 'href='.$href : '' }} {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
      <span class="mr-2">{!! $icon !!}</span>
    @endif
    {{ $slot }}
  </a>
@else
  <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
      <span class="mr-2">{!! $icon !!}</span>
    @endif
    {{ $slot }}
  </button>
@endif
