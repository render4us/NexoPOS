<?php

namespace Modules\NotaFiscal\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotaFiscalSettingsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notafiscal_settings')) {
            return;
        }

        Schema::create('notafiscal_settings', function (Blueprint $table) {
            $table->id();

            // ── Controle ─────────────────────────────────────────────────────
            $table->boolean('ativo')->default(false)
                ->comment('Habilita/desabilita emissão automática de NFC-e');

            // ── Ambiente ─────────────────────────────────────────────────────
            $table->tinyInteger('ambiente')->default(2)
                ->comment('1=Produção  2=Homologação (tpAmb)');

            // ── Dados da empresa emissora ─────────────────────────────────────
            $table->string('cnpj', 14)
                ->comment('CNPJ sem máscara (14 dígitos)');
            $table->string('razao_social', 60);
            $table->string('nome_fantasia', 60)->nullable();
            $table->string('ie', 14)->nullable()
                ->comment('Inscrição Estadual sem máscara');
            $table->string('cnae', 7)->nullable()
                ->comment('CNAE principal da empresa');

            // ── Endereço da empresa ───────────────────────────────────────────
            $table->string('uf', 2)
                ->comment('Sigla da UF (ex: SP, RJ, MG)');
            $table->string('cod_municipio', 7)
                ->comment('cMunFG - código IBGE do município');
            $table->string('municipio', 60);
            $table->string('cep', 8)->nullable()
                ->comment('CEP sem máscara');
            $table->string('logradouro', 60)->nullable();
            $table->string('numero', 10)->nullable();
            $table->string('complemento', 60)->nullable();
            $table->string('bairro', 60)->nullable();
            $table->string('telefone', 11)->nullable();

            // ── Regime tributário ─────────────────────────────────────────────
            $table->string('crt', 1)->default('1')
                ->comment('CRT: 1=Simples Nacional  2=SN Excesso  3=Regime Normal');

            // ── Série e numeração ─────────────────────────────────────────────
            $table->integer('serie')->default(1)
                ->comment('Série da NFC-e (ex: 1)');
            $table->integer('proximo_numero')->default(1)
                ->comment('Próximo nNF a emitir');

            // ── Certificado A1 ────────────────────────────────────────────────
            $table->longText('certificado_conteudo')->nullable()
                ->comment('Conteúdo do .pfx em base64');
            $table->string('certificado_senha', 255)->nullable()
                ->comment('Senha do certificado');

            // ── CSC para QR Code NFC-e ────────────────────────────────────────
            $table->string('csc', 36)->nullable()
                ->comment('Código de Segurança do Contribuinte');
            $table->string('csc_id', 6)->nullable()
                ->comment('Identificador do CSC (ex: 000001)');

            // ── Defaults fiscais ──────────────────────────────────────────────
            $table->string('ncm_padrao', 8)->nullable()
                ->comment('NCM padrão usado quando o produto não tem NCM próprio');
            $table->string('cfop_padrao', 4)->default('5102')
                ->comment('CFOP padrão: 5102=venda mercadoria; 5405=venda ST');
            $table->string('csosn_padrao', 3)->default('400')
                ->comment('CSOSN padrão para Simples Nacional');
            $table->string('cst_icms_padrao', 3)->nullable()
                ->comment('CST-ICMS padrão para Regime Normal');
            $table->decimal('aliquota_icms_padrao', 5, 2)->default(0.00)
                ->comment('Alíquota de ICMS padrão (%) para Regime Normal');

            // ── Flag de emissão automática ────────────────────────────────────
            $table->boolean('emitir_automaticamente')->default(true)
                ->comment('Se true, emite NFC-e automaticamente quando o pedido é pago');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notafiscal_settings');
    }
}
