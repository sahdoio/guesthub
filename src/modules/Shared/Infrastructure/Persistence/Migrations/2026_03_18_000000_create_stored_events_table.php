<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('event_category');
            $table->string('event_class');
            $table->string('aggregate_type')->nullable();
            $table->uuid('aggregate_id')->nullable();
            $table->json('payload');
            $table->timestamp('occurred_on');
            $table->timestamp('stored_at')->useCurrent();

            $table->index('event_type');
            $table->index('event_category');
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index('occurred_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_events');
    }
};
