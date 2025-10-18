<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Coffee Shop Management')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-gray-900">☕ Coffee Shop</a>
                </div>
                <nav class="flex items-center space-x-4">
                    <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-gray-900">Products</a>
                    <a href="/admin" class="text-gray-700 hover:text-gray-900">Admin</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="py-6">
        @yield('content')
    </main>

    <footer class="bg-gray-800 text-white py-4 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; {{ date('Y') }} Coffee Shop Management System</p>
        </div>
    </footer>
</body>
</html>
