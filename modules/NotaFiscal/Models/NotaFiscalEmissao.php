<?php

namespace Modules\NotaFiscal\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $order_id
 * @property int    $n_nf
 * @property int    $serie
 * @property string $chave        44 dígitos
 * @property string $protocolo
 * @property string $xml_enviado
 * @property string $xml_retorno
 * @property string $status       pendente|autorizada|rejeitada|cancelada|erro
 * @property string $cstat
 * @property string $mensagem
 * @property int    $ambiente
 * @property string $danfce_path
 */
class NotaFiscalEmissao extends Model
{
    protected $table = 'notafiscal_emissoes';

    protected $fillable = [
        'order_id',
        'n_nf',
        'serie',
        'chave',
        'protocolo',
        'xml_enviado',
        'xml_retorno',
        'status',
        'cstat',
        'mensagem',
        'ambiente',
        'danfce_path',
    ];

    protected $casts = [
        'n_nf'    => 'integer',
        'serie'   => 'integer',
        'ambiente'=> 'integer',
    ];

    // ── Status constants ───────────────────────────────────────────────────────

    const STATUS_PENDENTE   = 'pendente';
    const STATUS_AUTORIZADA = 'autorizada';
    const STATUS_REJEITADA  = 'rejeitada';
    const STATUS_CANCELADA  = 'cancelada';
    const STATUS_ERRO       = 'erro';

    // ── Relationships ──────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
