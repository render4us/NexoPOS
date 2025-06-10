<form id="mercadoPagoForm" class="mt-6">
    @csrf

    <input type="hidden" name="order_id" value="{{ $order->id }}">
    <input type="hidden" name="amount" value="{{ $order->total }}">

    <label for="payment_type" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
        Método de Pagamento
    </label>
    <select name="payment_type" id="payment_type" required class="w-full border-gray-300 rounded-md dark:bg-gray-800 dark:text-white">
        <option value="credit_card">Cartão de Crédito</option>
        <option value="debit_card">Cartão de Débito</option>
        <option value="pix">PIX</option>
    </select>

    <button type="submit"
            class="mt-4 inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
        Enviar para o Mercado Pago
    </button>
</form>

<div id="mp-feedback" class="mt-4 text-sm text-gray-800 dark:text-gray-200"></div>

@push('scripts')
    <script>
        document.getElementById('mercadoPagoForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);
            const feedback = document.getElementById('mp-feedback');
            feedback.textContent = 'Enviando pagamento para o Mercado Pago...';

            fetch("{{ route('mercadopago.payment.intent') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: formData.get('order_id'),
                    amount: parseFloat(formData.get('amount')),
                    payment_type: formData.get('payment_type'),
                })
            })
                .then(res => res.json())
                .then(data => {
                    feedback.textContent = data.message ?? 'Resposta recebida.';
                    if (data.mercadopago_response) {
                        console.log('Resposta Mercado Pago:', data.mercadopago_response);
                    }
                })
                .catch(err => {
                    console.error(err);
                    feedback.textContent = 'Erro ao enviar pagamento.';
                });
        });
    </script>
@endpush
