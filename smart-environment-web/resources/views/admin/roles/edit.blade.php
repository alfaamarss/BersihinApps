@extends('layouts.master')

@section('title','Edit Role')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <h2 class="mb-6 text-2xl font-bold text-black dark:text-white">Edit Role</h2>

      @if($errors->any())
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-700/40 dark:bg-red-900/30 dark:text-red-200">
          <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('manage-role.update', $role->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        <x-form.input name="name" label="Role Name" :value="$role->name" required />
        <div class="space-y-3">
          <p class="text-sm font-medium text-black dark:text-white">Grant Menus</p>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @php($selectedMenus = $getmenus ?? [])
            @foreach($menus as $menu)
              <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                <x-form.checkbox name="menu_id[]" :value="$menu->id" :checked="in_array($menu->id, $selectedMenus)" label="{{ $menu->nama_menu }}" />
                @if($menu->submenus && $menu->submenus->count())
                  <div class="mt-2 space-y-1 pl-6">
                    @foreach($menu->submenus as $sm)
                      <x-form.checkbox name="menu_id[]" :value="$sm->id" :checked="in_array($sm->id, $selectedMenus)" label="{{ $sm->nama_menu }}" class="text-sm" />
                    @endforeach
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        </div>
        @if(isset($permissions) && $permissions->count())
          <div class="space-y-1">
            <p class="text-sm font-medium text-black dark:text-white">Assigned Permissions</p>
            <ul class="list-disc pl-6 text-xs text-gray-700 dark:text-gray-300">
              @foreach($permissions as $p)
                <li>{{ $p->name }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <div class="flex justify-end gap-3 pt-2">
          <x-button as="a" href="{{ route('manage-role.index') }}" variant="secondary">Cancel</x-button>
          <x-button type="submit" variant="primary">Update</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
