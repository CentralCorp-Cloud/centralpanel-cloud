<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('options_bgs') || Schema::hasColumn('options_bgs', 'video_url')) {
            return;
        }

        Schema::table('options_bgs', function (Blueprint $table) {
            $table->string('video_url', 500)->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('options_bgs') || !Schema::hasColumn('options_bgs', 'video_url')) {
            return;
        }

        Schema::table('options_bgs', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
};
