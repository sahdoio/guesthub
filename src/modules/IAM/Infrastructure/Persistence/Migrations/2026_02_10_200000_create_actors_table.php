<?php

declare(strict_types=1);

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
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->index('account_id');
            $table->index('user_id');
        });

        Schema::create('actor_type_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('actors')->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('actor_types')->cascadeOnDelete();

            $table->unique(['actor_id', 'type_id']);
            $table->index('type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_type_map');
        Schema::dropIfExists('actors');
    }
};
