@extends('layouts.master')

@section('content')
<div class="p-6 lg:p-8 max-w-4xl">
  <div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-2">Edit Profile</h1>
    <p class="text-gray-600 dark:text-gray-400">Update your account information</p>
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
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <!-- Avatar Upload Section -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Profile Photo
        </label>
        <div class="flex items-center gap-6">
          <div class="relative">
            <img 
              id="avatar-preview"
              src="{{ $user->avatar_url ? asset('storage/' . $user->avatar_url) : asset('assets/images/user/owner.jpg') }}" 
              alt="Avatar" 
              class="w-24 h-24 rounded-full object-cover border-4 border-gray-200 dark:border-gray-700"
            />
          </div>
          <div class="flex-1">
            <input
              type="file"
              id="avatar"
              name="avatar"
              accept="image/jpeg,image/png,image/jpg,image/gif"
              class="hidden"
              onchange="previewAvatar(event)"
            />
            <label
              for="avatar"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg cursor-pointer transition-colors"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Choose Photo
            </label>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
              JPG, PNG or GIF. Max size 2MB
            </p>
          </div>
        </div>
      </div>

      <div class="mb-6">
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Full Name
        </label>
        <input
          type="text"
          id="name"
          name="name"
          value="{{ old('name', $user->name) }}"
          required
          class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <div class="mb-6">
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Email Address
        </label>
        <input
          type="email"
          id="email"
          name="email"
          value="{{ old('email', $user->email) }}"
          required
          class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <div class="flex gap-3">
        <button
          type="submit"
          class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
          Save Changes
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
      href="{{ route('profile.change-password') }}"
      class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
    >
      Change Password →
    </a>
  </div>
</div>

<script>
function previewAvatar(event) {
  const input = event.target;
  const preview = document.getElementById('avatar-preview');
  
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
      preview.src = e.target.result;
    }
    
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
@endsection
