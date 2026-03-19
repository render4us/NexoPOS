<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('ativo')->default(false);
            $table->string('api_url')->default('');
            $table->string('api_key')->default('');
            $table->string('instance_name')->default('');
            $table->text('mensagem_template')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_whatsapp_settings');
    }
};
