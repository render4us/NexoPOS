<?php
namespace Modules\MercadoPago\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('mercado_pago_transactions', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2);
            $table->string('status')->index();
            $table->string('transaction_id')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mercado_pago_transactions');
    }
};
