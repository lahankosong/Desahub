@extends('admin::layouts.master')
@section('title', 'Dashboard - DesaHub Admin')

@section('content')
<h2 class="text-2xl font-bold mb-6">Dashboard</h2>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <a href="{{ route('admin.outlets.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center gap-4">
            <div class="bg-blue-100 p-3 rounded-full"><i class="fas fa-store text-blue-600 text-xl"></i></div>
            <div><p class="text-gray-500 text-sm">Total Outlet</p><p class="text-2xl font-bold">{{ $stats['total_outlets'] }}</p></div>
        </div>
    </a>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center gap-4">
            <div class="bg-yellow-100 p-3 rounded-full"><i class="fas fa-clock text-yellow-600 text-xl"></i></div>
            <div><p class="text-gray-500 text-sm">Menunggu Verifikasi</p><p class="text-2xl font-bold">{{ $stats['pending_verification'] }}</p></div>
        </div>
    </div>
    <a href="{{ route('admin.orders.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center gap-4">
            <div class="bg-green-100 p-3 rounded-full"><i class="fas fa-shopping-cart text-green-600 text-xl"></i></div>
            <div><p class="text-gray-500 text-sm">Order Hari Ini</p><p class="text-2xl font-bold">{{ $stats['orders_hari_ini'] }}</p></div>
        </div>
    </a>
    <a href="{{ route('admin.payments.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center gap-4">
            <div class="bg-red-100 p-3 rounded-full"><i class="fas fa-money-bill text-red-600 text-xl"></i></div>
            <div><p class="text-gray-500 text-sm">COD Belum Disetor</p><p class="text-2xl font-bold">Rp {{ number_format($stats['cod_belum_disetor']) }}</p></div>
        </div>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-bold text-lg mb-4">Status Order</h3>
        <div class="space-y-3">
            <div class="flex justify-between"><span class="text-gray-600">Dibuat</span><span class="font-bold text-blue-600">{{ $stats['orders_dibuat'] }}</span></div>
            <div class="flex justify-between"><span class="text-gray-600">Selesai</span><span class="font-bold text-green-600">{{ $stats['orders_selesai'] }}</span></div>
            <div class="flex justify-between"><span class="text-gray-600">Total Order</span><span class="font-bold">{{ $stats['total_orders'] }}</span></div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-bold text-lg mb-4">Kurir</h3>
        <div class="space-y-3">
            <div class="flex justify-between"><span class="text-gray-600">Total Kurir</span><span class="font-bold">{{ $stats['total_kurir'] }}</span></div>
            <div class="flex justify-between"><span class="text-gray-600">Online</span><span class="font-bold text-green-600">{{ $stats['kurir_online'] }}</span></div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-bold text-lg mb-4">Produk Warung</h3>
        <p class="text-4xl font-bold text-blue-600">{{ $stats['total_produk'] }}</p>
        <p class="text-gray-500 text-sm">Total produk terdaftar</p>
    </div>
</div>
@endsection