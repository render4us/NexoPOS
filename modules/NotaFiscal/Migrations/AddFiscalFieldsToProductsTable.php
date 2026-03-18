<?php

namespace Modules\NotaFiscal\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos fiscais à tabela nexopos_products.
 *
 * Campos existentes aproveitados:
 *   - barcode: usado como cEAN / cEANTrib (se for GTIN válido)
 *   - tax_value: percentual de imposto já calculado pelo NexoPOS
 *   - name:  xProd
 *
 * Campos adicionados:
 *   - fiscal_ncm:       NCM (8 dígitos) - Nomenclatura Comum do Mercosul
 *   - fiscal_cfop:      CFOP (4 dígitos) - default 5102 (venda a consumidor)
 *   - fiscal_orig:      Origem da mercadoria (0=Nacional, 1=Estrangeira, etc.)
 *   - fiscal_csosn:     CSOSN para Simples Nacional (ex: 400, 500)
 *   - fiscal_cst_icms:  CST-ICMS para Lucro Presumido/Real (ex: 00, 10, 40)
 *   - fiscal_un_com:    Unidade comercial (UN, KG, LT, CX, etc.)
 */
class AddFiscalFieldsToProductsTable extends Migration
{
    public function up(): void
    {
        Schema::table('nexopos_products', function (Blueprint $table) {
            if (! Schema::hasColumn('nexopos_products', 'fiscal_ncm')) {
                $table->string('fiscal_ncm', 8)->nullable()->after('description')
                    ->comment('NCM - Nomenclatura Comum do Mercosul (8 dígitos)');
            }

            if (! Schema::hasColumn('nexopos_products', 'fiscal_cfop')) {
                $table->string('fiscal_cfop', 4)->nullable()->after('fiscal_ncm')
                    ->comment('CFOP - ex: 5102 venda a consumidor, 5405 venda ST');
            }

            if (! Schema::hasColumn('nexopos_products', 'fiscal_orig')) {
                $table->string('fiscal_orig', 1)->nullable()->default('0')->after('fiscal_cfop')
                    ->comment('Origem (0=Nacional, 1=Estrangeira importação direta, 2=Estrangeira adquirida no mercado interno)');
            }

            if (! Schema::hasColumn('nexopos_products', 'fiscal_csosn')) {
                $table->string('fiscal_csosn', 3)->nullable()->after('fiscal_orig')
                    ->comment('CSOSN para Simples Nacional (ex: 102, 400, 500, 900)');
            }

            if (! Schema::hasColumn('nexopos_products', 'fiscal_cst_icms')) {
                $table->string('fiscal_cst_icms', 3)->nullable()->after('fiscal_csosn')
                    ->comment('CST-ICMS para Regime Normal (ex: 00, 10, 20, 40, 41, 60)');
            }

            if (! Schema::hasColumn('nexopos_products', 'fiscal_un_com')) {
                $table->string('fiscal_un_com', 6)->nullable()->default('UN')->after('fiscal_cst_icms')
                    ->comment('Unidade comercial: UN, KG, LT, CX, PCT, etc.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nexopos_products', function (Blueprint $table) {
            $columns = [
                'fiscal_ncm', 'fiscal_cfop', 'fiscal_orig',
                'fiscal_csosn', 'fiscal_cst_icms', 'fiscal_un_com',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('nexopos_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
