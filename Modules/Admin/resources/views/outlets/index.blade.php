@extends('admin::layouts.master')
@section('title', 'Verifikasi Outlet - DesaHub Admin')

@section('content')
<h2 class="text-2xl font-bold mb-2">Verifikasi Outlet</h2>
<p class="text-gray-500 mb-6">Outlet dengan status "Dasar" belum diverifikasi lokasi usahanya oleh admin.</p>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Outlet</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vertikal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alamat</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($outlets as $outlet)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm">#{{ $outlet->id }}</td>
                <td class="px-6 py-4 text-sm font-medium">{{ $outlet->nama }}</td>
                <td class="px-6 py-4 text-sm">{{ $outlet->vertikals ?? '-' }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($outlet->alamat, 30) }}</td>
                <td class="px-6 py-4">
                    @if($outlet->level_verifikasi === 'terverifikasi')
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Terverifikasi</span>
                    @else
                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Dasar</span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    @if($outlet->level_verifikasi === 'dasar')
                        <form action="{{ route('admin.outlets.verify', $outlet->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Verifikasi</button>
                        </form>
                    @else
                        <span class="text-sm text-gray-400">Selesai</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $outlets->links() }}</div>
</div>
@endsection