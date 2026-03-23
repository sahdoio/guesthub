<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stays', function (Blueprint $table) {
            $table->string('cover_image_path')->nullable()->after('amenities');
        });

        Schema::create('stay_images', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->unsignedBigInteger('stay_id');
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('stay_id')
                ->references('id')
                ->on('stays')
                ->cascadeOnDelete();

            $table->index(['stay_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stay_images');

        Schema::table('stays', function (Blueprint $table) {
            $table->dropColumn('cover_image_path');
        });
    }
};
