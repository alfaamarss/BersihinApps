@extends('layouts.master')

@section('title','Create Menu')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <h2 class="mb-6 text-2xl font-bold text-black dark:text-white">Create Menu</h2>

      @if($errors->any())
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-700/40 dark:bg-red-900/30 dark:text-red-200">
          <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('manage-menu.store') }}" method="POST" class="space-y-5">
        @csrf
        <x-form.input name="nama_menu" label="Nama Menu" required />
        <x-form.input name="url" label="URL" />
        <x-form.input name="icon" label="Icon (optional)" />
        @php($parentOptions = $menus->map(fn($m)=>['value'=>$m->id,'label'=>$m->nama_menu])->toArray())
        <x-form.select name="parent_id" label="Parent Menu" :options="$parentOptions" placeholder="-- Root --" />
        <div class="flex justify-end gap-3 pt-2">
          <x-button as="a" href="{{ route('manage-menu.index') }}" variant="secondary">Cancel</x-button>
          <x-button type="submit" variant="primary">Create</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
