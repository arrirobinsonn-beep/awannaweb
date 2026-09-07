<?php

namespace Database\Seeders;

use App\Models\TrackingHeaderMapping;
use App\Services\AggregatorTrackingImportService;
use Illuminate\Database\Seeder;

/**
 * Mapping header CSV → kolom database BAWAAN untuk tracking dashboard
 * aggregator (FLIK / SiCepat / SPX).
 *
 * Sumber header adalah file template asli di `training/templateTracking/`
 * (header_flix.csv, header_sicepat.csv, header_spx.csv). Tiap header dicocokkan
 * ke kolom DB memakai logika alias yang SAMA dengan import tracking
 * (AggregatorTrackingImportService::extractDefaultMapping) sehingga default
 * konsisten — tanpa menebak teks header satu per satu di kode.
 *
 * Idempotent (updateOrCreate per source+header): mapping admin yang sudah ada
 * tidak dihapus; nilai db_column untuk header bawaan dikembalikan ke default.
 * Setelah di-seed, admin bebas mengedit lewat halaman Aturan Status.
 */
class TrackingHeaderMappingSeeder extends Seeder
{
    public function run(): void
    {
        $dir = base_path('training/templateTracking');

        $files = [
            'flik' => $dir.'/header_flix.csv',
            'sicepat' => $dir.'/header_sicepat.csv',
            'spx' => $dir.'/header_spx.csv',
            'idx' => $dir.'/header_idx.csv',
        ];

        $service = new AggregatorTrackingImportService;

        foreach ($files as $expectedSource => $path) {
            if (! file_exists($path)) {
                $this->command?->warn("File template tracking tidak ditemukan: {$path} (source {$expectedSource} dilewati)");

                continue;
            }

            $result = $service->extractDefaultMapping($path, $expectedSource);
            $source = $result['source'] ?: $expectedSource;

            $count = 0;
            foreach ($result['mapping'] as $header => $dbColumn) {
                TrackingHeaderMapping::updateOrCreate(
                    ['source' => $source, 'header' => $header],
                    ['db_column' => $dbColumn],
                );
                $count++;
            }

            $this->command?->info("Tracking header mapping {$source}: {$count} kolom di-seed.");
        }
    }
}
