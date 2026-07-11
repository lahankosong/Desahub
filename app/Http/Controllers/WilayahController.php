<?php

namespace App\Http\Controllers;

use App\Models\WilayahProvinsi;
use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahDesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WilayahController extends Controller
{
    /**
     * GET /api/wilayah/provinsi
     * List all provinces.
     */
    public function provinsi(): JsonResponse
    {
        return response()->json(
            WilayahProvinsi::orderBy('nama')->get(['kode', 'nama'])
        );
    }

    /**
     * GET /api/wilayah/kabupaten?provinsi_kode=35
     * List regencies/cities by province code.
     */
    public function kabupaten(Request $request): JsonResponse
    {
        $provKode = $request->query('provinsi_kode');
        if (! $provKode) {
            return response()->json(['error' => 'provinsi_kode required'], 400);
        }

        return response()->json(
            WilayahKabupaten::where('provinsi_kode', $provKode)
                ->orderBy('nama')
                ->get(['kode', 'nama'])
        );
    }

    /**
     * GET /api/wilayah/kecamatan?kabupaten_kode=3525
     * List districts by regency code.
     */
    public function kecamatan(Request $request): JsonResponse
    {
        $kabKode = $request->query('kabupaten_kode');
        if (! $kabKode) {
            return response()->json(['error' => 'kabupaten_kode required'], 400);
        }

        return response()->json(
            WilayahKecamatan::where('kabupaten_kode', $kabKode)
                ->orderBy('nama')
                ->get(['kode', 'nama'])
        );
    }

    /**
     * GET /api/wilayah/desa?kecamatan_kode=3525010
     * List villages by district code.
     */
    public function desa(Request $request): JsonResponse
    {
        $kecKode = $request->query('kecamatan_kode');
        if (! $kecKode) {
            return response()->json(['error' => 'kecamatan_kode required'], 400);
        }

        return response()->json(
            WilayahDesa::where('kecamatan_kode', $kecKode)
                ->orderBy('nama')
                ->get(['kode', 'nama'])
        );
    }
}