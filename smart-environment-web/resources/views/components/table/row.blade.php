@props([
  'as' => 'tr',
  'class' => ''
])
@php
  $tag = $as;
  $base = 'bg-white '.
          'dark:bg-white/[0.03] '.
          'even:bg-gray-50 dark:even:bg-white/[0.02] '.
          'transition-colors '.
          'hover:bg-gray-100 dark:hover:bg-white/[0.05]';
@endphp
<{{ $tag }} {{ $attributes->merge(['class' => $base.' '.$class]) }}>
  {!! $slot !!}
</{{ $tag }}>
