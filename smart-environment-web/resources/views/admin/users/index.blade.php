@extends('layouts.master')

@section('title','Users')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
    <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-black dark:text-white">Users Management</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola akun & role pengguna.</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-button as="a" href="{{ route('manage-user.create') }}" variant="primary" size="md">Add User</x-button>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="mb-4">
                <form method="GET" action="{{ route('manage-user.index') }}" class="flex flex-wrap gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari nama atau email..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    </div>
                    <div class="min-w-[150px]">
                        <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="">Semua Role</option>
                            @foreach($roles ?? [] as $role)
                                <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[150px]">
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="">Semua Status</option>
                            <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                            <option value="unverified" {{ request('status') == 'unverified' ? 'selected' : '' }}>Unverified</option>
                        </select>
                    </div>
                    <div class="min-w-[150px]">
                        <select name="deleted" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="">Semua User</option>
                            <option value="active" {{ request('deleted') == 'active' ? 'selected' : '' }}>Aktif Saja</option>
                            <option value="only" {{ request('deleted') == 'only' ? 'selected' : '' }}>Dihapus Saja</option>
                        </select>
                    </div>
                    <x-button type="submit" variant="secondary" size="md">Filter</x-button>
                    @if(request()->hasAny(['search', 'role', 'status', 'deleted']))
                        <x-button as="a" href="{{ route('manage-user.index') }}" variant="secondary" size="md">Reset</x-button>
                    @endif
                </form>
            </div>

            <x-table :headers="[
                ['label' => 'Name','class'=>'min-w-[150px]'],
                ['label' => 'Email','class'=>'min-w-[150px]'],
                ['label' => 'Roles','class'=>'min-w-[120px]'],
                ['label' => 'Status','class'=>'min-w-[100px]'],
                ['label' => 'Actions']
            ]" empty="No users found.">
                @foreach($users as $user)
                    <x-table.row class="{{ $user->trashed() ? 'opacity-50 bg-gray-50 dark:bg-gray-800/50' : '' }}">
                        <x-table.cell>
                            <div class="flex items-center gap-2">
                                {{ $user->name }}
                                @if($user->trashed())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Deleted
                                    </span>
                                @endif
                            </div>
                        </x-table.cell>
                        <x-table.cell muted>{{ $user->email }}</x-table.cell>
                        <x-table.cell muted>
                            @if($user->getRoleNames()->isNotEmpty())
                                @foreach($user->getRoleNames() as $role)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mr-1">
                                        {{ $role }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-gray-400 text-sm">No role</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            @if($user->email_verified_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Verified
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    Unverified
                                </span>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($user->trashed())
                                    <!-- Restore button for deleted users -->
                                    <form action="{{ route('manage-user.restore', $user->id) }}" method="POST" class="restore-form">
                                        @csrf
                                        <x-button type="submit" variant="secondary" size="sm" class="dark:border-green-600 dark:hover:border-green-500">Restore</x-button>
                                    </form>
                                @else
                                    <!-- Edit and Delete buttons for active users -->
                                    <x-button as="a" href="{{ route('manage-user.edit', $user->id) }}" variant="secondary" size="sm" class="dark:border-gray-600 dark:hover:border-gray-500">Edit</x-button>
                                    <form action="{{ route('manage-user.destroy', $user->id) }}" method="POST" class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm" class="dark:bg-red-600 dark:hover:bg-red-500">Delete</x-button>
                                    </form>
                                @endif
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforeach
            </x-table>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</div>
@endsection

@push('body:end')
<script>
  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Hapus Pengguna?',
        text: 'Pengguna akan dinonaktifkan dari sistem!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('.restore-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Restore Pengguna?',
        text: 'Pengguna akan diaktifkan kembali!',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Restore!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
@endpush
