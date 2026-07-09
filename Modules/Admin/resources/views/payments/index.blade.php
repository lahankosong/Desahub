@extends('admin::layouts.master')
@section('title', 'COD Settlement - DesaHub Admin')

@section('content')
<h2 class="text-2xl font-bold mb-2">COD Settlement</h2>
<p class="text-gray-500 mb-2">Pencatatan uang tunai COD yang dipegang kurir.</p>
<div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-6">
    <strong>Total COD belum disetor: Rp {{ number_format($total_belum_disetor) }}</strong>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kurir ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($settlements as $s)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm">#{{ $s->id }}</td>
                <td class="px-6 py-4 text-sm">#{{ $s->order_id }}</td>
                <td class="px-6 py-4 text-sm">{{ $s->outlet_nama ?? 'N/A' }}</td>
                <td class="px-6 py-4 text-sm">#{{ $s->kurir_id }}</td>
                <td class="px-6 py-4 text-sm font-medium">Rp {{ number_format($s->jumlah_diterima) }}</td>
                <td class="px-6 py-4">
                    @if($s->status_setor === 'belum_disetor')
                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Belum Disetor</span>
                    @else
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Sudah Disetor</span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    @if($s->status_setor === 'belum_disetor')
                        <form action="{{ route('admin.payments.setor', $s->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Tandai Disetor</button>
                        </form>
                    @else
                        <span class="text-sm text-gray-400">Selesai</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $settlements->links() }}</div>
</div>
@endsection