<?php

namespace App\Console\Commands;

use App\Models\WilayahProvinsi;
use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahDesa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ImportWilayahData extends Command
{
    protected $signature = 'wilayah:import {--source=remote : Source data: remote or local} {--path= : Local CSV path if source is local}';
    protected $description = 'Import Indonesian administrative region data from open source repositories';

    const FILES = ['provinces.csv', 'regencies.csv', 'districts.csv', 'villages.csv'];

    const URLS = [
        'https://raw.githubusercontent.com/edwardsamuel/Wilayah-Administratif-Indonesia/master/csv/',
        'https://raw.githubusercontent.com/kodewilayah/permendagri-72-2019/main/csv/',
    ];

    public function handle(): void
    {
        $source = $this->option('source');

        if ($source === 'remote') {
            $this->importFromRemote();
        } else {
            $this->importFromLocal();
        }
    }

    private function importFromRemote(): void
    {
        // Find working base URL
        $baseUrl = null;
        foreach (self::URLS as $url) {
            $testUrl = $url . 'provinces.csv';
            $resp = Http::timeout(10)->head($testUrl);
            if ($resp->successful()) {
                $baseUrl = $url;
                break;
            }
        }

        if (! $baseUrl) {
            $this->error('No working remote source found. Run: php artisan db:seed --class=WilayahSeeder (provinces only)');
            $this->warn('Or use local CSV: php artisan wilayah:import --source=local --path=/path/to/csv');
            return;
        }

        $this->info("Using: {$baseUrl}");

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        WilayahDesa::truncate();
        WilayahKecamatan::truncate();
        WilayahKabupaten::truncate();
        WilayahProvinsi::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $typeMap = [
            'provinces.csv' => ['model' => WilayahProvinsi::class, 'cols' => ['kode', 'nama']],
            'regencies.csv' => ['model' => WilayahKabupaten::class, 'cols' => ['kode', 'provinsi_kode', 'nama']],
            'districts.csv' => ['model' => WilayahKecamatan::class, 'cols' => ['kode', 'kabupaten_kode', 'nama']],
            'villages.csv' => ['model' => WilayahDesa::class, 'cols' => ['kode', 'kecamatan_kode', 'nama']],
        ];

        foreach (self::FILES as $file) {
            $type = str_replace('.csv', '', $file);
            $url = $baseUrl . $file;

            $this->info("Downloading {$file}...");
            $response = Http::timeout(120)->get($url);

            if ($response->failed()) {
                $this->error("Failed to download {$file}");
                continue;
            }

            $lines = explode("\n", trim($response->body()));
            $header = array_shift($lines);
            $total = count($lines);
            $this->info("Importing {$total} records...");
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $inserted = 0;
            $config = $typeMap[$file];
            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (count($row) < count($config['cols'])) { $bar->advance(); continue; }
                $data = [];
                foreach ($config['cols'] as $i => $col) {
                    $data[$col] = trim($row[$i] ?? '');
                }
                $config['model']::insert($data);
                $inserted++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("✓ {$type}: {$inserted} records");
        }

        $this->newLine();
        $this->info('Import completed!');
        $this->info('  Provinces: ' . WilayahProvinsi::count());
        $this->info('  Regencies: ' . WilayahKabupaten::count());
        $this->info('  Districts: ' . WilayahKecamatan::count());
        $this->info('  Villages: ' . WilayahDesa::count());
    }

    private function importFromLocal(): void
    {
        $path = $this->option('path') ?: storage_path('wilayah');
        $this->info("Importing from: {$path}");

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return;
        }

        $map = [
            'provinces.csv' => fn($r) => WilayahProvinsi::insert(['kode' => $r[0], 'nama' => $r[1]]),
            'regencies.csv' => fn($r) => WilayahKabupaten::insert(['kode' => $r[0], 'provinsi_kode' => $r[1], 'nama' => $r[2]]),
            'districts.csv' => fn($r) => WilayahKecamatan::insert(['kode' => $r[0], 'kabupaten_kode' => $r[1], 'nama' => $r[2]]),
            'villages.csv' => fn($r) => WilayahDesa::insert(['kode' => $r[0], 'kecamatan_kode' => $r[1], 'nama' => $r[2]]),
        ];

        foreach ($map as $file => $callback) {
            $fp = $path . '/' . $file;
            if (! file_exists($fp)) {
                $this->warn("File not found: {$file}");
                continue;
            }
            $this->info("Importing {$file}...");
            $h = fopen($fp, 'r');
            fgetcsv($h);
            $count = 0;
            while (($r = fgetcsv($h)) !== false) {
                if (count($r) < 2) continue;
                $callback($r);
                $count++;
            }
            fclose($h);
            $this->info("  → {$count} records");
        }

        $this->info('Import completed!');
    }
}