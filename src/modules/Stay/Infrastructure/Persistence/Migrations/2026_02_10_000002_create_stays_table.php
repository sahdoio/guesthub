<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('account_uuid')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('type')->default('room');
            $table->string('category')->default('hotel_room');
            $table->decimal('price_per_night', 10, 2)->nullable();
            $table->integer('capacity')->nullable()->default(2);
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('active');
            $table->json('amenities')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->index('type');
            $table->index('category');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stays');
    }
};
