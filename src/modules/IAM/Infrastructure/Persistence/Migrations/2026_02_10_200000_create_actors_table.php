<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('profile_type')->nullable();
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->index(['profile_type', 'profile_id']);
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actors');
    }
};
