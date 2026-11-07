@extends('layouts.master')

@section('title','Roles')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Roles Management</h2>
          <p class="text-sm text-gray-500 mt-1">Definisikan & atur hak akses.</p>
        </div>
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('manage-role.create') }}" variant="primary">Add Role</x-button>
        </div>
      </div>

      {{-- Flash messages now handled globally in layout --}}

      <x-table :headers="[
        ['label'=>'Name','class'=>'min-w-[200px]'],
        ['label'=>'Actions']
      ]" empty="No roles found.">
        @foreach($roles as $role)
          <x-table.row>
            <x-table.cell>{{ $role->name }}</x-table.cell>
            <x-table.cell>
              <div class="flex items-center gap-2">
                <x-button as="a" href="{{ route('manage-role.edit', $role->id) }}" variant="secondary" size="sm" class="dark:border-gray-600 dark:hover:border-gray-500">Edit</x-button>
                <form action="{{ route('manage-role.destroy', $role->id) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm" class="dark:bg-red-600 dark:hover:bg-red-500">Delete</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>
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
        title: 'Hapus Role?',
        text: 'Role akan dihapus permanen! Pastikan tidak ada user yang menggunakan role ini.',
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
</script>
@endpush
