<?php

namespace Modules\MercadoPago\Settings;

use App\Services\SettingsPage;
use App\Classes\FormInput;
use App\Classes\SettingForm;

class MercadoPagoSettings extends SettingsPage
{
    const IDENTIFIER = 'mercadopago';
    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = SettingForm::form(
            title: __('Mercado Pago Settings'),
            description: __('Configure Mercado Pago API credentials.'),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'credentials',
                    label: __('API Credentials'),
                    fields: SettingForm::fields(
                        FormInput::text(
                            label: __('Access Token'),
                            name: 'mercadopago_access_token',
                            value: ns()->option->get('mercadopago_access_token'),
                            validation: 'required'
                        ),
                        FormInput::text(
                            label: __('Terminal ID'),
                            name: 'mercadopago_terminal_id',
                            value: ns()->option->get('mercadopago_terminal_id')
                        )
                    )
                )
            )
        );
    }
}
