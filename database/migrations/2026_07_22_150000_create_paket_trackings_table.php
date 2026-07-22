<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kiriman_actual_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_id')->nullable();
            $table->string('awb')->nullable();
            $table->string('kurir')->nullable();
            $table->string('service')->nullable();
            $table->date('tanggal_pembuatan')->nullable();
            $table->text('detail_penjemputan')->nullable();
            $table->decimal('cod', 15, 2)->nullable();
            $table->string('nama_shopper')->nullable();
            $table->string('no_telp')->nullable();
            $table->decimal('ongkir_sebelum_diskon', 15, 2)->nullable();
            $table->decimal('diskon', 15, 2)->nullable();
            $table->decimal('harga_setelah_diskon', 15, 2)->nullable();
            $table->decimal('nominal_cod', 15, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('status_terakhir_dari_3pl')->nullable();
            $table->string('nama_produk')->nullable();
            $table->string('provinsi')->nullable();
            $table->text('catatan_kurir')->nullable();
            $table->string('pod')->nullable();
            $table->string('scheduled_pickup')->nullable();
            $table->string('terakhir_update')->nullable();
            $table->string('nama_warehouse')->nullable();
            $table->string('sumber')->nullable();
            $table->decimal('komisi_cod', 15, 2)->nullable();
            $table->decimal('komisi_jagokurir', 15, 2)->nullable();
            $table->string('actual_pickup')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_trackings');
    }
};
