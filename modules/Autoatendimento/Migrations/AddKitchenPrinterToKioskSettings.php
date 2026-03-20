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
            if (! Schema::hasColumn('kiosk_settings', 'kitchen_printer_enabled')) {
                $table->boolean('kitchen_printer_enabled')->default(false)->after('teste_pagamento_ativo');
            }
            if (! Schema::hasColumn('kiosk_settings', 'kitchen_printer_ip')) {
                $table->string('kitchen_printer_ip')->nullable()->after('kitchen_printer_enabled');
            }
            if (! Schema::hasColumn('kiosk_settings', 'kitchen_printer_port')) {
                $table->integer('kitchen_printer_port')->default(9100)->after('kitchen_printer_ip');
            }
            if (! Schema::hasColumn('kiosk_settings', 'kitchen_printer_columns')) {
                $table->integer('kitchen_printer_columns')->default(48)->after('kitchen_printer_port');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kiosk_settings', function (Blueprint $table) {
            foreach (['kitchen_printer_enabled', 'kitchen_printer_ip', 'kitchen_printer_port', 'kitchen_printer_columns'] as $col) {
                if (Schema::hasColumn('kiosk_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
