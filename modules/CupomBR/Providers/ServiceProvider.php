<?php

namespace Modules\CupomBR\Providers;

use App\Classes\Hook;
use App\Providers\AppServiceProvider;

class ServiceProvider extends AppServiceProvider
{
    public function register(): void
    {
        // Adiciona "Cupom BR" como opção no seletor de template de recibo
        Hook::addFilter('ns-receipt-template-options', function (array $options) {
            $options['cupom_br'] = __('Cupom BR (Estilo Brasileiro)');
            return $options;
        });

        // Substitui o template de recibo quando "cupom_br" estiver selecionado
        Hook::addFilter('ns-web-receipt-template', function (string $template) {
            if (ns()->option->get('ns_invoice_receipt_template') === 'cupom_br') {
                return 'CupomBR::_cupom_br_receipt';
            }
            return $template;
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'CupomBR');
    }
}
