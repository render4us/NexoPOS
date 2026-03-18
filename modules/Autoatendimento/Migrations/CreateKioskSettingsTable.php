<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('ativo')->default(true);
            $table->string('titulo')->default('Bem-vindo!');
            $table->string('subtitulo')->default('Como prefere seu pedido?');
            $table->string('cor_primaria', 20)->default('#583f32');
            $table->string('video_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->unsignedBigInteger('operator_user_id')->default(1);
            $table->unsignedInteger('reset_timeout')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_settings');
    }
};
