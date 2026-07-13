<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DesaHub Admin')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    @if(session('admin_logged_in'))
    <!-- Sidebar -->
    <div class="flex h-screen">
        <aside class="w-64 bg-gray-800 text-white flex-shrink-0">
            <div class="p-4 border-b border-gray-700">
                <h1 class="text-xl font-bold">DesaHub Admin</h1>
                <p class="text-sm text-gray-400 mt-1">{{ session('admin_nama') }}</p>
            </div>
            <nav class="p-4 space-y-2">
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-home w-5"></i> Dashboard
                </a>
                <a href="{{ route('admin.outlets.index') }}" 
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.outlets.*') ? 'bg-blue-600' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-store w-5"></i> Verifikasi Outlet
                </a>
                <a href="{{ route('admin.orders.index') }}" 
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.orders.*') ? 'bg-blue-600' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-shopping-cart w-5"></i> Orders
                </a>
                <a href="{{ route('admin.payments.index') }}" 
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.payments.*') ? 'bg-blue-600' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-money-bill w-5"></i> COD Settlement
                </a>
                <a href="{{ route('admin.kategori.index') }}" 
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.kategori.*') ? 'bg-blue-600' : 'hover:bg-gray-700' }}">
                    <i class="fas fa-tags w-5"></i> Kategori Produk
                </a>
                <hr class="border-gray-700 my-2">
                <form action="{{ route('admin.logout') }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-red-600 w-full text-left">
                        <i class="fas fa-sign-out-alt w-5"></i> Logout
                    </button>
                </form>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
    @else
        @yield('content')
    @endif
</body>
</html>