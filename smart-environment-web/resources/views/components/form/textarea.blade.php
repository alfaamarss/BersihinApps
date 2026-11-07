@props([
  'label' => null,
  'name',
  'rows' => 4,
  'required' => false,
  'help' => null,
])
@php
  $base = 'w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:placeholder:text-gray-500';
@endphp
<div {{ $attributes->class(['space-y-1']) }}>
  @if($label)
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-black dark:text-white">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
  @endif
  <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" {{ $required ? 'required' : '' }} class="{{ $base }}">{{ old($name, $slot) }}</textarea>
  @error($name)
    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
  @enderror
  @if($help)
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $help }}</p>
  @endif
</div>
