<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosk_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('kiosk_settings', 'cor_sidebar')) {
                $table->string('cor_sidebar', 10)->default('#111116')->after('cor_primaria');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kiosk_settings', function (Blueprint $table) {
            $table->dropColumn('cor_sidebar');
        });
    }
};
