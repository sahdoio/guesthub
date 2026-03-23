<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedSmallInteger('adults')->default(1)->after('check_out');
            $table->unsignedSmallInteger('children')->default(0)->after('adults');
            $table->unsignedSmallInteger('babies')->default(0)->after('children');
            $table->unsignedSmallInteger('pets')->default(0)->after('babies');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['adults', 'children', 'babies', 'pets']);
        });
    }
};
