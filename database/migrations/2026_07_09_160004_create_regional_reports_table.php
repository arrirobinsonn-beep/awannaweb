<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_reports', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('province', 100);
            $table->integer('lead')->default(0);
            $table->integer('paid')->default(0);
            $table->decimal('paid_ratio', 5, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Satu provinsi cuma boleh 1x per tanggal per user
            $table->unique(['tanggal', 'user_id', 'province'], 'regional_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_reports');
    }
};
