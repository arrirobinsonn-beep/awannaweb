<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengirimans', function (Blueprint $table) {
            $table->id();

            // Sumber aggregator: flik / sicepat / spx
            $table->string('sumber', 20)->index();

            // Identitas kiriman — natural key gabungan (sumber, no_resi)
            $table->string('no_resi')->index();
            $table->string('order_id', 100)->nullable();

            $table->string('kurir')->nullable();
            $table->string('service')->nullable();
            $table->string('nama_penerima')->nullable();
            $table->string('telepon', 32)->nullable();

            $table->text('alamat_lengkap')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 16)->nullable();

            $table->string('nama_produk')->nullable();
            $table->integer('jumlah')->unsigned()->default(1);

            $table->decimal('ongkir', 14, 2)->default(0);
            $table->decimal('nilai_paket', 14, 2)->default(0);
            $table->boolean('is_cod')->default(false);
            $table->decimal('nominal_cod', 14, 2)->default(0);

            $table->string('status')->nullable()->index();
            $table->string('catatan_kurir')->nullable();

            $table->date('tanggal_buat')->nullable()->index();
            $table->date('tanggal_pickup')->nullable();
            $table->date('tanggal_terkirim')->nullable();

            // Riwayat index status terakhir untuk lookup cepat di dashboard
            $table->string('file_sumber', 255)->nullable();

            $table->timestamps();

            // Satu kiriman dari aggregator yang sama = unik
            $table->unique(['sumber', 'no_resi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengirimans');
    }
};
