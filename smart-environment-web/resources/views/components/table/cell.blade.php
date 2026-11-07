@props([
  'tag' => 'td',
  'alignTop' => false,
  'muted' => false,
  'class' => ''
])
@php
  $classes = 'px-4 py-4';
  if($alignTop) $classes .= ' align-top';
  $textClass = $muted ? 'text-gray-600 dark:text-gray-300' : 'font-medium text-gray-800 dark:text-gray-100';
@endphp
<{{ $tag ?? 'td' }} {{ $attributes->merge(['class' => $classes.' '.$class]) }}>
  <span class="{{ $textClass }}">{!! $slot !!}</span>
</{{ $tag ?? 'td' }}>
