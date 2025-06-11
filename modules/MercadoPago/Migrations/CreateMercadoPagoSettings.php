<?php

namespace Modules\MercadoPago\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMercadoPagoSettings extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_settings', function (Blueprint $table) {
            $table->id();
            $table->string('access_token');
            $table->string('terminal_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_settings');
    }
}
