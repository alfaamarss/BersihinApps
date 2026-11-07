@props([
  'label' => null,
  'name',
  'required' => false,
  'options' => [], // [['value'=>'','label'=>'']]
  'placeholder' => null,
  'help' => null,
])
@php
  $base = 'w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 pr-10 text-sm text-gray-800 focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
  $value = old($name, $attributes->get('value'));
  $normalized = collect($options)->map(function ($opt, $key) {
      if (is_array($opt) && array_key_exists('value', $opt) && array_key_exists('label', $opt)) {
          return $opt;
      }

      if (is_array($opt)) {
          $val = $opt['value'] ?? $key;
          $label = $opt['label'] ?? ($opt['text'] ?? $opt['name'] ?? $val);
          return ['value' => $val, 'label' => $label];
      }

      $val = is_string($key) ? $key : $opt;
      return ['value' => $val, 'label' => $opt];
  });
@endphp
<div {{ $attributes->class(['space-y-1']) }}>
  @if($label)
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-black dark:text-white">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
  @endif
  <select id="{{ $name }}" name="{{ $name }}" {{ $required ? 'required' : '' }} class="{{ $base }}">
    @if($placeholder)
      <option value="">{{ $placeholder }}</option>
    @endif
    @foreach($normalized as $opt)
      <option value="{{ $opt['value'] }}" {{ (string)$value === (string)$opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
    @endforeach
    {{ $slot }}
  </select>
  @if($help)
    <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
  @endif
  @error($name)
    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
  @enderror
</div>
