<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')           // penerima notifikasi
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('from_user_id')      // pengirim (siapa yg trigger)
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('type');                // new_proposal, proposal_approved, proposal_declined, payment_confirmed
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();       // data tambahan (proposal_id, url, dll)
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
