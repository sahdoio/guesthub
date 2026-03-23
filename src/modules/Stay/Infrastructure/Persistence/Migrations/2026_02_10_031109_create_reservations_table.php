<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->uuid('account_uuid')->nullable();
            $table->foreignId('stay_id')->constrained('stays')->cascadeOnDelete();
            $table->uuid('stay_uuid')->nullable();
            $table->string('status');
            $table->uuid('guest_id')->index();
            $table->date('check_in');
            $table->date('check_out');
            $table->json('special_requests')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->index('account_id');
            $table->index('stay_id');
            $table->index('status');
            $table->index('check_in');
            $table->index('check_out');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
