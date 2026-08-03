<?php

namespace App\Console\Commands;

use App\Services\ShipmentImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportShipments extends Command
{
    protected $signature = 'shipment:import
        {file? : Path file CSV (default: cari di storage/app/pengiriman)}
        {--source= : sumber aggregator (flik/sicepat/spx) untuk mode multi}
        {--all : Proses semua file pengiriman di storage/app/pengiriman}
        {--month= : filter created_date (YYYY-MM)}';

    protected $description = 'Import & upsert shipping data from 3 aggregators (FLIK/SiCepat/SPX)';

    public function handle(ShipmentImportService $service): int
    {
        $disk = Storage::disk('local');
        $directory = 'pengiriman';

        $paths = $this->argument('file')
            ? [str_starts_with($this->argument('file'), DIRECTORY_SEPARATOR) ? $this->argument('file') : base_path($this->argument('file'))]
            : collect($disk->files($directory))->map(fn ($f) => $disk->path($f))->all();

        if (empty($paths)) {
            $this->error("Tidak ada file di {$directory}/. Letakkan file CSV di storage/app/pengiriman/.");

            return Command::FAILURE;
        }

        $totals = ['source' => [], 'inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'unmatched' => 0];

        foreach ($paths as $path) {
            if (! file_exists($path)) {
                $this->warn('File tidak ditemukan: '.$path);

                continue;
            }
            try {
                $result = $service->import($path);
                $totals['inserted'] += $result['inserted'];
                $totals['updated'] += $result['updated'];
                $totals['unchanged'] += $result['unchanged'];
                $totals['unmatched'] += count($result['unmatched']);
                $totals['source'][$result['source'] ?? '?'] = ($totals['source'][$result['source'] ?? '?'] ?? 0) + $result['inserted'] + $result['updated'] + $result['unchanged'];

                $line = 'OK '.basename($path).' → '.strtoupper($result['source'] ?? '-')
                    .' insert='.$result['inserted'].' update='.$result['updated'].' unchanged='.$result['unchanged'];

                if (! empty($result['unmatched'])) {
                    $line .= ' unmatched='.count($result['unmatched']).' (tidak disimpan)';
                    $names = collect($result['unmatched'])->pluck('product_name')->unique()->take(5)->implode(', ');
                    $this->line('   ⚠ Produk tak dikenal: '.$names);
                }

                $this->info($line);
            } catch (\Throwable $e) {
                $this->error('Gagal '.basename($path).': '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('SELESAI. Total insert='.$totals['inserted'].' update='.$totals['updated'].' unchanged='.$totals['unchanged'].' unmatched='.$totals['unmatched']);

        return Command::SUCCESS;
    }
}
