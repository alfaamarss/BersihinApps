@extends('layouts.master')

@section('title','Edit Menu')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <h2 class="mb-6 text-2xl font-bold text-black dark:text-white">Edit Menu</h2>

      @if($errors->any())
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-700/40 dark:bg-red-900/30 dark:text-red-200">
          <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('manage-menu.update', $menu->id) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')
        <x-form.input name="nama_menu" label="Nama Menu" :value="$menu->nama_menu" required />
        <x-form.input name="url" label="URL" :value="$menu->url" />
        <x-form.input name="icon" label="Icon (optional)" :value="$menu->icon" />
        @php($parentOptions = $menus->map(fn($m)=>['value'=>$m->id,'label'=>$m->nama_menu])->toArray())
        <x-form.select name="parent_id" label="Parent Menu" :options="$parentOptions" placeholder="-- Root --" :value="old('parent_id',$menu->parent_id)" />
        <div class="flex justify-end gap-3 pt-2">
          <x-button as="a" href="{{ route('manage-menu.index') }}" variant="secondary">Cancel</x-button>
          <x-button type="submit" variant="primary">Update</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
