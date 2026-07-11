@extends('layouts.warung')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0" style="font-family: var(--font-judul);">Chat Konsumen</h5>
    <small class="text-muted">Tanya jawab stok & produk</small>
</div>

@if (session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
@endif

<div class="row g-3">
    {{-- Inbox percakapan --}}
    <div class="col-12 col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-2">
                @if ($percakapan->isEmpty())
                    <div class="text-center text-muted py-4" style="font-size: 0.85rem;">
                        Belum ada percakapan.<br>Konsumen akan muncul di sini saat mereka mengirim pesan.
                    </div>
                @else
                    @foreach ($percakapan as $pc)
                        @php
                            $last = $pc->pesanTerakhir->first();
                            $unread = $pc->belumDibacaOutlet;
                        @endphp
                        <a href="{{ route('warung.chat.show', $pc->id) }}"
                           class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none {{ $activeId == $pc->id ? 'bg-light' : '' }}"
                           style="color: var(--warna-teks);">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                 style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold text-truncate">{{ $pc->konsumen?->nama ?? 'Konsumen' }}</span>
                                    @if ($unread > 0)
                                        <span class="badge rounded-pill bg-danger">{{ $unread }}</span>
                                    @endif
                                </div>
                                <small class="text-muted text-truncate d-block">
                                    {{ $last ? Str::limit($last->isi_pesan, 30) : '—' }}
                                </small>
                            </div>
                        </a>
                        @if (!$loop->last)<hr class="my-1">@endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- Panel percakapan aktif --}}
    <div class="col-12 col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-column" style="min-height: 60vh;">
                @if ($activeId && isset($active))
                    <div class="border-bottom pb-2 mb-3">
                        <strong>{{ $active->konsumen?->nama ?? 'Konsumen' }}</strong>
                        <br><small class="text-muted">{{ $active->konsumen?->email }}</small>
                    </div>

                    <div class="flex-grow-1 overflow-auto mb-3" id="pesan-area">
                        @foreach ($pesanList as $msg)
                            @if ($msg->pengirim_type === 'Outlet')
                                <div class="d-flex justify-content-end mb-2">
                                    <div class="p-2 rounded-3 text-white"
                                         style="background-color: var(--warna-aksen-utama); max-width: 80%; font-size: 0.85rem;">
                                        {{ $msg->isi_pesan }}
                                    </div>
                                </div>
                            @else
                                <div class="d-flex justify-content-start mb-2">
                                    <div class="p-2 rounded-3 border"
                                         style="background: #f8f8f8; max-width: 80%; font-size: 0.85rem;">
                                        {{ $msg->isi_pesan }}
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('warung.chat.kirim', $active->id) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="isi_pesan" class="form-control" placeholder="Tulis balasan..." required>
                        <button type="submit" class="btn btn-primary px-3">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                @else
                    <div class="text-center text-muted m-auto py-5" style="font-size: 0.9rem;">
                        Pilih percakapan di kiri untuk melihat pesan.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($activeId && isset($active))
    <script>
        // Auto-scroll ke pesan terbawah saat buka percakapan
        document.addEventListener('DOMContentLoaded', function() {
            var area = document.getElementById('pesan-area');
            if (area) area.scrollTop = area.scrollHeight;
        });
    </script>
@endif

@endsection