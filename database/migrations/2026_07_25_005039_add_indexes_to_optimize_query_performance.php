<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. spending_harians ─────────────────────────────────
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->index('user_id', 'idx_sh_user_id');
            $table->index('tanggal', 'idx_sh_tanggal');
            $table->index('whitelist_id', 'idx_sh_whitelist_id');
            $table->index('product_id', 'idx_sh_product_id');
            $table->index(['user_id', 'tanggal'], 'idx_sh_user_tanggal');
        });

        // ─── 2. stock_movements ────────────────────────────────
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('product_id', 'idx_sm_product_id');
            $table->index('tanggal', 'idx_sm_tanggal');
            $table->index('gudang', 'idx_sm_gudang');
            $table->index(['product_id', 'tanggal'], 'idx_sm_product_tanggal');
            $table->index(['gudang', 'tanggal'], 'idx_sm_gudang_tanggal');
        });

        // ─── 3. notifications ──────────────────────────────────
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id', 'idx_notif_user_id');
            $table->index('is_read', 'idx_notif_is_read');
            $table->index(['user_id', 'is_read', 'created_at'], 'idx_notif_user_read_created');
        });

        // ─── 4. pembelian_barangs ──────────────────────────────
        Schema::table('pembelian_barangs', function (Blueprint $table) {
            $table->index('product_id', 'idx_pb_product_id');
            $table->index('tanggal', 'idx_pb_tanggal');
            $table->index('keterangan', 'idx_pb_keterangan');
            $table->index(['product_id', 'tanggal', 'keterangan'], 'idx_pb_product_tgl_ket');
        });

        // ─── 5. top_up_proposals ───────────────────────────────
        Schema::table('top_up_proposals', function (Blueprint $table) {
            $table->index('user_id', 'idx_tup_user_id');
            $table->index('status', 'idx_tup_status');
            $table->index(['user_id', 'status'], 'idx_tup_user_status');
        });

        // ─── 6. top_up_proposal_items ──────────────────────────
        Schema::table('top_up_proposal_items', function (Blueprint $table) {
            $table->index('proposal_id', 'idx_tupi_proposal_id');
            $table->index('whitelist_id', 'idx_tupi_whitelist_id');
            $table->index('payment_status', 'idx_tupi_payment_status');
        });

        // ─── 7. kiriman_actuals ────────────────────────────────
        Schema::table('kiriman_actuals', function (Blueprint $table) {
            $table->index('tanggal', 'idx_ka_tanggal');
            $table->index('dashboard', 'idx_ka_dashboard');
            $table->index(['tanggal', 'dashboard', 'jenis'], 'idx_ka_tgl_dash_jenis');
        });

        // ─── 8. paket_trackings ────────────────────────────────
        Schema::table('paket_trackings', function (Blueprint $table) {
            $table->index('kiriman_actual_id', 'idx_pt_kiriman_actual_id');
            $table->index('status', 'idx_pt_status');
            $table->index('tanggal_pembuatan', 'idx_pt_tanggal_pembuatan');
        });

        // ─── 9. regional_reports ───────────────────────────────
        Schema::table('regional_reports', function (Blueprint $table) {
            $table->index('user_id', 'idx_rr_user_id');
            $table->index('tanggal', 'idx_rr_tanggal');
        });

        // ─── 10. users ─────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->index('advertiser_id', 'idx_users_advertiser_id');
            $table->index('is_active', 'idx_users_is_active');
        });

        // ─── 11. whitelists ────────────────────────────────────
        Schema::table('whitelists', function (Blueprint $table) {
            $table->index('user_id', 'idx_wl_user_id');
            $table->index('status', 'idx_wl_status');
            $table->index('platform', 'idx_wl_platform');
        });

        // ─── 12. regional_cs_stats ─────────────────────────────
        Schema::table('regional_cs_stats', function (Blueprint $table) {
            $table->index('user_id', 'idx_rcs_user_id');
            $table->index('tanggal', 'idx_rcs_tanggal');
        });
    }

    public function down(): void
    {
        // spending_harians
        Schema::table('spending_harians', function (Blueprint $table) {
            $table->dropIndex('idx_sh_user_id');
            $table->dropIndex('idx_sh_tanggal');
            $table->dropIndex('idx_sh_whitelist_id');
            $table->dropIndex('idx_sh_product_id');
            $table->dropIndex('idx_sh_user_tanggal');
        });

        // stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_sm_product_id');
            $table->dropIndex('idx_sm_tanggal');
            $table->dropIndex('idx_sm_gudang');
            $table->dropIndex('idx_sm_product_tanggal');
            $table->dropIndex('idx_sm_gudang_tanggal');
        });

        // notifications
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notif_user_id');
            $table->dropIndex('idx_notif_is_read');
            $table->dropIndex('idx_notif_user_read_created');
        });

        // pembelian_barangs
        Schema::table('pembelian_barangs', function (Blueprint $table) {
            $table->dropIndex('idx_pb_product_id');
            $table->dropIndex('idx_pb_tanggal');
            $table->dropIndex('idx_pb_keterangan');
            $table->dropIndex('idx_pb_product_tgl_ket');
        });

        // top_up_proposals
        Schema::table('top_up_proposals', function (Blueprint $table) {
            $table->dropIndex('idx_tup_user_id');
            $table->dropIndex('idx_tup_status');
            $table->dropIndex('idx_tup_user_status');
        });

        // top_up_proposal_items
        Schema::table('top_up_proposal_items', function (Blueprint $table) {
            $table->dropIndex('idx_tupi_proposal_id');
            $table->dropIndex('idx_tupi_whitelist_id');
            $table->dropIndex('idx_tupi_payment_status');
        });

        // kiriman_actuals
        Schema::table('kiriman_actuals', function (Blueprint $table) {
            $table->dropIndex('idx_ka_tanggal');
            $table->dropIndex('idx_ka_dashboard');
            $table->dropIndex('idx_ka_tgl_dash_jenis');
        });

        // paket_trackings
        Schema::table('paket_trackings', function (Blueprint $table) {
            $table->dropIndex('idx_pt_kiriman_actual_id');
            $table->dropIndex('idx_pt_status');
            $table->dropIndex('idx_pt_tanggal_pembuatan');
        });

        // regional_reports
        Schema::table('regional_reports', function (Blueprint $table) {
            $table->dropIndex('idx_rr_user_id');
            $table->dropIndex('idx_rr_tanggal');
        });

        // users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_advertiser_id');
            $table->dropIndex('idx_users_is_active');
        });

        // whitelists
        Schema::table('whitelists', function (Blueprint $table) {
            $table->dropIndex('idx_wl_user_id');
            $table->dropIndex('idx_wl_status');
            $table->dropIndex('idx_wl_platform');
        });

        // regional_cs_stats
        Schema::table('regional_cs_stats', function (Blueprint $table) {
            $table->dropIndex('idx_rcs_user_id');
            $table->dropIndex('idx_rcs_tanggal');
        });
    }
};