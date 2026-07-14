<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk #{{ $order->id }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <style>
        @media print {
            body * { visibility: hidden; }
            #struk-area, #struk-area * { visibility: visible; }
            #struk-area { 
                position: absolute; 
                left: 50%; 
                top: 50%; 
                transform: translate(-50%, -50%);
                width: 280px;
                margin: 0;
                padding: 12px;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print { display: none !important; }
            #struk-area .shadow-lg { box-shadow: none !important; }
        }
        @page { 
            size: 70mm auto; 
            margin: 0; 
        }
        #struk-area {
            font-size: 11px;
            line-height: 1.4;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-start justify-center py-3 px-2">

    <div class="w-full max-w-xs">
        <!-- Action Buttons -->
        <div class="no-print mb-2 flex flex-wrap gap-1 justify-center">
            <button onclick="window.print()" 
                    class="px-2.5 py-1 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700 transition">
                🖨️ Cetak
            </button>
            <button onclick="downloadGambar()" 
                    class="px-2.5 py-1 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700 transition">
                📥 PNG
            </button>
            @if($pelanggan && $pelanggan->no_hp)
                <a href="{{ $waLink ?? '#' }}" target="_blank" 
                   class="px-2.5 py-1 bg-emerald-500 text-white rounded text-xs font-medium hover:bg-emerald-600 transition inline-block">
                    📱 WA
                </a>
            @endif
            <a href="{{ route('warung.pos') }}" 
               class="px-2.5 py-1 bg-gray-500 text-white rounded text-xs font-medium hover:bg-gray-600 transition inline-block">
                ← Kembali
            </a>
        </div>

        <!-- Struk Area -->
        <div id="struk-area" class="bg-white shadow-lg mx-auto" style="max-width: 280px; padding: 12px 14px;">
            
            <!-- Header -->
            <div class="text-center border-b border-dashed border-gray-300 pb-1.5 mb-1.5">
                <h1 class="text-sm font-bold text-gray-800 mb-0.5">{{ $outlet->nama ?? 'Outlet' }}</h1>
                @if($outlet->warungDetail)
                    <p class="text-[10px] text-gray-600 leading-tight">{{ $outlet->warungDetail->alamat ?? '' }}</p>
                @endif
                @if($outlet->no_hp ?? false)
                    <p class="text-[10px] text-gray-600 leading-tight">{{ $outlet->no_hp }}</p>
                @endif
                <p class="text-[10px] text-gray-500 mt-1">— STRUK PEMBAYARAN —</p>
            </div>

            <!-- Info Transaksi -->
            <div class="text-[10px] space-y-0.5 mb-1.5">
                <div class="flex justify-between">
                    <span class="text-gray-600">No. Transaksi</span>
                    <span class="font-mono font-semibold">#{{ $order->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Tanggal</span>
                    <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Metode</span>
                    <span class="font-semibold {{ $order->metode_pembayaran === 'tempo' ? 'text-orange-600' : 'text-green-600' }}">
                        {{ strtoupper($order->metode_pembayaran === 'tempo' ? 'TEMPO' : 'TUNAI') }}
                    </span>
                </div>
                @if($pelanggan)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Pelanggan</span>
                        <span class="font-semibold">{{ $pelanggan->nama }}</span>
                    </div>
                    @if($pelanggan->no_hp)
                        <div class="flex justify-between">
                            <span class="text-gray-600">No. HP</span>
                            <span>{{ $pelanggan->no_hp }}</span>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Items -->
            <div class="border-t border-dashed border-gray-300 pt-1.5 mb-1.5">
                <table class="w-full text-[10px]">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-0.5 font-semibold">Item</th>
                            <th class="text-center py-0.5 font-semibold">Qty</th>
                            <th class="text-right py-0.5 font-semibold">Harga</th>
                            <th class="text-right py-0.5 font-semibold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="border-b border-gray-100">
                                <td class="py-0.5 text-left">{{ $item->nama_produk }}</td>
                                <td class="py-0.5 text-center">{{ $item->qty }}</td>
                                <td class="py-0.5 text-right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                <td class="py-0.5 text-right font-semibold">Rp {{ number_format($item->qty * $item->harga_satuan, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Total -->
            <div class="border-t-2 border-dashed border-gray-300 pt-1.5 mb-1.5">
                <div class="flex justify-between text-xs font-bold">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</span>
                </div>
                @if($order->metode_pembayaran === 'tempo')
                    <div class="text-[10px] text-orange-600 mt-0.5 text-right">
                        *Belum lunas — lihat piutang
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="text-center border-t-2 border-dashed border-gray-300 pt-1.5">
                <p class="text-[10px] text-gray-700 font-semibold">Terima kasih atas kunjungan Anda!</p>
                <p class="text-[9px] text-gray-500">Struk ini adalah bukti pembayaran yang sah.</p>
                @if($order->metode_pembayaran === 'tempo' && $order->jatuh_tempo ?? false)
                    <p class="text-[9px] text-orange-600 mt-0.5">
                        ⏰ Jatuh tempo: {{ \Carbon\Carbon::parse($order->jatuh_tempo)->format('d/m/Y') }}
                    </p>
                @endif
            </div>
        </div>

        @if($pelanggan && $pelanggan->no_hp)
            @php
                $waText = "Struk #{$order->id}\n";
                $waText .= "Tanggal: {$order->created_at->format('d/m/Y H:i')}\n";
                $waText .= "Metode: " . strtoupper($order->metode_pembayaran === 'tempo' ? 'TEMPO' : 'TUNAI') . "\n";
                $waText .= "Pelanggan: {$pelanggan->nama}\n\n";
                $waText .= "Item:\n";
                foreach($order->items as $item) {
                    $waText .= "- {$item->nama_produk} ({$item->qty}x) = Rp " . number_format($item->qty * $item->harga_satuan, 0, ',', '.') . "\n";
                }
                $waText .= "\nTOTAL: Rp " . number_format($order->total_harga, 0, ',', '.') . "\n";
                $waText .= "\nTerima kasih!";
                $waLink = 'https://wa.me/62' . ltrim($pelanggan->no_hp, '0') . '?text=' . urlencode($waText);
            @endphp
        @endif
    </div>

    <script>
        function downloadGambar() {
            const element = document.getElementById('struk-area');
            html2canvas(element, {
                scale: 2.5,
                backgroundColor: '#ffffff',
                useCORS: true,
                width: 280,
                onclone: function(doc) {
                    const el = doc.getElementById('struk-area');
                    if (el) el.style.boxShadow = 'none';
                }
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'struk-{{ $order->id }}-{{ date("Ymd-His") }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            }).catch(err => {
                console.error('Gagal download gambar:', err);
                alert('Gagal download gambar. Silakan gunakan tombol Cetak.');
            });
        }
    </script>
</body>
</html>