<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('pending');
            $table->float('speed_factor')->default(1.0);
            $table->string('watermark_type')->nullable();
            $table->string('watermark_image_path')->nullable();
            $table->string('watermark_text')->nullable();
            $table->string('watermark_position')->nullable();
            $table->float('watermark_opacity')->nullable();
            $table->integer('target_width')->nullable();
            $table->integer('target_height')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
