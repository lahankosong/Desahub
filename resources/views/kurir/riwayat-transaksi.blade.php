@extends('layouts.kurir')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="font-family: var(--font-judul);">Riwayat Transaksi COD</h6>
                <p class="text-muted mb-3" style="font-size: 0.85rem;">
                    Daftar transaksi COD yang telah Anda catat.
                </p>

                @php
                    $kurirId = auth()->user()?->kurirProfile?->id ?? null;
                    $transaksi = $kurirId
                        ? \Modules\Payment\app\Models\CodSettlement::where('kurir_id', $kurirId)
                            ->orderBy('dicatat_pada', 'desc')
                            ->take(20)
                            ->get()
                        : collect();
                @endphp

                @if ($transaksi->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover" style="font-size: 0.85rem;">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transaksi as $t)
                                    <tr>
                                        <td>#{{ $t->order_id }}</td>
                                        <td class="fw-semibold">Rp{{ number_format($t->jumlah_diterima, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge rounded-pill px-2 py-1" style="background-color: {{ $t->status_setor == 'sudah_disetor' ? 'var(--warna-aksen-kedua)' : 'var(--warna-aksen-utama)' }}; color: #fff; font-size: 0.7rem;">
                                                {{ $t->status_setor == 'sudah_disetor' ? 'Sudah Disetor' : 'Belum Disetor' }}
                                            </span>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($t->dicatat_pada)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-receipt display-6" style="color: var(--warna-netral-garis);"></i>
                        <p class="mt-2 text-muted" style="font-size: 0.85rem;">Belum ada transaksi COD</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection