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
            $table->string('number')->unique();
            $table->string('type');
            $table->integer('floor');
            $table->integer('capacity');
            $table->decimal('price_per_night', 10, 2);
            $table->string('status')->default('available');
            $table->json('amenities')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

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
