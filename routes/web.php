<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\Warung\ProdukWebController;
use App\Http\Controllers\Warung\ProfilWebController;
use App\Http\Controllers\Warung\OrderWebController;
use App\Http\Controllers\Konsumen\OutletController;
use App\Http\Controllers\Konsumen\CheckoutController;
use App\Http\Controllers\Kurir\KurirWebController;
use App\Http\Controllers\Warung\ReportController;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Outlet\app\Models\Outlet;
use Modules\Warung\app\Models\Produk;

/*
|--------------------------------------------------------------------------
| PWA Web Routes — Multi-Role Layout
|--------------------------------------------------------------------------
| 1 aplikasi Laravel, 3 role: /warung, /konsumen, /kurir
| Masing-masing punya layout, manifest, dan Service Worker scope sendiri.
*/

// ========== Public — Auth Pages (shared views, per-role) ==========

foreach (['warung', 'konsumen', 'kurir'] as $role) {
    Route::get("/{$role}/login", [WebAuthController::class, 'showLogin'])->name("{$role}.login");
    Route::post("/{$role}/login", [WebAuthController::class, 'login']);

    Route::get("/{$role}/register", [WebAuthController::class, 'showRegister'])->name("{$role}.register");
    Route::post("/{$role}/register", [WebAuthController::class, 'register']);

    Route::get("/{$role}/verify-otp", [WebAuthController::class, 'showVerifyOtp'])->name("{$role}.verify-otp");
    Route::post("/{$role}/verify-otp", [WebAuthController::class, 'verifyOtp']);

    // Google OAuth
    Route::get("/{$role}/auth/google", [WebAuthController::class, 'redirectToGoogle'])->name("{$role}.auth.google");
}

// Google OAuth callback (shared, role from session)
Route::get('/auth/google/callback', [WebAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Shared logout (POST dari form + GET fallback untuk browser cache/history)
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
Route::get('/logout', [WebAuthController::class, 'logout']);

// ========== Protected — Role Dashboards ==========

Route::middleware('auth')->group(function () {
    // Warung
    Route::get('/warung', function () {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        $totalProduk = $outlet ? Produk::where('outlet_id', $outlet->id)->count() : 0;
        $orderBaru = $outlet
            ? \Modules\Order\app\Models\Order::where('outlet_id', $outlet->id)->where('status', 'dibuat')->count()
            : 0;
        $orderHariIni = $outlet
            ? \Modules\Order\app\Models\Order::where('outlet_id', $outlet->id)->whereDate('created_at', now()->toDateString())->count()
            : 0;
        $omzetHariIni = $outlet
            ? \Modules\Order\app\Models\Order::where('outlet_id', $outlet->id)
                ->where('status', 'selesai')
                ->whereDate('created_at', now()->toDateString())
                ->sum('total_harga')
            : 0;
        $stokTipis = 0;
        if ($outlet) {
            foreach (Produk::where('outlet_id', $outlet->id)->get() as $p) {
                $s = HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id);
                if ($s > 0 && $s <= 2) $stokTipis++;
            }
        }
        $warung = $outlet?->warungDetail;

        return view('warung.dashboard', compact(
            'outlet', 'totalProduk', 'orderBaru', 'orderHariIni', 'omzetHariIni', 'stokTipis', 'warung'
        ));
    })->name('warung.dashboard');
    Route::get('/warung/order-masuk', [OrderWebController::class, 'index'])->name('warung.order-masuk');
    Route::post('/warung/order/{id}/konfirmasi', [OrderWebController::class, 'konfirmasi'])->name('warung.order.konfirmasi');
    Route::post('/warung/order/{id}/tolak', [OrderWebController::class, 'tolak'])->name('warung.order.tolak');
    Route::get('/warung/laporan', [ReportController::class, 'index'])->name('warung.laporan');
    Route::get('/warung/pos', function () {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        $produk = $outlet
            ? Produk::where('outlet_id', $outlet->id)->orderBy('created_at', 'desc')->get()
            : collect();

        $produkList = $produk->map(function ($p) {
            return [
                'id'          => $p->id,
                'nama'        => $p->nama,
                'harga'       => (float) $p->harga,
                'satuan'      => $p->satuan,
                'stok'        => HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id),
                'tersedia'    => HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id) > 0,
            ];
        })->toArray();

        return view('warung.pos', ['produkList' => $produkList]);
    })->name('warung.pos');
    Route::post('/warung/pos/transaksi', [ProdukWebController::class, 'posTransaksi'])->name('warung.pos.transaksi');
    Route::get('/warung/kelola-produk', function () {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        $produk = $outlet
            ? Produk::where('outlet_id', $outlet->id)->orderBy('created_at', 'desc')->get()
            : collect();

        $produkList = $produk->map(function ($p) {
            return [
                'id'          => $p->id,
                'nama'        => $p->nama,
                'deskripsi'   => $p->deskripsi,
                'harga'       => (float) $p->harga,
                'harga_beli'  => (float) ($p->harga_beli ?? 0),
                'satuan'      => $p->satuan,
                'stok'        => HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id),
                'tersedia'    => HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id) > 0,
            ];
        })->toArray();

        return view('warung.kelola-produk', ['produkList' => $produkList]);
    })->name('warung.kelola-produk');
    Route::post('/warung/kelola-produk', [ProdukWebController::class, 'store'])->name('warung.produk.store');
    Route::put('/warung/kelola-produk/{id}', [ProdukWebController::class, 'update'])->name('warung.produk.update');
    Route::post('/warung/kelola-produk/{id}/toggle', [ProdukWebController::class, 'toggle'])->name('warung.produk.toggle');
    Route::post('/warung/kelola-produk/{id}/restock', [ProdukWebController::class, 'restock'])->name('warung.produk.restock');
    Route::get('/warung/produk/barcode/{barcode}', [ProdukWebController::class, 'lookupByBarcode'])->name('warung.produk.barcode');

    // Profil
    Route::get('/warung/profil', [ProfilWebController::class, 'index'])->name('warung.profil');
    Route::post('/warung/profil/akun', [ProfilWebController::class, 'updateAkun'])->name('warung.profil.akun');
    Route::post('/warung/profil/password', [ProfilWebController::class, 'updatePassword'])->name('warung.profil.password');
    Route::post('/warung/profil/outlet', [ProfilWebController::class, 'saveOutlet'])->name('warung.profil.outlet');
    Route::get('/warung/verifikasi', [ProfilWebController::class, 'verifikasi'])->name('warung.verifikasi');
    Route::post('/warung/verifikasi', [ProfilWebController::class, 'submitVerifikasi'])->name('warung.verifikasi.submit');

    // Konsumen
    Route::get('/konsumen', [OutletController::class, 'index'])->name('konsumen.dashboard');
    Route::get('/konsumen/outlet', [OutletController::class, 'daftar'])->name('konsumen.outlet');
    Route::get('/konsumen/order', function () {
        $user = Auth::user();
        $orders = \Modules\Order\app\Models\Order::with(['items', 'outlet'])
            ->where('buyer_type', 'Konsumen')
            ->where('buyer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
        return view('konsumen.order-list', ['orders' => $orders]);
    })->name('konsumen.order');
    Route::get('/konsumen/checkout', [CheckoutController::class, 'index'])->name('konsumen.checkout');
    Route::post('/konsumen/checkout', [CheckoutController::class, 'store']);
    Route::get('/konsumen/laporan', [OutletController::class, 'laporan'])->name('konsumen.laporan');
    Route::get('/konsumen/profil', function () {
        return view('konsumen.profil');
    })->name('konsumen.profil');
    Route::put('/konsumen/profil', [OutletController::class, 'updateProfil'])->name('konsumen.profil.update');
    Route::post('/konsumen/profil/password', [OutletController::class, 'gantiPassword'])->name('konsumen.profil.password');

    // Kurir
    Route::get('/kurir', [KurirWebController::class, 'index'])->name('kurir.dashboard');
    Route::post('/kurir/toggle-online', [KurirWebController::class, 'toggleOnline'])->name('kurir.toggle-online');
    Route::get('/kurir/order-tersedia', [KurirWebController::class, 'orderTersedia'])->name('kurir.order-tersedia');
    Route::post('/kurir/order/{id}/klaim', [KurirWebController::class, 'klaimOrder'])->name('kurir.order.klaim');
    Route::get('/kurir/order-aktif', [KurirWebController::class, 'orderAktif'])->name('kurir.order-aktif');
    Route::post('/kurir/order/{id}/update-status', [KurirWebController::class, 'updateStatus'])->name('kurir.order.update-status');
    Route::get('/kurir/riwayat-transaksi', [KurirWebController::class, 'riwayatTransaksi'])->name('kurir.riwayat-transaksi');
});

// ========== Wilayah API (Public) ==========
use App\Http\Controllers\WilayahController;

Route::get('/api/wilayah/provinsi', [WilayahController::class, 'provinsi']);
Route::get('/api/wilayah/kabupaten', [WilayahController::class, 'kabupaten']);
Route::get('/api/wilayah/kecamatan', [WilayahController::class, 'kecamatan']);
Route::get('/api/wilayah/desa', [WilayahController::class, 'desa']);

// ========== Root ==========
Route::get('/', function () {
    return view('welcome');
});
