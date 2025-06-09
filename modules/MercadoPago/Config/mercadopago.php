<?php
return [
    'access_token' => ns()->option->get('mercadopago_access_token', env('MERCADOPAGO_ACCESS_TOKEN', '')),
    'terminal_id'  => ns()->option->get('mercadopago_terminal_id', env('MERCADOPAGO_TERMINAL_ID', '')),
];
