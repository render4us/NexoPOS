<?php
use App\Models\Order;
use App\Classes\Hook;
use Illuminate\Support\Facades\View;
?>
<style>
    .cupom-br * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    .cupom-br {
        font-family: 'Courier New', Courier, monospace;
        font-size: 13px;
        color: #111;
        background: #fff;
        width: 100%;
        max-width: 340px;
        margin: 0 auto;
        border: 1px solid #ccc;
        padding: 12px 14px;
    }
    .cupom-br .cupom-header {
        text-align: center;
        padding-bottom: 8px;
        margin-bottom: 6px;
    }
    .cupom-br .cupom-header img {
        max-height: 60px;
        max-width: 200px;
        margin-bottom: 6px;
    }
    .cupom-br .cupom-store-name {
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .cupom-br .cupom-header-info {
        font-size: 11px;
        color: #444;
        margin-top: 4px;
        line-height: 1.5;
    }
    .cupom-br .cupom-sep {
        border: none;
        border-top: 1px dashed #888;
        margin: 6px 0;
    }
    .cupom-br .cupom-sep-solid {
        border: none;
        border-top: 1px solid #555;
        margin: 6px 0;
    }
    .cupom-br .cupom-title {
        text-align: center;
        font-weight: bold;
        font-size: 13px;
        letter-spacing: 2px;
        padding: 4px 0;
        text-transform: uppercase;
    }
    .cupom-br .cupom-meta {
        font-size: 11px;
        line-height: 1.6;
    }
    .cupom-br .cupom-meta td {
        padding: 1px 0;
        vertical-align: top;
    }
    .cupom-br .cupom-meta td:last-child {
        text-align: right;
        font-weight: bold;
    }
    .cupom-br .cupom-items thead tr th {
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        padding: 3px 0;
        border-bottom: 1px dashed #888;
    }
    .cupom-br .cupom-items thead tr th:last-child,
    .cupom-br .cupom-items tbody tr td:last-child {
        text-align: right;
    }
    .cupom-br .cupom-items thead tr th:nth-child(2),
    .cupom-br .cupom-items tbody tr td:nth-child(2) {
        text-align: center;
    }
    .cupom-br .cupom-items tbody tr td {
        font-size: 12px;
        padding: 3px 0;
        border-bottom: 1px dotted #ccc;
        vertical-align: top;
    }
    .cupom-br .cupom-items tbody tr td.item-name {
        max-width: 140px;
        word-break: break-word;
        padding-right: 4px;
    }
    .cupom-br .cupom-totals td {
        font-size: 12px;
        padding: 2px 0;
    }
    .cupom-br .cupom-totals td:last-child {
        text-align: right;
    }
    .cupom-br .cupom-totals tr.total-final td {
        font-size: 15px;
        font-weight: bold;
        padding: 5px 0 3px;
        border-top: 1px solid #333;
        border-bottom: 1px solid #333;
    }
    .cupom-br .cupom-totals tr.total-troco td {
        font-weight: bold;
    }
    .cupom-br .cupom-payments td {
        font-size: 12px;
        padding: 2px 0;
    }
    .cupom-br .cupom-payments td:last-child {
        text-align: right;
    }
    .cupom-br .cupom-coupons td {
        font-size: 11px;
        padding: 2px 0;
        color: #c00;
    }
    .cupom-br .cupom-coupons td:last-child {
        text-align: right;
        font-weight: bold;
    }
    .cupom-br .cupom-footer {
        text-align: center;
        font-size: 11px;
        color: #555;
        margin-top: 6px;
        line-height: 1.6;
    }
    .cupom-br .cupom-note {
        font-size: 11px;
        font-style: italic;
        text-align: center;
        margin: 4px 0;
        color: #333;
    }
    .cupom-br table {
        width: 100%;
        border-collapse: collapse;
    }
    @media print {
        .cupom-br {
            border: none;
            max-width: 100%;
        }
    }
</style>

<div class="cupom-br">

    {{-- ── CABEÇALHO ───────────────────────────────────────────────── --}}
    <div class="cupom-header">
        @if ( ns()->option->get('ns_invoice_receipt_logo') )
            <img src="{{ ns()->option->get('ns_invoice_receipt_logo') }}"
                 alt="{{ ns()->option->get('ns_store_name') }}"><br>
        @endif
        <div class="cupom-store-name">{{ ns()->option->get('ns_store_name') }}</div>

        {{-- Coluna A e B do template (endereço, CNPJ, telefone, etc.) --}}
        @php
            $colA = trim($ordersService->orderTemplateMapping('ns_invoice_receipt_column_a', $order));
            $colB = trim($ordersService->orderTemplateMapping('ns_invoice_receipt_column_b', $order));
        @endphp

        @if ($colA || $colB)
            <div class="cupom-header-info">
                @if ($colA) {!! nl2br(e($colA)) !!} @endif
                @if ($colA && $colB) &nbsp;|&nbsp; @endif
                @if ($colB) {!! nl2br(e($colB)) !!} @endif
            </div>
        @endif
    </div>

    <hr class="cupom-sep-solid">

    {{-- ── IDENTIFICAÇÃO DO CUPOM ──────────────────────────────────── --}}
    <div class="cupom-title">Cupom de Venda</div>

    <hr class="cupom-sep">

    <table class="cupom-meta">
        <tr>
            <td>Pedido Nº</td>
            <td><strong>{{ $order->code }}</strong></td>
        </tr>
        <tr>
            <td>Data/Hora</td>
            <td>{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Tipo</td>
            <td>
                @php
                    $tipo = $order->type ?? '';
                    echo match(true) {
                        str_contains($tipo, 'eat_in')   => 'Mesa',
                        str_contains($tipo, 'takeaway') => 'Viagem',
                        default => ($tipo ?: 'Balcão'),
                    };
                @endphp
            </td>
        </tr>
        @if ($order->user)
        <tr>
            <td>Atendente</td>
            <td>{{ $order->user->username ?? $order->user->name }}</td>
        </tr>
        @endif
        @if ($order->customer && $order->customer->name && $order->customer->name !== 'Default Customer')
        <tr>
            <td>Cliente</td>
            <td>{{ $order->customer->first_name }} {{ $order->customer->last_name }}</td>
        </tr>
        @endif
    </table>

    <hr class="cupom-sep">

    {{-- ── ITENS ────────────────────────────────────────────────────── --}}
    <table class="cupom-items">
        <thead>
            <tr>
                <th style="text-align:left; width:44%">Item</th>
                <th style="width:18%">Qtd</th>
                <th style="width:18%; text-align:right">Unit.</th>
                <th style="width:20%; text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach( Hook::filter('ns-receipt-products', $order->combinedProducts) as $product )
            <tr>
                <td class="item-name">
                    <?php
                        $productName = View::make('pages.dashboard.orders.templates._product-name', compact('product'));
                        echo Hook::filter('ns-receipt-product-name', strip_tags($productName->render()), $product);
                    ?>
                </td>
                <td style="text-align:center">{{ (int) $product->quantity }}</td>
                <td style="text-align:right">{{ ns()->currency->define($product->unit_price) }}</td>
                <td style="text-align:right">{{ ns()->currency->define($product->total_price) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="cupom-sep">

    {{-- ── TOTAIS ───────────────────────────────────────────────────── --}}
    <table class="cupom-totals">

        {{-- Imposto produto (quando exibido separado) --}}
        @if( $order->settings?->where('key', 'ns_pos_price_with_tax')->first()?->value === 'no' && $order->products_tax_value > 0 )
        <tr>
            <td>Impostos s/ Produtos</td>
            <td>{{ ns()->currency->define($order->products_tax_value) }}</td>
        </tr>
        @endif

        <tr>
            <td>Subtotal</td>
            <td>{{ ns()->currency->define($order->subtotal) }}</td>
        </tr>

        @if ($order->discount > 0)
        <tr>
            <td>
                Desconto
                @if ($order->discount_type === 'percentage')
                    ({{ $order->discount_percentage }}%)
                @endif
            </td>
            <td>- {{ ns()->currency->define($order->discount) }}</td>
        </tr>
        @endif

        {{-- Cupons de desconto --}}
        @if ($order->total_coupons > 0)
        <tr>
            <td colspan="2"><hr class="cupom-sep" style="margin:3px 0"></td>
        </tr>
        @foreach ($order->coupons as $cupom)
        <tr class="cupom-coupons">
            <td>Cupom: {{ $cupom->name }} <span style="font-size:10px">({{ $cupom->code }})</span></td>
            <td>- {{ ns()->currency->define($cupom->value) }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="2"><hr class="cupom-sep" style="margin:3px 0"></td>
        </tr>
        @endif

        {{-- Impostos (breakdown ou resumo) --}}
        @if ( ns()->option->get('ns_invoice_display_tax_breakdown') === 'yes' )
            @foreach ($order->taxes as $tax)
            <tr>
                <td>{{ $tax->tax_name }} ({{ $order->tax_type === 'inclusive' ? 'Inc.' : 'Exc.' }})</td>
                <td>{{ ns()->currency->define($tax->tax_value) }}</td>
            </tr>
            @endforeach
        @else
            @if ($order->tax_value > 0)
            <tr>
                <td>{{ $order->tax_group?->name ?? 'Imposto' }} ({{ $order->tax_type === 'inclusive' ? 'Inc.' : 'Exc.' }})</td>
                <td>{{ ns()->currency->define($order->tax_value) }}</td>
            </tr>
            @endif
        @endif

        @if ($order->shipping > 0)
        <tr>
            <td>Frete</td>
            <td>{{ ns()->currency->define($order->shipping) }}</td>
        </tr>
        @endif

        <tr class="total-final">
            <td>TOTAL</td>
            <td>{{ ns()->currency->define($order->total) }}</td>
        </tr>
    </table>

    <hr class="cupom-sep">

    {{-- ── PAGAMENTOS ───────────────────────────────────────────────── --}}
    <table class="cupom-payments">
        @foreach ($order->payments as $payment)
        <tr>
            <td>{{ $paymentTypes[$payment['identifier']] ?? __('Pagamento') }}</td>
            <td>{{ ns()->currency->define($payment['value']) }}</td>
        </tr>
        @endforeach

        <tr>
            <td><strong>Pago</strong></td>
            <td><strong>{{ ns()->currency->define($order->tendered) }}</strong></td>
        </tr>

        {{-- Reembolso --}}
        @if ( in_array($order->payment_status, ['refunded', 'partially_refunded']) )
            @foreach ($order->refund as $refund)
            <tr>
                <td>Reembolso</td>
                <td>- {{ ns()->currency->define($refund->total) }}</td>
            </tr>
            @endforeach
        @endif

        {{-- Troco / Saldo devedor --}}
        @switch($order->payment_status)
            @case(Order::PAYMENT_PAID)
            <tr class="total-troco">
                <td>Troco</td>
                <td>{{ ns()->currency->define($order->change) }}</td>
            </tr>
            @break
            @case(Order::PAYMENT_PARTIALLY)
            <tr class="total-troco">
                <td>Saldo Devedor</td>
                <td>{{ ns()->currency->define(abs($order->change)) }}</td>
            </tr>
            @break
        @endswitch
    </table>

    {{-- ── OBSERVAÇÃO ───────────────────────────────────────────────── --}}
    @if ($order->note_visibility === 'visible' && $order->note)
    <hr class="cupom-sep">
    <p class="cupom-note"><strong>Obs:</strong> {{ $order->note }}</p>
    @endif

    <hr class="cupom-sep-solid">

    {{-- ── RODAPÉ ────────────────────────────────────────────────────── --}}
    <div class="cupom-footer">
        @if ( ns()->option->get('ns_invoice_receipt_footer') )
            <p>{{ ns()->option->get('ns_invoice_receipt_footer') }}</p>
        @else
            <p>Obrigado pela preferência!</p>
        @endif
        <p style="margin-top:4px; font-size:10px; color:#aaa">
            {{ now()->format('d/m/Y H:i:s') }}
        </p>
    </div>

</div>

@includeWhen( request()->query('autoprint') === 'true', '/pages/dashboard/orders/templates/_autoprint' )
