<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Register | TailAdmin</title>
  <link href="https://cdn.tailwindcss.com" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-600 via-sky-600 to-teal-500">
  <div class="relative flex min-h-screen items-center justify-center px-4 py-10">
    <div class="absolute inset-0 bg-grid-white/[0.08] [mask-image:radial-gradient(ellipse_at_center,white,transparent_60%)]"></div>

    <div class="relative z-10 mx-auto w-full max-w-md">
      <div class="mb-6 text-center text-white">
        <h1 class="text-2xl font-bold">Create an account</h1>
        <p class="text-white/80 text-sm">Join TailAdmin today</p>
      </div>

      <div class="rounded-2xl bg-white/95 p-6 shadow-2xl ring-1 ring-white/10 backdrop-blur-md dark:bg-gray-900/95">
        @if($errors->any())
          <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/40 dark:text-red-200">
            <ul class="list-disc pl-5">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
          @csrf
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20">
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20">
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" required class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20">
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Confirm Password</label>
            <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20">
          </div>
          <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-6 py-3 font-semibold text-white shadow-lg transition hover:bg-indigo-700">Create account</button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-500">Sign in</a></p>
      </div>
    </div>
  </div>
</body>
</html>
