<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapping export dinamis: untuk tiap template courier (flik/sicepat/spx),
     * simpan per kolom (column_index) → sumber isi (kolom shipping_orders,
     * nilai khusus/computed, teks tetap, atau kosong). Header diambil dari
     * template CSV yang di-upload admin (menu Aturan Export).
     */
    public function up(): void
    {
        Schema::create('export_template_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('template'); // flik | sicepat | spx
            $table->unsignedInteger('column_index');
            $table->string('header');
            $table->string('source_type'); // column | computed | static | empty
            $table->string('source_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['template', 'column_index']);
            $table->index('template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_template_mappings');
    }
};
