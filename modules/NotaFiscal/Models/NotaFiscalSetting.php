<?php

namespace Modules\NotaFiscal\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property bool   $ativo
 * @property int    $ambiente          1=Produção  2=Homologação
 * @property string $cnpj
 * @property string $razao_social
 * @property string $nome_fantasia
 * @property string $ie
 * @property string $cnae
 * @property string $uf
 * @property string $cod_municipio
 * @property string $municipio
 * @property string $cep
 * @property string $logradouro
 * @property string $numero
 * @property string $complemento
 * @property string $bairro
 * @property string $telefone
 * @property string $crt             1|2|3
 * @property int    $serie
 * @property int    $proximo_numero
 * @property string $certificado_conteudo  base64 do .pfx
 * @property string $certificado_senha
 * @property string $csc
 * @property string $csc_id
 * @property string $ncm_padrao
 * @property string $cfop_padrao
 * @property string $csosn_padrao
 * @property string $cst_icms_padrao
 * @property float  $aliquota_icms_padrao
 * @property bool   $emitir_automaticamente
 */
class NotaFiscalSetting extends Model
{
    protected $table = 'notafiscal_settings';

    protected $fillable = [
        'ativo',
        'ambiente',
        'cnpj',
        'razao_social',
        'nome_fantasia',
        'ie',
        'cnae',
        'uf',
        'cod_municipio',
        'municipio',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'telefone',
        'crt',
        'serie',
        'proximo_numero',
        'certificado_conteudo',
        'certificado_senha',
        'csc',
        'csc_id',
        'ncm_padrao',
        'cfop_padrao',
        'csosn_padrao',
        'cst_icms_padrao',
        'aliquota_icms_padrao',
        'emitir_automaticamente',
    ];

    protected $casts = [
        'ativo'                  => 'boolean',
        'ambiente'               => 'integer',
        'serie'                  => 'integer',
        'proximo_numero'         => 'integer',
        'aliquota_icms_padrao'   => 'float',
        'emitir_automaticamente' => 'boolean',
    ];

    /**
     * Código IBGE das UFs, mapeado por sigla.
     */
    public static array $ufCodes = [
        'AC' => 12, 'AL' => 27, 'AM' => 13, 'AP' => 16, 'BA' => 29,
        'CE' => 23, 'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21,
        'MG' => 31, 'MS' => 50, 'MT' => 51, 'PA' => 15, 'PB' => 25,
        'PE' => 26, 'PI' => 22, 'PR' => 41, 'RJ' => 33, 'RN' => 24,
        'RO' => 11, 'RR' => 14, 'RS' => 43, 'SC' => 42, 'SE' => 28,
        'SP' => 35, 'TO' => 17,
    ];

    /**
     * Retorna o código IBGE da UF configurada.
     */
    public function getCodigoUf(): int
    {
        return self::$ufCodes[strtoupper($this->uf)] ?? 35;
    }

    /**
     * Retorna true se o emitente é Simples Nacional (CRT 1 ou 2).
     */
    public function isSimlesNacional(): bool
    {
        return in_array($this->crt, ['1', '2']);
    }
}
