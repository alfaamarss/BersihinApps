@extends('layouts.master')

@section('title','Edit User')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
    <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-2xl font-bold text-black dark:text-white mb-6">Edit User</h2>

            @if($errors->any())
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-700/40 dark:bg-red-900/30 dark:text-red-200">
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('manage-user.update', $user->id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                <x-form.input name="name" label="Name" required>{{ old('name', $user->name) }}</x-form.input>
                <x-form.input name="email" type="email" label="Email" required>{{ old('email', $user->email) }}</x-form.input>
                <x-form.input name="password" type="password" label="Password (leave blank to keep current)" />
                <x-form.select name="role" label="Role" :options="$roles->toArray()" placeholder="Select Role" :value="old('role', $user->getRoleNames()->first())" />
                <x-form.checkbox name="verified" :checked="old('verified', $user->email_verified_at ? true : false)" label="Email Verified" />
                <div class="flex justify-end gap-3 pt-2">
                    <x-button as="a" href="{{ route('manage-user.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" variant="primary">Update User</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
