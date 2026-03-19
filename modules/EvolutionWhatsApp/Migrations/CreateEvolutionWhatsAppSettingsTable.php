<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evolution_whatsapp_settings')) {
            return;
        }

        Schema::create('evolution_whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('ativo')->default(false);
            $table->boolean('disparar_ao_criar')->default(false);
            $table->string('api_url')->default('');
            $table->string('api_key')->default('');
            $table->string('instance_name')->default('');
            $table->text('mensagem_template')->nullable();
            $table->text('mensagem_template_criacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_whatsapp_settings');
    }
};
