<?php

namespace Modules\NotaFiscal\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotaFiscalEmissoesTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notafiscal_emissoes')) {
            return;
        }

        Schema::create('notafiscal_emissoes', function (Blueprint $table) {
            $table->id();

            // ── Vínculo com o pedido ──────────────────────────────────────────
            $table->unsignedBigInteger('order_id')->index()
                ->comment('ID do pedido SnowSYS (nexopos_orders.id)');

            // ── Dados da nota ─────────────────────────────────────────────────
            $table->integer('n_nf')->nullable()
                ->comment('Número da nota fiscal (nNF)');
            $table->integer('serie')->nullable()
                ->comment('Série da nota');
            $table->string('chave', 44)->nullable()->index()
                ->comment('Chave de acesso de 44 dígitos');
            $table->string('protocolo', 15)->nullable()
                ->comment('Número do protocolo de autorização SEFAZ');

            // ── XMLs ──────────────────────────────────────────────────────────
            $table->longText('xml_enviado')->nullable()
                ->comment('XML assinado enviado à SEFAZ');
            $table->longText('xml_retorno')->nullable()
                ->comment('XML de retorno da SEFAZ (nfeProc ou rejição)');

            // ── Status e log ──────────────────────────────────────────────────
            $table->string('status', 20)->default('pendente')
                ->comment('pendente | autorizada | rejeitada | cancelada | erro');
            $table->string('cstat', 3)->nullable()
                ->comment('cStat retornado pela SEFAZ (100=autorizado, etc.)');
            $table->text('mensagem')->nullable()
                ->comment('xMotivo ou mensagem de erro');

            // ── Ambiente ──────────────────────────────────────────────────────
            $table->tinyInteger('ambiente')->default(2)
                ->comment('1=Produção  2=Homologação');

            // ── Arquivo DANFCE ────────────────────────────────────────────────
            $table->string('danfce_path', 255)->nullable()
                ->comment('Caminho relativo ao PDF DANFCE gerado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notafiscal_emissoes');
    }
}
