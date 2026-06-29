<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = array_values(array_filter(
            ['market_api_key', 'active_theme_id', 'active_theme_version'],
            fn (string $column): bool => Schema::hasColumn('options_general', $column)
        ));

        if (empty($columns)) {
            return;
        }

        Schema::table('options_general', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
