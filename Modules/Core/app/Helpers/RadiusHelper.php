<?php

namespace Modules\Core\app\Helpers;

/**
 * Helper untuk query radius berbasis koordinat GPS.
 *
 * Menggunakan formula Haversine dengan optimasi bounding-box
 * untuk MySQL tanpa index spasial (shared hosting).
 *
 * @see project.md bagian "Query radius 1km"
 */
class RadiusHelper
{
    /**
     * Radius bumi dalam kilometer.
     */
    public const EARTH_RADIUS_KM = 6371;

    /**
     * Hitung bounding box untuk optimasi query.
     *
     * Menghasilkan lat/lng min-max untuk filter WHERE awal,
     * supaya bisa pakai index biasa — HAVING baru menyaring
     * jarak presisi dari himpunan kecil hasil bounding box.
     *
     * @param float $lat Latitude titik pusat
     * @param float $lng Longitude titik pusat
     * @param float $radiusKm Radius dalam kilometer
     * @return array{lat_min: float, lat_max: float, lng_min: float, lng_max: float}
     */
    public static function hitungBoundingBox(float $lat, float $lng, float $radiusKm = 1.0): array
    {
        $latDelta = rad2deg($radiusKm / self::EARTH_RADIUS_KM);
        $lngDelta = rad2deg(asin($radiusKm / self::EARTH_RADIUS_KM) / cos(deg2rad($lat)));

        return [
            'lat_min' => $lat - $latDelta,
            'lat_max' => $lat + $latDelta,
            'lng_min' => $lng - $lngDelta,
            'lng_max' => $lng + $lngDelta,
        ];
    }

    /**
     * Raw SQL untuk menghitung jarak Haversine (tanpa SELECT).
     *
     * Gunakan ini sebagai bagian dari query SELECT:
     *   SELECT *, ({@see getHaversineExpression()}) AS jarak_km
     *
     * @param float $latUser Latitude pengguna
     * @param float $lngUser Longitude pengguna
     * @param string $latCol Nama kolom latitude di tabel (default: 'lat')
     * @param string $lngCol Nama kolom longitude di tabel (default: 'lng')
     * @return string Raw SQL expression
     */
    public static function getHaversineExpression(
        float $latUser,
        float $lngUser,
        string $latCol = 'lat',
        string $lngCol = 'lng'
    ): string {
        $r = self::EARTH_RADIUS_KM;

        return "({$r} * acos("
            . "cos(radians({$latUser})) * cos(radians({$latCol})) * "
            . "cos(radians({$lngCol}) - radians({$lngUser})) + "
            . "sin(radians({$latUser})) * sin(radians({$latCol}))"
            . "))";
    }

    /**
     * Tambah where bounding-box ke query builder.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param float $lat Latitude titik pusat
     * @param float $lng Longitude titik pusat
     * @param float $radiusKm Radius dalam kilometer
     * @param string $latCol Nama kolom latitude
     * @param string $lngCol Nama kolom longitude
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public static function tambahBoundingBox(
        $query,
        float $lat,
        float $lng,
        float $radiusKm = 1.0,
        string $latCol = 'lat',
        string $lngCol = 'lng'
    ) {
        $box = self::hitungBoundingBox($lat, $lng, $radiusKm);

        return $query
            ->whereBetween($latCol, [$box['lat_min'], $box['lat_max']])
            ->whereBetween($lngCol, [$box['lng_min'], $box['lng_max']]);
    }

    /**
     * Tambah HAVING jarak ke query builder.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param float $lat Latitude titik pusat
     * @param float $lng Longitude titik pusat
     * @param float $radiusKm Radius dalam kilometer
     * @param string $latCol Nama kolom latitude
     * @param string $lngCol Nama kolom longitude
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public static function tambahHavingJarak(
        $query,
        float $lat,
        float $lng,
        float $radiusKm = 1.0,
        string $latCol = 'lat',
        string $lngCol = 'lng'
    ) {
        $haversine = self::getHaversineExpression($lat, $lng, $latCol, $lngCol);

        return $query->havingRaw("{$haversine} <= ?", [$radiusKm]);
    }

    /**
     * Hitung jarak antara dua titik koordinat (dalam km).
     * Berguna untuk perhitungan di aplikasi (bukan di query).
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Jarak dalam kilometer
     */
    public static function hitungJarak(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos($latFrom) * cos($latTo)
            * sin($lngDelta / 2) * sin($lngDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }
}