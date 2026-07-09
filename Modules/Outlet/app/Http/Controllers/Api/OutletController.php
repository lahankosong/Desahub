<?php

namespace Modules\Outlet\app\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\app\Helpers\RadiusHelper;

class OutletController extends Controller
{
    /**
     * Cari outlet dalam radius 1km.
     * GET /api/outlets?lat=-6.2&lng=106.8
     */
    public function index(Request $request)
    {
        $valid = $request->validate([
            'lat'       => 'required|numeric',
            'lng'       => 'required|numeric',
            'radius_km' => 'nullable|numeric|max:10',
        ]);

        $radiusKm = $valid['radius_km'] ?? 1.0;
        $box = RadiusHelper::boundingBox((float)$valid['lat'], (float)$valid['lng'], $radiusKm);

        $haversine = RadiusHelper::haversineSql($box['lat'], $box['lng']);

        $outlets = \DB::table('outlets')
            ->selectRaw("outlets.*, {$haversine} as jarak_km")
            ->whereBetween('lat', [$box['lat_min'], $box['lat_max']])
            ->whereBetween('lng', [$box['lng_min'], $box['lng_max']])
            ->having('jarak_km', '<=', $radiusKm)
            ->orderBy('jarak_km', 'asc')
            ->paginate(20);

        return response()->json($outlets);
    }

    /**
     * Detail outlet.
     * GET /api/outlets/{id}
     */
    public function show(int $id)
    {
        $outlet = \DB::table('outlets')->find($id);

        if (! $outlet) {
            return response()->json(['message' => 'Outlet tidak ditemukan'], 404);
        }

        $vertikals = \DB::table('outlet_vertikal')
            ->where('outlet_id', $id)
            ->pluck('vertikal');

        $outlet->vertikals = $vertikals;

        return response()->json($outlet);
    }
}