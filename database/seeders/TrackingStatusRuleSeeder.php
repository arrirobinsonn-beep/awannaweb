<?php

namespace Database\Seeders;

use App\Models\TrackingStatusRule;
use Illuminate\Database\Seeder;

/**
 * Aturan status bawaan (idempotent — updateOrCreate by kombinasi unik):
 * mapping raw status dashboard FLIK / SiCepat / SPX → status sistem Inggris.
 *
 * Aturan bermasalah memakai problem_mode='required' dengan sort_order KECIL
 * agar dievaluasi lebih dulu: bila kolom masalah file terpenuhi → problem,
 * kalau tidak → jatuh ke rule normal untuk status yang sama.
 *
 * Admin bisa ubah/hapus/tambah lewat halaman Aturan Status.
 */
class TrackingStatusRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // ── FLIK ────────────────────────────────────────────────
            // Dikonfirmasi/Sedang Diantar + kolom 3PL berisi "problem" → problem
            ['flik', 'dikonfirmasi', 'exact', 'problem', 'required', 'problem', 1],
            ['flik', 'dikonfirmasi', 'exact', 'waiting_pickup', 'none', null, 10],
            ['flik', 'sedang diantar', 'exact', 'problem', 'required', 'problem', 1],
            ['flik', 'sedang diantar', 'exact', 'in_transit', 'none', null, 20],
            ['flik', 'dicairkan', 'exact', 'delivered', 'none', null, 30],
            ['flik', 'terkirim', 'exact', 'delivered', 'none', null, 40],
            ['flik', 'dalam transit pengembalian', 'exact', 'returning', 'none', null, 50],
            ['flik', 'dikembalikan', 'exact', 'returned', 'none', null, 60],

            // ── SICEPAT ─────────────────────────────────────────────
            ['sicepat', 'menunggu pickup', 'exact', 'waiting_pickup', 'none', null, 10],
            ['sicepat', 'proses pengiriman', 'exact', 'in_transit', 'none', null, 20],
            ['sicepat', 'terkirim', 'exact', 'delivered', 'none', null, 30],
            ['sicepat', 'proses retur', 'exact', 'returning', 'none', null, 40],
            ['sicepat', 'retur', 'exact', 'returned', 'none', null, 50],
            ['sicepat', 'bermasalah', 'exact', 'problem', 'none', null, 60],

            // ── SPX ─────────────────────────────────────────────────
            // Pending Pickup/In Transit/Delivering + kolom OnHold Reason terisi → problem
            ['spx', 'pending pickup', 'exact', 'problem', 'required', null, 1],
            ['spx', 'pending pickup', 'exact', 'waiting_pickup', 'none', null, 10],
            ['spx', 'in transit', 'exact', 'problem', 'required', null, 1],
            ['spx', 'in transit', 'exact', 'in_transit', 'none', null, 20],
            ['spx', 'delivering', 'exact', 'problem', 'required', null, 1],
            ['spx', 'delivering', 'exact', 'in_transit', 'none', null, 30],
            ['spx', 'delivered', 'exact', 'delivered', 'none', null, 40],
            ['spx', 'returning', 'exact', 'returning', 'none', null, 50],
            ['spx', 'returned', 'exact', 'returned', 'none', null, 60],
        ];

        foreach ($rules as [$source, $rawStatus, $matchType, $status, $problemMode, $keyword, $sortOrder]) {
            TrackingStatusRule::updateOrCreate(
                [
                    'source' => $source,
                    'raw_status' => $rawStatus,
                    'match_type' => $matchType,
                    'problem_mode' => $problemMode,
                    'status' => $status,
                ],
                [
                    'problem_keyword' => $keyword,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );
        }
    }
}
