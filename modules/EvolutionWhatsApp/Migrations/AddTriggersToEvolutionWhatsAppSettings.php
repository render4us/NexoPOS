<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evolution_whatsapp_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('evolution_whatsapp_settings', 'disparar_ao_criar')) {
                $table->boolean('disparar_ao_criar')
                    ->default(false)
                    ->after('ativo');
            }

            if (! Schema::hasColumn('evolution_whatsapp_settings', 'mensagem_template_criacao')) {
                $table->text('mensagem_template_criacao')
                    ->nullable()
                    ->after('mensagem_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evolution_whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['disparar_ao_criar', 'mensagem_template_criacao']);
        });
    }
};
