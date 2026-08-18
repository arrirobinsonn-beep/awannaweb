<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master template export (courier). `export_template_mappings.template`
     * (string) merujuk ke `key` tabel ini (denormalized agar tak perlu alter
     * tabel mapping). Tiga template bawaan: flik / sicepat / spx.
     *
     * `couriers` = JSON daftar courier yang memakai template ini saat export
     * (dipakai `couriersForTemplate`). Kosong → `[key]` (nama template dipakai
     * sebagai courier, cocok untuk template custom).
     */
    public function up(): void
    {
        Schema::create('export_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->json('couriers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed 3 template bawaan (couriers sesuai legacy OrderTemplateExportService)
        DB::table('export_templates')->insert([
            [
                'key' => 'flik',
                'name' => 'FLIK',
                'couriers' => json_encode(['flix-tf', 'flix-idx', 'flix-sicepat', 'flix-spx']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sicepat',
                'name' => 'SiCepat',
                'couriers' => json_encode(['sicepat']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'spx',
                'name' => 'SPX',
                'couriers' => json_encode(['spx']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('export_templates');
    }
};
