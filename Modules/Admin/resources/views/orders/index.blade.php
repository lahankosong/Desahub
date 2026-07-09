@extends('admin::layouts.master')
@section('title', 'Orders - DesaHub Admin')

@section('content')
<h2 class="text-2xl font-bold mb-2">Daftar Orders</h2>
<p class="text-gray-500 mb-6">Semua transaksi yang terjadi di platform.</p>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buyer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Metode</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($orders as $order)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium">#{{ $order->id }}</td>
                <td class="px-6 py-4 text-sm">{{ $order->outlet_nama ?? 'N/A' }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->buyer_type }} #{{ $order->buyer_id }}</td>
                <td class="px-6 py-4 text-sm">Rp {{ number_format($order->total_harga) }}</td>
                <td class="px-6 py-4 text-sm uppercase">{{ $order->metode_pembayaran }}</td>
                <td class="px-6 py-4">
                    @php $colors = ['dibuat'=>'bg-blue-100 text-blue-800','diambil_kurir'=>'bg-yellow-100 text-yellow-800','diantar'=>'bg-purple-100 text-purple-800','selesai'=>'bg-green-100 text-green-800','dibatalkan'=>'bg-red-100 text-red-800','gagal_kirim'=>'bg-gray-100 text-gray-800']; @endphp
                    <span class="px-2 py-1 text-xs rounded-full {{ $colors[$order->status] ?? 'bg-gray-100' }}">
                        {{ str_replace('_', ' ', $order->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($order->dibuat_pada)->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $orders->links() }}</div>
</div>
@endsection