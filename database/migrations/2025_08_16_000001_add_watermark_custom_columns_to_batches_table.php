<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->float('watermark_scale')->nullable()->after('watermark_opacity');
            $table->float('watermark_custom_x')->nullable()->after('watermark_scale');
            $table->float('watermark_custom_y')->nullable()->after('watermark_custom_x');
            $table->float('watermark_rotation')->nullable()->after('watermark_custom_y');
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn([
                'watermark_scale',
                'watermark_custom_x',
                'watermark_custom_y',
                'watermark_rotation',
            ]);
        });
    }
};
