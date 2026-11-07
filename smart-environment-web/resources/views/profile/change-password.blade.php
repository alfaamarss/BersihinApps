@extends('layouts.master')

@section('content')
<div class="p-6 lg:p-8 max-w-4xl">
  <div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-2">Change Password</h1>
    <p class="text-gray-600 dark:text-gray-400">Update your password to keep your account secure</p>
  </div>

  @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
      <ul class="list-disc list-inside text-red-800 dark:text-red-200">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
    <form action="{{ route('profile.update-password') }}" method="POST">
      @csrf

      <div class="mb-6">
        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Current Password
        </label>
        <input
          type="password"
          id="current_password"
          name="current_password"
          required
          class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <div class="mb-6">
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          New Password
        </label>
        <input
          type="password"
          id="password"
          name="password"
          required
          class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Minimum 8 characters
        </p>
      </div>

      <div class="mb-6">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Confirm New Password
        </label>
        <input
          type="password"
          id="password_confirmation"
          name="password_confirmation"
          required
          class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <div class="flex gap-3">
        <button
          type="submit"
          class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
          Update Password
        </button>
        <a
          href="{{ route('home') }}"
          class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-medium rounded-lg transition-colors"
        >
          Cancel
        </a>
      </div>
    </form>
  </div>

  <div class="mt-6">
    <a
      href="{{ route('profile.edit') }}"
      class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
    >
      ← Back to Profile
    </a>
  </div>
</div>
@endsection
