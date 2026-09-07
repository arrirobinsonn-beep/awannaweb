<?php

namespace Database\Seeders;

use App\Models\TrackingStatusRule;
use Illuminate\Database\Seeder;

/**
 * Aturan status bawaan (idempotent — updateOrCreate by kombinasi unik):
 * mapping raw status dashboard FLIK / SiCepat / SPX → status sistem Inggris.
 *
 * raw_status disimpan dengan huruf ASLI (tidak di-lowercase) agar tampil
 * konsisten di UI. Perbandingan dilakukan case-insensitive di service.
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
            // Kolom status NORMAL, tapi kolom 3PL terpisah (header beda) berisi
            // "Problem..." (diawali) → problem. starts_with = keunikan FLIK.
            ['flik', 'Dikonfirmasi', 'exact', 'problem', 'required', 'problem', 'starts_with', 1],
            ['flik', 'Dikonfirmasi', 'exact', 'waiting_pickup', 'none', null, 'contains', 10],
            ['flik', 'Sedang Diantar', 'exact', 'problem', 'required', 'problem', 'starts_with', 1],
            ['flik', 'Sedang Diantar', 'exact', 'in_transit', 'none', null, 'contains', 20],
            ['flik', 'Dicairkan', 'exact', 'delivered', 'none', null, 'contains', 30],
            ['flik', 'Terkirim', 'exact', 'delivered', 'none', null, 'contains', 40],
            ['flik', 'Dalam Transit Pengembalian', 'exact', 'returning', 'none', null, 'contains', 50],
            ['flik', 'Dikembalikan', 'exact', 'returned', 'none', null, 'contains', 60],

            // ── SICEPAT ─────────────────────────────────────────────
            ['sicepat', 'Menunggu Pickup', 'exact', 'waiting_pickup', 'none', null, 'contains', 10],
            ['sicepat', 'Proses Pengiriman', 'exact', 'in_transit', 'none', null, 'contains', 20],
            ['sicepat', 'Terkirim', 'exact', 'delivered', 'none', null, 'contains', 30],
            ['sicepat', 'Proses Retur', 'exact', 'returning', 'none', null, 'contains', 40],
            ['sicepat', 'Retur', 'exact', 'returned', 'none', null, 'contains', 50],
            ['sicepat', 'Bermasalah', 'exact', 'problem', 'none', null, 'contains', 60],

            // ── SPX ─────────────────────────────────────────────────
            // Pending Pickup/In Transit/Delivering + kolom OnHold Reason terisi → problem
            ['spx', 'Pending Pickup', 'exact', 'problem', 'required', null, 'contains', 1],
            ['spx', 'Pending Pickup', 'exact', 'waiting_pickup', 'none', null, 'contains', 10],
            ['spx', 'In Transit', 'exact', 'problem', 'required', null, 'contains', 1],
            ['spx', 'In Transit', 'exact', 'in_transit', 'none', null, 'contains', 20],
            ['spx', 'Delivering', 'exact', 'problem', 'required', null, 'contains', 1],
            ['spx', 'Delivering', 'exact', 'in_transit', 'none', null, 'contains', 30],
            ['spx', 'Delivered', 'exact', 'delivered', 'none', null, 'contains', 40],
            ['spx', 'Returning', 'exact', 'returning', 'none', null, 'contains', 50],
            ['spx', 'Returned', 'exact', 'returned', 'none', null, 'contains', 60],

            // ── IDX (IDExpress) ─────────────────────────────────────
            ['idx', 'Pending', 'exact', 'waiting_pickup', 'none', null, 'contains', 10],
            ['idx', 'Pickup', 'exact', 'waiting_pickup', 'none', null, 'contains', 11],
            ['idx', 'In Transit', 'exact', 'in_transit', 'none', null, 'contains', 20],
            ['idx', 'Out For Delivery', 'exact', 'in_transit', 'none', null, 'contains', 21],
            ['idx', 'Delivered', 'exact', 'delivered', 'none', null, 'contains', 40],
            ['idx', 'Return', 'exact', 'returning', 'none', null, 'contains', 50],
            ['idx', 'Returned', 'exact', 'returned', 'none', null, 'contains', 60],
            ['idx', 'Undeliverable', 'exact', 'problem', 'none', null, 'contains', 5],
        ];

        foreach ($rules as [$source, $rawStatus, $matchType, $status, $problemMode, $keyword, $problemMatchType, $sortOrder]) {
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
                    'problem_match_type' => $problemMatchType,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );
        }
    }
}
