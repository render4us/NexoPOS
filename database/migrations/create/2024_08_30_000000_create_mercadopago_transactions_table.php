<?php
use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up() {
        Schema::createIfMissing('nexopos_mercadopago_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id');
            $table->string('status');
            $table->text('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('nexopos_mercadopago_transactions');
    }
};
