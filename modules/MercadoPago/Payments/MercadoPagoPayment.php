<?php
namespace Modules\MercadoPago\Payments;

use App\Contracts\PaymentMethodInterface;
use Illuminate\Support\Facades\Http;

class MercadoPagoPayment implements PaymentMethodInterface
{
    public function getName(): string
    {
        return 'Mercado Pago';
    }

    public function getDescription(): string
    {
        return __('Payment via Mercado Pago Point Pro 2');
    }

    public function process(array $payload)
    {
        $token  = settings()->get('mercadopago_access_token');
        $device = settings()->get('mercadopago_device_id');

        $response = Http::withToken($token)
            ->post("https://api.mercadopago.com/point/integration-api/devices/{$device}/orders", [
                'external_reference' => $payload['transaction_id'],
                'title'              => 'Pagamento NexoPOS',
                'notification_url'   => url('/api/mercadopago/callback'),
                'total_amount'       => $payload['amount'],
                'items'              => $payload['items'],
            ]);

        return $response->json();
    }
}
