<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kiosk_settings', 'banner_url')) {
            Schema::table('kiosk_settings', function (Blueprint $table) {
                $table->string('banner_url', 500)->nullable()->after('video_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kiosk_settings', 'banner_url')) {
            Schema::table('kiosk_settings', function (Blueprint $table) {
                $table->dropColumn('banner_url');
            });
        }
    }
};
