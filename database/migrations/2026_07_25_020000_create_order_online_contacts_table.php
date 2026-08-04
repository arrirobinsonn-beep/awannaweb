<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_online_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone_normalized')->nullable()->index();
            $table->string('cs_name')->nullable();
            $table->string('order_id')->nullable();
            $table->string('buyer_name')->nullable();
            $table->timestamps();

            $table->index('advertiser_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_online_contacts');
    }
};
