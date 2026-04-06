<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('account_uuid')->index();
            $table->string('reservation_id')->index();
            $table->string('guest_id')->index();
            $table->string('status')->default('draft');
            $table->integer('subtotal_cents');
            $table->integer('tax_cents');
            $table->integer('total_cents');
            $table->string('currency')->default('usd');
            $table->string('stripe_customer_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
