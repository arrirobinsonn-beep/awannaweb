<?php

namespace App\Console\Commands;

use App\Models\KirimanActual;
use App\Models\PaketTracking;
use App\Models\Product;
use Illuminate\Console\Command;

class BackfillPaketTrackingProductId extends Command
{
    protected $signature = 'paket:backfill-product-id';
    protected $description = 'Isi product_id di PaketTracking yang kosong berdasarkan KirimanActual';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        $chunks = PaketTracking::whereNotNull('kiriman_actual_id')
            ->whereNull('product_id')
            ->select('kiriman_actual_id')
            ->distinct()
            ->lazy(100);

        foreach ($chunks as $chunk) {
            $ka = KirimanActual::with('products.product')->find($chunk->kiriman_actual_id);
            if (! $ka) continue;

            $products = $ka->products;

            if ($products->count() === 0) continue;

            if ($products->count() === 1) {
                $pid = $products->first()->product_id;
                $affected = PaketTracking::where('kiriman_actual_id', $ka->id)
                    ->whereNull('product_id')
                    ->update(['product_id' => $pid]);
                $updated += $affected;
                $this->line("  KA #{$ka->id}: {$affected} rows → product_id={$pid}");
            } else {
                $this->line("  KA #{$ka->id}: {$products->count()} products — coba cocokkan via nama_produk...");
                $pts = PaketTracking::where('kiriman_actual_id', $ka->id)
                    ->whereNull('product_id')
                    ->get();

                foreach ($pts as $pt) {
                    $matched = false;
                    foreach ($products as $kap) {
                        $p = $kap->product;
                        if (! $p) continue;
                        if (strcasecmp(trim($pt->nama_produk ?? ''), trim($p->nama_produk)) === 0) {
                            $pt->product_id = $p->id;
                            $pt->save();
                            $updated++;
                            $matched = true;
                            break;
                        }
                    }
                    if (! $matched) {
                        $tokens = $this->tokenize($pt->nama_produk ?? '');
                        if (empty($tokens)) continue;
                        foreach ($products as $kap) {
                            $p = $kap->product;
                            if (! $p) continue;
                            $dbTokens = $this->tokenize($p->nama_produk);
                            $common = array_intersect($tokens, $dbTokens);
                            if (count($common) >= min(2, count($tokens))) {
                                $pt->product_id = $p->id;
                                $pt->save();
                                $updated++;
                                $matched = true;
                                break;
                            }
                        }
                    }
                    if (! $matched) $skipped++;
                }
            }
        }

        $this->info("Selesai. {$updated} records diupdate, {$skipped} dilewati.");
        return Command::SUCCESS;
    }

    private function tokenize(string $name): array
    {
        $parts = preg_split('/[^a-z0-9+.\-\/\(\)]+/i', $name);
        $parts = array_filter($parts, fn ($w) => strlen($w) >= 2);
        return array_values(array_map('strtolower', $parts));
    }
}
