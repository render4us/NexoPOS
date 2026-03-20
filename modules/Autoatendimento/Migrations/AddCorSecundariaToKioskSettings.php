<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kiosk_settings')) {
            return;
        }

        Schema::table('kiosk_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('kiosk_settings', 'cor_secundaria')) {
                $table->string('cor_secundaria', 20)->default('#f59e0b')->after('cor_primaria');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kiosk_settings', function (Blueprint $table) {
            if (Schema::hasColumn('kiosk_settings', 'cor_secundaria')) {
                $table->dropColumn('cor_secundaria');
            }
        });
    }
};
