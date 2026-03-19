<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kiosk_settings') && ! Schema::hasColumn('kiosk_settings', 'teste_pagamento_ativo')) {
            Schema::table('kiosk_settings', function (Blueprint $table) {
                $table->boolean('teste_pagamento_ativo')->default(false)->after('printer_columns');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kiosk_settings', 'teste_pagamento_ativo')) {
            Schema::table('kiosk_settings', function (Blueprint $table) {
                $table->dropColumn('teste_pagamento_ativo');
            });
        }
    }
};
