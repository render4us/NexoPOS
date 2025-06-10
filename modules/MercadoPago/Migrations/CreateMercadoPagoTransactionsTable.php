<?php

namespace Modules\MercadoPago\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateMercadoPagoTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('mercadopago_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('transaction_id')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_type')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('nexopos_orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mercadopago_transactions');
    }
}
