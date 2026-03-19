<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('number');
            $table->string('type');
            $table->integer('floor');
            $table->integer('capacity');
            $table->decimal('price_per_night', 10, 2);
            $table->string('status')->default('available');
            $table->json('amenities')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->index('account_id');
            $table->index('hotel_id');
            $table->unique(['hotel_id', 'number']);
            $table->index('status');
            $table->index('type');
            $table->index('floor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
