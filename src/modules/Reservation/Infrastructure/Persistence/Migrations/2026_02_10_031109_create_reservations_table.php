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
            $table->string('status');
            $table->uuid('guest_profile_id')->index();
            $table->date('check_in');
            $table->date('check_out');
            $table->string('room_type');
            $table->string('assigned_room_number')->nullable();
            $table->json('special_requests')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

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
