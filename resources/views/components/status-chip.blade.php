{{-- StatusChip — sistem chip status konsisten di 3 app --}}
{{--
    Usage:
    <x-status-chip status="dibuat" />        -> ● Dibuat (abu-abu)
    <x-status-chip status="diambil_kurir" /> -> ● Diambil Kurir (kuning)
    <x-status-chip status="diantar" />       -> ● Diantar (biru)
    <x-status-chip status="selesai" />       -> ● Selesai (hijau)
    <x-status-chip status="dibatalkan" />    -> ● Dibatalkan (merah)
    <x-status-chip status="gagal_kirim" />   -> ● Gagal Kirim (merah)
    <x-status-chip status="tersedia" />     -> ● Tersedia (hijau)
    <x-status-chip status="habis" />        -> ● Habis (abu-abu)
    <x-status-chip status="hampir_habis" /> -> ● Hampir Habis (merah)
    <x-status-chip status="biasa" />        -> ● Biasa (hijau)
    <x-status-chip status="grosir" />       -> ● Grosir (emas)
--}}

@php
    $map = [
        // State machine Order
        'dibuat'          => ['#E8A23C', 'Menunggu Konfirmasi'],
        'diambil_kurir'   => ['#E8A23C', 'Diambil Kurir'],
        'diantar'         => ['#1976D2', 'Sedang Diantar'],
        'selesai'         => ['#1F5C4F', 'Selesai'],
        'dibatalkan'      => ['#C4482E', 'Dibatalkan'],
        'gagal_kirim'     => ['#C4482E', 'Gagal Kirim'],
        // Ketersediaan Produk
        'tersedia'        => ['#1F5C4F', 'Tersedia'],
        'habis'           => ['#9E9E9E', 'Habis'],
        'hampir_habis'    => ['#C4482E', 'Hampir Habis'],
        // Tier Warung
        'biasa'           => ['#1F5C4F', 'Biasa'],
        'grosir'          => ['#E8A23C', 'Grosir'],
    ];
    $entry = $map[$status ?? 'dibuat'] ?? ['#9E9E9E', ucfirst($status ?? '?')];
    $color = $entry[0];
    $label = $entry[1];
@endphp

<span class="badge rounded-pill px-3 py-1"
      style="background-color: {{ $color }}; color: #fff; font-size: <?= $size ?? '0.75rem'; ?>; opacity: <?= $opacity ?? '1'; ?>;">
    ● {{ $label }}
</span>