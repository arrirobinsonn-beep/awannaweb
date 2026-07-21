<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiriman_actuals', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('jenis'); // COD / TF
            $table->string('dashboard'); // SPX / FLIK / SICEPAT / PEACHTREE
            $table->integer('jumlah_resi')->default(0);
            $table->decimal('value_resi', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiriman_actuals');
    }
};
