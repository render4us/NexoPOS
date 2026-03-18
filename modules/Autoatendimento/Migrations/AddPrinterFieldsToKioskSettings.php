<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosk_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('kiosk_settings', 'printer_enabled')) {
                $table->boolean('printer_enabled')->default(false)->after('reset_timeout');
            }
            if (! Schema::hasColumn('kiosk_settings', 'printer_ip')) {
                $table->string('printer_ip', 50)->nullable()->after('printer_enabled');
            }
            if (! Schema::hasColumn('kiosk_settings', 'printer_port')) {
                $table->unsignedSmallInteger('printer_port')->default(9100)->after('printer_ip');
            }
            if (! Schema::hasColumn('kiosk_settings', 'printer_columns')) {
                $table->unsignedTinyInteger('printer_columns')->default(48)->after('printer_port');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kiosk_settings', function (Blueprint $table) {
            $table->dropColumn(['printer_enabled', 'printer_ip', 'printer_port', 'printer_columns']);
        });
    }
};
