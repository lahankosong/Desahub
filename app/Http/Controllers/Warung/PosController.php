<?php

namespace App\Http\Controllers\Warung;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Outlet\app\Models\Outlet;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Modules\Warung\app\Models\Produk;
use Modules\Warung\app\Models\PelangganWarung;
use Modules\Warung\app\Models\Piutang;

class PosController extends Controller
{
    // =============================================
    // TRANSAKSI POS (cash + tempo)
    // =============================================

    /**
     * Proses transaksi POS — POST /warung/pos/transaksi
     *
     * ═══════════════════════════════════════════════════════
     * CATATAN DEPLOYMENT — BACA SEBELUM APPLY FILE INI:
     *
     * 1. Route: pastikan /warung/pos/transaksi diarahkan ke
     *    PosController@transaksi, BUKAN ProdukWebController@posTransaksi.
     *    Di routes/web.php:
     *      Route::post('pos/transaksi', [PosController::class, 'transaksi']);
     *
     * 2. Jika ProdukWebController@posTransaksi masih ada, bisa
     *    dihapus atau dibiarkan — tapi route HARUS menunjuk ke sini.
     *
     * 3. JANGAN apply migration 2026_07_11_000001 dan 000002 dari Claude Web
     *    — sudah ditangani di Sesi 23/24 (000005-000008).
     * ═══════════════════════════════════════════════════════
     *
     * Menangani 2 alur:
     *   - metode=cash  : buyer_type=Umum, langsung selesai, catat cod_settlements
     *   - metode=tempo : buyer_type=PelangganWarung, langsung selesai, catat piutang
     */
    public function transaksi(Request $request)
    {
        $valid = $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.produk_id'     => 'required|integer|exists:warung_produk,id',
            'items.*.qty'           => 'required|integer|min:1',
            'items.*.harga_satuan'  => 'required|numeric|min:0',
            'total'                 => 'required|numeric|min:0',
            'metode'                => 'required|in:cash,tempo',
            'pelanggan_id'          => 'required_if:metode,tempo|nullable|integer|exists:pelanggan_warung,id',
            'jatuh_tempo'           => 'nullable|date|after_or_equal:today',
            'catatan_tempo'         => 'nullable|string|max:500',
        ]);

        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet) {
            return response()->json(['success' => false, 'message' => 'Outlet tidak ditemukan'], 404);
        }

        // Validasi stok semua item sebelum mulai transaksi
        foreach ($valid['items'] as $item) {
            $produk = Produk::findOrFail($item['produk_id']);
            $stok = HasKetersediaanLog::getKetersediaanCache(Produk::class, $produk->id);
            if ($stok < $item['qty']) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok \"{$produk->nama}\" tidak cukup (tersedia: {$stok})",
                ], 422);
            }
        }

        return DB::transaction(function () use ($valid, $outlet) {
            $isTempo = $valid['metode'] === 'tempo';

            // Buat order — buyer_type berbeda tergantung metode
            $order = Order::create([
                'outlet_id'          => $outlet->id,
                'buyer_type'         => $isTempo ? 'PelangganWarung' : 'Umum',
                'buyer_id'           => $isTempo ? $valid['pelanggan_id'] : null,
                'total_harga'        => $valid['total'],
                'metode_pembayaran'  => $isTempo ? 'tempo' : 'tunai_pos',
                'jenis_transaksi'    => 'pos',
                'metode_pengiriman'  => null, // POS tidak ada pengiriman
                'status'             => 'selesai', // POS langsung selesai, tanpa kurir
                'selesai_pada'       => now(),
                'dibuat_pada'        => now(),
            ]);

            // Buat order items + kurangi stok (atomik, konsisten dengan BuatOrder)
            foreach ($valid['items'] as $item) {
                $produk = Produk::findOrFail($item['produk_id']);

                OrderItem::create([
                    'order_id'      => $order->id,
                    'sellable_type' => Produk::class,
                    'sellable_id'   => $produk->id,
                    'nama_produk'   => $produk->nama,      // ← SNAPSHOT, bukan live-lookup
                    'qty'           => $item['qty'],
                    'harga_satuan'  => $item['harga_satuan'],
                ]);

                // Kurangi stok: UPDATE atomik bergerbang (WHERE qty >= jumlah)
                // Lalu catat log ketersediaan (append-only, sumber kebenaran sinkronisasi)
                HasKetersediaanLog::tambahCache(Produk::class, $produk->id, -$item['qty']);
                HasKetersediaanLog::catatPergerakan(
                    Produk::class, $produk->id, $outlet->id,
                    -$item['qty'], 'penjualan', $order->id
                );
            }

            if ($isTempo) {
                // TEMPO: catat piutang (bukan cod_settlements)
                Piutang::create([
                    'order_id'              => $order->id,
                    'outlet_id'             => $outlet->id,
                    'pelanggan_warung_id'   => $valid['pelanggan_id'],
                    'jumlah'                => $valid['total'],
                    'terbayar'              => 0,
                    'jatuh_tempo'           => $valid['jatuh_tempo'] ?? null,
                    'catatan'               => $valid['catatan_tempo'] ?? null,
                    'status'                => 'aktif',
                ]);
            } else {
                // CASH: catat ke cod_settlements (dicatat oleh warung, bukan kurir)
                DB::table('cod_settlements')->insert([
                    'order_id'        => $order->id,
                    'kurir_id'        => null, // POS tidak ada kurir
                    'jumlah_diterima' => $valid['total'],
                    'status_setor'    => 'belum_disetor',
                    'dicatat_oleh'    => 'warung',
                    'dicatat_pada'    => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            // Emit events (sesuai event registry events.md)
            // emitOrderDibuat() sudah handle parameter yang benar di Order model
            // (jangan pakai OrderDibuat::dispatch($order) langsung — constructor minta int bukan object)
            $order->emitOrderDibuat();

            // PembayaranDiterima: untuk POS tempo = 'sebagian' (masih piutang), cash = 'lunas'
            // Cek signature event PembayaranDiterima di project Anda sebelum dispatch
            // Kalau PembayaranDiterima tidak dipakai/belum ada, baris ini bisa di-comment dulu
            // \Modules\Payment\app\Events\PembayaranDiterima::dispatch(
            //     $order->id,
            //     $isTempo ? 'tempo' : 'tunai_pos',
            //     $valid['total'],
            //     $isTempo ? 'sebagian' : 'lunas'
            // );

            return response()->json([
                'success'  => true,
                'order_id' => $order->id,
                'metode'   => $valid['metode'],
            ]);
        });
    }

    // =============================================
    // PELANGGAN WARUNG
    // =============================================

    /**
     * Daftar pelanggan warung — GET /warung/pos/pelanggan
     * Dipakai untuk populate dropdown saat metode=tempo
     */
    public function daftarPelanggan()
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet) {
            return response()->json([]);
        }

        return response()->json(
            PelangganWarung::where('outlet_id', $outlet->id)
                ->orderBy('nama')
                ->get()
                ->map(fn($p) => [
                    'id'               => $p->id,
                    'nama'             => $p->nama,
                    'no_hp'            => $p->no_hp,
                    'catatan'          => $p->catatan,
                    'total_utang_aktif' => (float) Piutang::where('pelanggan_warung_id', $p->id)
                        ->where('status', 'aktif')
                        ->sum(DB::raw('jumlah - terbayar')),
                ])
        );
    }

    /**
     * Tambah pelanggan baru — POST /warung/pos/pelanggan
     * Dibatasi online-only (validasi berjalan di sisi server)
     */
    public function tambahPelanggan(Request $request)
    {
        $valid = $request->validate([
            'nama'    => 'required|string|max:200',
            'no_hp'   => 'nullable|string|max:20',
            'catatan' => 'nullable|string|max:500',
        ]);

        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet) {
            return response()->json(['success' => false, 'message' => 'Outlet tidak ditemukan'], 404);
        }

        $pelanggan = PelangganWarung::create([
            'outlet_id' => $outlet->id,
            'nama'      => $valid['nama'],
            'no_hp'     => $valid['no_hp'] ?? null,
            'catatan'   => $valid['catatan'] ?? null,
        ]);

        return response()->json([
            'success'   => true,
            'pelanggan' => [
                'id'               => $pelanggan->id,
                'nama'             => $pelanggan->nama,
                'no_hp'            => $pelanggan->no_hp,
                'catatan'          => $pelanggan->catatan,
                'total_utang_aktif' => 0,
            ],
        ]);
    }

    // =============================================
    // PIUTANG
    // =============================================

    /**
     * Daftar piutang aktif — GET /warung/pos/piutang
     * Dipakai untuk widget dashboard + halaman piutang
     */
    public function daftarPiutang()
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet) {
            return response()->json([]);
        }

        return response()->json(
            Piutang::with('pelangganWarung')
                ->where('outlet_id', $outlet->id)
                ->where('status', 'aktif')
                ->orderBy('jatuh_tempo')
                ->get()
                ->map(fn($p) => [
                    'id'            => $p->id,
                    'pelanggan'     => [
                        'nama'  => $p->pelangganWarung->nama,
                        'no_hp' => $p->pelangganWarung->no_hp,
                        // WhatsApp reminder link — kalau ada nomor HP
                        'wa_link' => $p->pelangganWarung->no_hp
                            ? 'https://wa.me/62' . ltrim($p->pelangganWarung->no_hp, '0')
                              . '?text=' . urlencode(
                                  "Halo {$p->pelangganWarung->nama}, mengingatkan bahwa tagihan Anda sebesar Rp"
                                  . number_format($p->sisa, 0, ',', '.')
                                  . " jatuh tempo pada {$p->jatuh_tempo}. Terima kasih."
                              )
                            : null,
                    ],
                    'jumlah'        => (float) $p->jumlah,
                    'terbayar'      => (float) $p->terbayar,
                    'sisa'          => (float) ($p->jumlah - $p->terbayar),
                    'jatuh_tempo'   => $p->jatuh_tempo?->format('Y-m-d'),
                    'sudah_lewat'   => $p->jatuh_tempo && $p->jatuh_tempo->isPast(),
                    'catatan'       => $p->catatan,
                    'order_id'      => $p->order_id,
                ])
        );
    }

    /**
     * Catat pembayaran piutang (parsial atau penuh) — POST /warung/pos/piutang/{id}/bayar
     */
    public function bayarPiutang(Request $request, $id)
    {
        $valid = $request->validate([
            'jumlah' => 'required|numeric|min:1',
        ]);

        $piutang = Piutang::findOrFail($id);

        if ($piutang->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Piutang sudah lunas atau tidak aktif.',
            ], 422);
        }

        $sisa    = $piutang->jumlah - $piutang->terbayar;
        $bayar   = min((float) $valid['jumlah'], $sisa); // tidak bisa bayar melebihi sisa
        $piutang->terbayar += $bayar;

        if ($piutang->terbayar >= $piutang->jumlah) {
            $piutang->status = 'lunas';
        }

        $piutang->save();

        return response()->json([
            'success'  => true,
            'sisa'     => (float) ($piutang->jumlah - $piutang->terbayar),
            'status'   => $piutang->status,
            'piutang'  => $piutang->fresh('pelangganWarung'),
        ]);
    }
}
