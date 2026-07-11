<?php

namespace App\Console\Commands;

use App\Models\WilayahProvinsi;
use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahDesa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SeedWilayahFull extends Command
{
    protected $signature = 'wilayah:seed-full';
    protected $description = 'Download and seed all Indonesian region data (provinsi, kabupaten, kecamatan, desa)';

    const BASE = 'https://raw.githubusercontent.com/edwardsamuel/Wilayah-Administratif-Indonesia/master/csv/';

    const BATCH = 1000;

    public function handle(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        WilayahDesa::truncate();
        WilayahKecamatan::truncate();
        WilayahKabupaten::truncate();
        WilayahProvinsi::truncate();

        $this->importCsv('provinces.csv', WilayahProvinsi::class, ['kode', 'nama']);
        $this->importCsv('regencies.csv', WilayahKabupaten::class, ['kode', 'provinsi_kode', 'nama']);
        $this->importCsv('districts.csv', WilayahKecamatan::class, ['kode', 'kabupaten_kode', 'nama']);
        $this->importCsv('villages.csv', WilayahDesa::class, ['kode', 'kecamatan_kode', 'nama']);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info('✓ Provinsi: ' . WilayahProvinsi::count());
        $this->info('✓ Kabupaten: ' . WilayahKabupaten::count());
        $this->info('✓ Kecamatan: ' . WilayahKecamatan::count());
        $this->info('✓ Desa: ' . WilayahDesa::count());
    }

    private function importCsv(string $file, string $model, array $cols): void
    {
        $url = self::BASE . $file;
        $this->info("Downloading {$file}...");
        $resp = Http::timeout(180)->get($url);
        if ($resp->failed()) {
            $this->error("Failed: {$file}");
            return;
        }

        $lines = array_filter(explode("\n", trim($resp->body())), fn($l) => trim($l) !== '');
        $header = array_shift($lines);
        $total = count($lines);
        $this->info("Importing {$total} records...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $batch = [];
        $seen = [];   // dedupe by primary key (first column)
        $inserted = 0;
        $skipped = 0;
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < count($cols)) {
                $bar->advance();
                continue;
            }
            $data = [];
            foreach ($cols as $i => $col) {
                $data[$col] = trim($row[$i] ?? '');
            }
            $pk = $data[$cols[0]];
            if (isset($seen[$pk])) {
                $skipped++;
                $bar->advance();
                continue;
            }
            $seen[$pk] = true;
            $batch[] = $data;
            $inserted++;
            $bar->advance();

            if (count($batch) >= self::BATCH) {
                $model::insertOrIgnore($batch);
                $batch = [];
            }
        }
        if (! empty($batch)) {
            $model::insertOrIgnore($batch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ {$file}: {$inserted} inserted, {$skipped} duplicate skipped");
    }
}