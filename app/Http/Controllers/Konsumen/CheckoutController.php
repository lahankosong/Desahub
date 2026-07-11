<?php

namespace App\Http\Controllers\Konsumen;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Modules\Warung\app\Models\Produk;

class CheckoutController extends Controller
{
    /**
     * Halaman checkout — GET /konsumen/checkout?produk_id=1&qty=1
     */
    public function index(Request $request)
    {
        $produkId = (int) $request->input('produk_id');
        $qty = max(1, (int) $request->input('qty', 1));
        $produk = Produk::with('outlet')->find($produkId);

        if (! $produk) {
            return redirect()->route('konsumen.dashboard')->withErrors(['produk' => 'Produk tidak ditemukan.']);
        }

        $stok = HasKetersediaanLog::getKetersediaanCache(Produk::class, $produk->id);
        if ($stok < $qty) {
            return back()->withErrors(['stok' => 'Stok tidak mencukupi.']);
        }

        $user = Auth::user();
        $profile = $user?->konsumenProfile;

        return view('konsumen.checkout', [
            'produk'  => $produk,
            'qty'     => $qty,
            'stok'    => $stok,
            'profile' => $profile,
        ]);
    }

    /**
     * Proses checkout — POST /konsumen/checkout
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'produk_id'           => 'required|exists:warung_produk,id',
            'qty'                 => 'required|integer|min:1',
            'metode_pembayaran'   => 'required|in:cod,transfer',
            'metode_pengiriman'   => 'required|in:diantar_kurir,ambil_sendiri',
            'alamat_antar'        => 'required_if:metode_pengiriman,diantar_kurir|nullable|string|max:500',
            'catatan'             => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $produk = Produk::with('outlet')->findOrFail($valid['produk_id']);
        $qty = (int) $valid['qty'];
        $hargaSatuan = $produk->getHarga($qty);
        $totalHarga = $hargaSatuan * $qty;

        // Stok diverifikasi & dikurangi di dalam listener WarungKetersediaanListener
        // (dipicu oleh emitOrderDibuat) — jangan kurangi 2x.
        DB::transaction(function () use ($valid, $user, $produk, $qty, $hargaSatuan, $totalHarga) {
            $order = Order::create([
                'outlet_id'          => $produk->outlet_id,
                'buyer_type'         => 'Konsumen',
                'buyer_id'           => $user->id,
                'total_harga'        => $totalHarga,
                'metode_pembayaran'  => $valid['metode_pembayaran'],
                'jenis_transaksi'    => 'online',
                'metode_pengiriman'  => $valid['metode_pengiriman'],
                'alamat_antar'       => $valid['alamat_antar'] ?? null,
                'catatan'            => $valid['catatan'] ?? null,
                'status'             => 'dibuat',
                'dibuat_pada'        => now(),
            ]);

            OrderItem::create([
                'order_id'      => $order->id,
                'sellable_type' => Produk::class,
                'sellable_id'   => $produk->id,
                'nama_produk'   => $produk->nama,
                'qty'           => $qty,
                'harga_satuan'  => $hargaSatuan,
            ]);

            // emitOrderDibuat memicu WarungKetersediaanListener
            // yang menangani: kurangiCacheAtomik + catatPergerakan + KetersediaanBerubah
            $order->emitOrderDibuat();
        });

        return redirect()->route('konsumen.order')
            ->with('success', 'Order berhasil dibuat! Menunggu konfirmasi warung.');
    }
}