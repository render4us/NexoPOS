<?php

namespace Modules\Autoatendimento\Models;

use Illuminate\Database\Eloquent\Model;

class KioskSetting extends Model
{
    protected $table = 'kiosk_settings';

    protected $fillable = [
        'ativo',
        'titulo',
        'subtitulo',
        'cor_primaria',
        'cor_sidebar',
        'video_url',
        'logo_url',
        'operator_user_id',
        'reset_timeout',
        'printer_enabled',
        'printer_ip',
        'printer_port',
        'printer_columns',
    ];

    protected $casts = [
        'ativo'            => 'boolean',
        'operator_user_id' => 'integer',
        'reset_timeout'    => 'integer',
        'printer_enabled'  => 'boolean',
        'printer_port'     => 'integer',
        'printer_columns'  => 'integer',
    ];

    /**
     * Retorna a instância de configuração (cria com defaults se não existir).
     */
    public static function instance(): static
    {
        return static::firstOrCreate([], [
            'ativo'            => true,
            'titulo'           => 'Bem-vindo!',
            'subtitulo'        => 'Como prefere seu pedido?',
            'cor_primaria'     => '#583f32',
            'cor_sidebar'      => '#111116',
            'video_url'        => '',
            'logo_url'         => '',
            'operator_user_id' => 1,
            'reset_timeout'    => 10,
            'printer_enabled'  => false,
            'printer_ip'       => '',
            'printer_port'     => 9100,
            'printer_columns'  => 48,
        ]);
    }
}
