<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_allocation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 20); // advertiser, cs, keuangan, admin
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['advertiser_id', 'role']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_allocation_settings');
    }
};
