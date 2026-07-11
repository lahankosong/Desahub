<?php

namespace App\Http\Controllers\Konsumen;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Core\app\Helpers\RadiusHelper;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Outlet\app\Models\Outlet;
use Modules\Warung\app\Models\Produk;

class OutletController extends Controller
{
    /**
     * Dashboard Konsumen — GET /konsumen
     * Menampilkan semua produk dari warung dalam radius.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = $user?->konsumenProfile;

        // Ambil GPS dari query string, fallback ke profil konsumen
        $lat = (float) $request->input('lat', $profile->lat ?? 0);
        $lng = (float) $request->input('lng', $profile->lng ?? 0);
        $radius = max(1, min(50, (int) $request->input('radius', 1))); // 1-50km, default 1
        $hasGps = $lat !== 0.0 && $lng !== 0.0;
        $fromProfile = !$request->has('lat') && $hasGps;
        $requestHasGps = $request->has('lat') || $request->has('lng'); // user input GPS via URL/JS

        $produkList = collect();
        $outletCount = 0;
        $lokasiNama = null;

        if ($hasGps) {
            // Reverse geocode: cari desa outlet terdekat
            $lokasiNama = $this->cariNamaLokasi($lat, $lng);

            $haversine = RadiusHelper::getHaversineExpression($lat, $lng);
            $box = RadiusHelper::hitungBoundingBox($lat, $lng, $radius);

            $outletIds = Outlet::select('id')
                ->where('lat', '!=', 0)
                ->where('lng', '!=', 0)
                ->whereBetween('lat', [$box['lat_min'], $box['lat_max']])
                ->whereBetween('lng', [$box['lng_min'], $box['lng_max']])
                ->whereRaw("({$haversine}) <= ?", [$radius])
                ->pluck('id');

            $outletCount = $outletIds->count();

            if ($outletIds->isNotEmpty()) {
                $produkList = Produk::whereIn('outlet_id', $outletIds)
                    ->with('outlet')
                    ->orderBy('created_at', 'desc')
                    ->take(30)
                    ->get()
                    ->map(function ($p) {
                        $stok = HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id);
                        return [
                            'id'          => $p->id,
                            'nama'        => $p->nama,
                            'deskripsi'   => $p->deskripsi,
                            'harga'       => (float) $p->harga,
                            'satuan'      => $p->satuan,
                            'stok'        => $stok,
                            'tersedia'    => $stok > 0,
                            'outlet_id'   => $p->outlet_id,
                            'outlet_nama' => $p->outlet?->nama ?? 'Warung',
                        ];
                    });
            }
        } else {
            // Tidak ada GPS sama sekali: tampilkan produk terbaru
            $produkList = Produk::with('outlet')
                ->orderBy('created_at', 'desc')
                ->take(30)
                ->get()
                ->map(function ($p) {
                    $stok = HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id);
                    return [
                        'id'          => $p->id,
                        'nama'        => $p->nama,
                        'deskripsi'   => $p->deskripsi,
                        'harga'       => (float) $p->harga,
                        'satuan'      => $p->satuan,
                        'stok'        => $stok,
                        'tersedia'    => $stok > 0,
                        'outlet_id'   => $p->outlet_id,
                        'outlet_nama' => $p->outlet?->nama ?? 'Warung',
                    ];
                });
        }

        return view('konsumen.dashboard', [
            'produkList'  => $produkList,
            'outletCount' => $outletCount,
            'hasGps'      => $hasGps,
            'fromProfile' => $fromProfile,
            'lat'         => $lat,
            'lng'         => $lng,
            'radius'      => $radius,
            'lokasiNama'     => $lokasiNama,
            'requestHasGps'  => $requestHasGps,
        ]);
    }

    /**
     * Cari nama lokasi (desa/kecamatan) dari koordinat GPS.
     */
    private function cariNamaLokasi(float $lat, float $lng): ?string
    {
        $outlet = Outlet::select('desa_kelurahan', 'kecamatan', 'kabupaten')
            ->whereNotNull('desa_kelurahan')
            ->where('lat', '!=', 0)
            ->where('lng', '!=', 0)
            ->orderByRaw("(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) ASC", [$lat, $lng, $lat])
            ->first();

        if (! $outlet) return null;

        $parts = array_filter([$outlet->desa_kelurahan, $outlet->kecamatan]);
        return implode(', ', $parts);
    }

    /**
     * Update profil konsumen — PUT /konsumen/profil
     */
    public function updateProfil(Request $request)
    {
        $user = Auth::user();
        $valid = $request->validate([
            'nama' => 'required|string|max:200',
            'no_hp' => 'required|string|max:20',
            'email' => 'required|email|max:200',
        ]);
        $user->update($valid);
        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Ganti password konsumen — POST /konsumen/profil/password
     */
    public function gantiPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);
        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama salah']);
        }
        $user->update(['password' => Hash::make($request->new_password)]);
        return back()->with('success', 'Password berhasil diganti.');
    }

    /**
     * Laporan Konsumen — GET /konsumen/laporan
     */
    public function laporan(Request $request)
    {
        $user = Auth::user();
        $bulanFilter = $request->input('bulan', now()->format('Y-m'));
        [$tahun, $bulan] = explode('-', $bulanFilter);

        // Base query: order milik konsumen ini yang sudah selesai/diantar/diambil_kurir
        $orders = \Modules\Order\app\Models\Order::where('buyer_type', 'Konsumen')
            ->where('buyer_id', $user->id)
            ->whereIn('status', ['selesai', 'diantar', 'diambil_kurir'])
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->with(['items', 'outlet'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalOrder = $orders->count();
        $totalBelanja = $orders->sum('total_harga');

        // Grafik mingguan (4 minggu dalam bulan)
        $grafikMingguan = collect();
        for ($i = 1; $i <= 4; $i++) {
            $mingguMulai = now()->setYear($tahun)->setMonth($bulan)->startOfMonth()->addWeeks($i - 1);
            $mingguAkhir = (clone $mingguMulai)->addWeek();
            $total = $orders->filter(function ($o) use ($mingguMulai, $mingguAkhir) {
                return $o->created_at >= $mingguMulai && $o->created_at < $mingguAkhir;
            })->sum('total_harga');
            $grafikMingguan->push([
                'label'  => 'M' . $i,
                'total'  => $total,
            ]);
        }

        // Top 5 produk favorit
        $topProduk = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $nama = $item->produk_nama ?? 'Produk';
                $found = false;
                foreach ($topProduk as &$tp) {
                    if ($tp['nama'] === $nama) {
                        $tp['total_qty'] += $item->qty;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $topProduk[] = ['nama' => $nama, 'total_qty' => $item->qty];
                }
            }
        }
        usort($topProduk, fn($a, $b) => $b['total_qty'] <=> $a['total_qty']);
        $topProduk = array_slice($topProduk, 0, 5);

        // Riwayat order terakhir (10 terbaru)
        $riwayatOrder = $orders->take(10);

        // Status labels + colors
        $statusLabels = [
            'dibuat'        => 'Menunggu Konfirmasi',
            'diambil_kurir' => 'Diambil Kurir',
            'diantar'       => 'Sedang Diantar',
            'selesai'       => 'Selesai',
            'dibatalkan'    => 'Dibatalkan',
        ];
        $statusColors = [
            'dibuat'        => '#E8A23C',
            'diambil_kurir' => '#2196F3',
            'diantar'       => '#4CAF50',
            'selesai'       => '#4CAF50',
            'dibatalkan'    => '#F44336',
        ];

        return view('konsumen.laporan', compact(
            'totalOrder', 'totalBelanja', 'grafikMingguan', 'topProduk', 'riwayatOrder',
            'bulanFilter', 'statusLabels', 'statusColors'
        ));
    }

    /**
     * Daftar outlet — GET /konsumen/outlet
     */
    public function daftar(Request $request)
    {
        $user = Auth::user();
        $profile = $user?->konsumenProfile;

        $lat = (float) $request->input('lat', $profile->lat ?? 0);
        $lng = (float) $request->input('lng', $profile->lng ?? 0);
        $radius = (float) $request->input('radius', 1);
        $hasGps = $lat !== 0.0 && $lng !== 0.0;

        $outlets = collect();

        if ($hasGps) {
            $haversine = RadiusHelper::getHaversineExpression($lat, $lng);
            $box = RadiusHelper::hitungBoundingBox($lat, $lng, $radius);

            $outlets = Outlet::select(DB::raw("*, {$haversine} AS jarak_km"))
                ->where('lat', '!=', 0)
                ->where('lng', '!=', 0)
                ->whereBetween('lat', [$box['lat_min'], $box['lat_max']])
                ->whereBetween('lng', [$box['lng_min'], $box['lng_max']])
                ->whereRaw("({$haversine}) <= ?", [$radius])
                ->orderBy('jarak_km', 'asc')
                ->take(50)
                ->get();
        }

        return view('konsumen.outlet-list', [
            'outlets' => $outlets,
            'hasGps'  => $hasGps,
            'lat'     => $lat,
            'lng'     => $lng,
            'radius'  => $radius,
        ]);
    }
}