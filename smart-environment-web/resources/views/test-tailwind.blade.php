<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Tailwind CSS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="min-h-screen bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Tailwind CSS Test</h1>
            <p class="text-gray-600 mb-6">Jika Anda melihat halaman ini dengan styling yang bagus, berarti Tailwind CSS
                sudah berfungsi dengan baik!</p>
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                Test Button
            </button>
            <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                <strong>Success!</strong> Tailwind CSS v4 is working properly.
            </div>
        </div>
    </div>
</body>

</html>
