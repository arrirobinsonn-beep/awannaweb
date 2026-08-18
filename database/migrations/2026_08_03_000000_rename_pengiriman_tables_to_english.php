<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lepas FK + index histories dulu agar rename kolom aman
        Schema::table('pengiriman_status_histories', function (Blueprint $table) {
            $table->dropForeign(['pengiriman_id']);
            $table->dropIndex(['pengiriman_id']);
            $table->dropIndex(['user_id']);
        });

        // Lepas index pengirimans sebelum rename kolom
        Schema::table('pengirimans', function (Blueprint $table) {
            $table->dropUnique(['sumber', 'no_resi']);
            $table->dropIndex(['sumber']);
            $table->dropIndex(['no_resi']);
            $table->dropIndex(['status']);
            $table->dropIndex(['tanggal_buat']);
        });

        // ── pengirimans → shipments ──
        Schema::table('pengirimans', function (Blueprint $table) {
            $table->renameColumn('sumber', 'source');
            $table->renameColumn('no_resi', 'tracking_number');
            $table->renameColumn('kurir', 'courier');
            $table->renameColumn('nama_penerima', 'recipient_name');
            $table->renameColumn('telepon', 'phone');
            $table->renameColumn('alamat_lengkap', 'full_address');
            $table->renameColumn('kecamatan', 'district');
            $table->renameColumn('kota', 'city');
            $table->renameColumn('provinsi', 'province');
            $table->renameColumn('kode_pos', 'postal_code');
            $table->renameColumn('nama_produk', 'product_name');
            $table->renameColumn('jumlah', 'quantity');
            $table->renameColumn('ongkir', 'shipping_fee');
            $table->renameColumn('nilai_paket', 'parcel_value');
            $table->renameColumn('nominal_cod', 'cod_amount');
            $table->renameColumn('catatan_kurir', 'courier_note');
            $table->renameColumn('tanggal_buat', 'created_date');
            $table->renameColumn('tanggal_pickup', 'pickup_date');
            $table->renameColumn('tanggal_terkirim', 'delivered_date');
            $table->renameColumn('file_sumber', 'source_file');
        });

        Schema::rename('pengirimans', 'shipments');

        Schema::table('shipments', function (Blueprint $table) {
            $table->unique(['source', 'tracking_number']);
            $table->index('source');
            $table->index('tracking_number');
            $table->index('status');
            $table->index('created_date');
        });

        // ── pengiriman_status_histories → shipment_status_histories ──
        Schema::table('pengiriman_status_histories', function (Blueprint $table) {
            $table->renameColumn('pengiriman_id', 'shipment_id');
            $table->renameColumn('catatan_kurir', 'courier_note');
            $table->renameColumn('dilihat', 'viewed_at');
        });

        Schema::rename('pengiriman_status_histories', 'shipment_status_histories');

        Schema::table('shipment_status_histories', function (Blueprint $table) {
            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->index('shipment_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_status_histories', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
            $table->dropIndex(['shipment_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropUnique(['source', 'tracking_number']);
            $table->dropIndex(['source']);
            $table->dropIndex(['tracking_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_date']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->renameColumn('source', 'sumber');
            $table->renameColumn('tracking_number', 'no_resi');
            $table->renameColumn('courier', 'kurir');
            $table->renameColumn('recipient_name', 'nama_penerima');
            $table->renameColumn('phone', 'telepon');
            $table->renameColumn('full_address', 'alamat_lengkap');
            $table->renameColumn('district', 'kecamatan');
            $table->renameColumn('city', 'kota');
            $table->renameColumn('province', 'provinsi');
            $table->renameColumn('postal_code', 'kode_pos');
            $table->renameColumn('product_name', 'nama_produk');
            $table->renameColumn('quantity', 'jumlah');
            $table->renameColumn('shipping_fee', 'ongkir');
            $table->renameColumn('parcel_value', 'nilai_paket');
            $table->renameColumn('cod_amount', 'nominal_cod');
            $table->renameColumn('courier_note', 'catatan_kurir');
            $table->renameColumn('created_date', 'tanggal_buat');
            $table->renameColumn('pickup_date', 'tanggal_pickup');
            $table->renameColumn('delivered_date', 'tanggal_terkirim');
            $table->renameColumn('source_file', 'file_sumber');
        });

        Schema::rename('shipments', 'pengirimans');

        Schema::table('pengirimans', function (Blueprint $table) {
            $table->unique(['sumber', 'no_resi']);
            $table->index('sumber');
            $table->index('no_resi');
            $table->index('status');
            $table->index('tanggal_buat');
        });

        Schema::table('shipment_status_histories', function (Blueprint $table) {
            $table->renameColumn('shipment_id', 'pengiriman_id');
            $table->renameColumn('courier_note', 'catatan_kurir');
            $table->renameColumn('viewed_at', 'dilihat');
        });

        Schema::rename('shipment_status_histories', 'pengiriman_status_histories');

        Schema::table('pengiriman_status_histories', function (Blueprint $table) {
            $table->foreign('pengiriman_id')->references('id')->on('pengirimans')->cascadeOnDelete();
            $table->index('pengiriman_id');
            $table->index('user_id');
        });
    }
};
