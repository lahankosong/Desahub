<?php

namespace Modules\Core\app\Helpers;

/**
 * Helper query radius 1km dengan Haversine + optimasi bounding-box.
 *
 * Karena shared hosting tidak punya index spasial andal, query menggunakan
 * filtering WHERE lat/lng bounding-box dulu (bisa pakai index biasa),
 * baru HAVING menyaring jarak presisi dari himpunan kecil hasil bounding-box.
 */
class RadiusHelper
{
    /**
     * Hitung bounding box ±radiusKm dari titik pusat.
     *
     * @param float $lat  Latitude titik pusat
     * @param float $lng  Longitude titik pusat
     * @param float $radiusKm Radius dalam kilometer
     * @return array ['lat_min', 'lat_max', 'lng_min', 'lng_max', 'lat', 'lng']
     */
    public static function boundingBox(float $lat, float $lng, float $radiusKm = 1.0): array
    {
        // 1 derajat latitude ≈ 111.32 km
        $latDelta = $radiusKm / 111.32;

        // 1 derajat longitude bervariasi tergantung latitude
        $lngDelta = $radiusKm / (111.32 * cos(deg2rad($lat)));

        return [
            'lat'     => $lat,
            'lng'     => $lng,
            'lat_min' => $lat - $latDelta,
            'lat_max' => $lat + $latDelta,
            'lng_min' => $lng - $lngDelta,
            'lng_max' => $lng + $lngDelta,
        ];
    }

    /**
     * Query Haversine dengan optimasi bounding-box.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param float $lat       Latitude titik pusat
     * @param float $lng       Longitude titik pusat
     * @param string $latColumn Nama kolom latitude di tabel (default: 'lat')
     * @param string $lngColumn Nama kolom longitude di tabel (default: 'lng')
     * @param float $radiusKm  Radius dalam kilometer (default: 1.0)
     * @param array  $selectColumns Kolom tambahan yang ingin di-select
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public static function applyRadius(
        $query,
        float $lat,
        float $lng,
        string $latColumn = 'lat',
        string $lngColumn = 'lng',
        float $radiusKm = 1.0,
        array $selectColumns = ['*']
    ) {
        $box = self::boundingBox($lat, $lng, $radiusKm);

        // Haversine formula
        $haversine = "(6371 * acos(
            cos(radians({$box['lat']})) * cos(radians({$latColumn})) *
            cos(radians({$lngColumn}) - radians({$box['lng']})) +
            sin(radians({$box['lat']})) * sin(radians({$latColumn}))
        ))";

        $queryString = $query->getQuery();
        // Use getConnection() properly
        $grammar = $queryString->getGrammar();

        return $query
            ->selectRaw(implode(', ', array_map(function ($col) {
                return $col === '*' ? $col : $col;
            }, $selectColumns)))
            ->selectRaw("{$haversine} as jarak_km")
            ->whereBetween($latColumn, [$box['lat_min'], $box['lat_max']])
            ->whereBetween($lngColumn, [$box['lng_min'], $box['lng_max']])
            ->having('jarak_km', '<=', $radiusKm)
            ->orderBy('jarak_km', 'asc');
    }

    /**
     * Raw SQL snippet Haversine untuk digunakan dalam query builder.
     *
     * @param float $lat
     * @param float $lng
     * @param string $latColumn
     * @param string $lngColumn
     * @return string
     */
    public static function haversineSql(
        float $lat,
        float $lng,
        string $latColumn = 'lat',
        string $lngColumn = 'lng'
    ): string {
        return "(6371 * acos(
            cos(radians({$lat})) * cos(radians({$latColumn})) *
            cos(radians({$lngColumn}) - radians({$lng})) +
            sin(radians({$lat})) * sin(radians({$latColumn}))
        ))";
    }

    /**
     * Hitung jarak antara dua titik koordinat (dalam km).
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float
     */
    public static function hitungJarak(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}