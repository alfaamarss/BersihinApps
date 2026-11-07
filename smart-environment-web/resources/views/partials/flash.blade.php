@if (session('success') || session('error') || session('status'))
    <div class="space-y-3 mb-6">
        @foreach (['success' => 'green', 'status' => 'blue', 'error' => 'red'] as $key => $color)
            @if (session($key))
                <div x-data="{show:true}" x-show="show" x-transition class="flex items-start justify-between gap-4 rounded-md border border-{{$color}}-200 bg-{{$color}}-50 px-4 py-3 text-sm text-{{$color}}-700 dark:border-{{$color}}-700/40 dark:bg-{{$color}}-900/40 dark:text-{{$color}}-200">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-{{$color}}-100 text-{{$color}}-600 dark:bg-{{$color}}-800/60 dark:text-{{$color}}-300">
                            @switch($key)
                                @case('success')
                                    ✓
                                    @break
                                @case('status')
                                    ℹ
                                    @break
                                @case('error')
                                    !
                                    @break
                            @endswitch
                        </span>
                        <div class="font-medium first-letter:uppercase">{{ session($key) }}</div>
                    </div>
                    <button @click="show=false" type="button" class="text-{{$color}}-500 hover:text-{{$color}}-700 dark:text-{{$color}}-300 dark:hover:text-{{$color}}-100 focus:outline-none">✕</button>
                </div>
            @endif
        @endforeach
    </div>
@endif
