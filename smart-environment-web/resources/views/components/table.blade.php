@props([
  'headers' => [], // array of [label, class?]
  'empty' => 'No data.',
  'striped' => true,
  'hover' => true,
])

@php
  $colCount = count($headers);
@endphp

<div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
  <table class="w-full text-sm table-auto">
    <thead class="bg-gray-100 dark:bg-white/[0.03]">
      <tr class="text-left">
        @foreach($headers as $h)
          @php
            $text = is_array($h) ? ($h['label'] ?? $h[0] ?? '') : $h;
            $cls  = is_array($h) ? ($h['class'] ?? '') : '';
          @endphp
          <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200 {{ $cls }}">{!! $text !!}</th>
        @endforeach
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
      @if(trim($slot) === '')
        <tr>
          <td colspan="{{ $colCount }}" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{!! $empty !!}</td>
        </tr>
      @else
        {!! $slot !!}
      @endif
    </tbody>
  </table>
</div>
