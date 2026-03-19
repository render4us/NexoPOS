<?php

namespace Modules\EvolutionWhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionWhatsAppSetting extends Model
{
    protected $table = 'evolution_whatsapp_settings';

    protected $fillable = [
        'ativo',
        'disparar_ao_criar',
        'api_url',
        'api_key',
        'instance_name',
        'mensagem_template',
        'mensagem_template_criacao',
    ];

    protected $casts = [
        'ativo'             => 'boolean',
        'disparar_ao_criar' => 'boolean',
    ];

    public static function instance(): static
    {
        return static::firstOrCreate([], [
            'ativo'                     => false,
            'disparar_ao_criar'         => false,
            'api_url'                   => '',
            'api_key'                   => '',
            'instance_name'             => '',
            'mensagem_template'         => "✅ *Pedido #{pedido_id} confirmado!*\n\n📋 *Itens do seu pedido:*\n{itens}\n\n💰 *Total: R$ {total}*\n\n💳 Pagamento: {forma_pagamento}\n📅 {data} — {modo}\n\nObrigado pela preferência! 😊\n_{loja}_",
            'mensagem_template_criacao' => "🛒 *Pedido #{pedido_id} recebido!*\n\n📋 *Itens do seu pedido:*\n{itens}\n\n💰 *Total: R\$ {total}*\n\n📅 {data} — {modo}\n\nAguarde a confirmação do pagamento na maquininha. ⏳\n\n_{loja}_",
        ]);
    }
}
