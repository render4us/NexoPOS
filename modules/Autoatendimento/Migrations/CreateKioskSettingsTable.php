<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kiosk_settings')) {
            Schema::create('kiosk_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('ativo')->default(true);
                $table->string('titulo')->default('Bem-vindo!');
                $table->string('subtitulo')->default('Como prefere seu pedido?');
                $table->string('cor_primaria', 20)->default('#583f32');
                $table->string('cor_sidebar', 10)->default('#111116');
                $table->string('video_url')->nullable();
                $table->string('logo_url')->nullable();
                $table->unsignedBigInteger('operator_user_id')->default(1);
                $table->unsignedInteger('reset_timeout')->default(10);
                $table->boolean('printer_enabled')->default(false);
                $table->string('printer_ip', 50)->nullable();
                $table->unsignedSmallInteger('printer_port')->default(9100);
                $table->unsignedTinyInteger('printer_columns')->default(48);
                $table->boolean('teste_pagamento_ativo')->default(false);
                $table->timestamps();
            });
        } else {
            // Tabela já existe — garante colunas adicionadas em migrações posteriores
            Schema::table('kiosk_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('kiosk_settings', 'cor_sidebar')) {
                    $table->string('cor_sidebar', 10)->default('#111116')->after('cor_primaria');
                }
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
                if (! Schema::hasColumn('kiosk_settings', 'teste_pagamento_ativo')) {
                    $table->boolean('teste_pagamento_ativo')->default(false)->after('printer_columns');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_settings');
    }
};
