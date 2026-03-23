<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stay_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->uuid('guest_uuid');
            $table->timestamp('first_reservation_at');

            $table->unique(['account_id', 'guest_uuid']);
            $table->index('guest_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stay_guests');
    }
};
