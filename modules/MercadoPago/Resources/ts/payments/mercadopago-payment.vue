<template>
    <div class="h-full w-full flex flex-col">
        <div class="p-2">
            <select v-model="paymentType" class="w-full border rounded p-2">
                <option value="credit_card">Cartão de Crédito</option>
                <option value="debit_card">Cartão de Débito</option>
                <option value="pix">PIX</option>
            </select>
        </div>
        <sample-payment
            :identifier="identifier"
            :label="label"
            @submit="processPayment"/>
    </div>
</template>
<script>
import samplePayment from "~/pages/dashboard/pos/payments/sample-payment.vue";
import { nsHttpClient, nsSnackBar } from "~/bootstrap.js";

export default {
    name: 'mercadopago-payment',
    props: ['identifier', 'label'],
    components: { samplePayment },
    data() {
        return {
            paymentType: 'credit_card',
        };
    },
    mounted() {
        console.log('MercadoPago payment component mounted');
    },
    methods: {
        async processPayment() {
            try {
                const order = POS.order.getValue();
                console.log('Sending order to /api/mercadopago/pay', order, 'type', this.paymentType);
                const response = await nsHttpClient.post('/api/mercadopago/pay', { order, payment_type: this.paymentType }).toPromise();
                console.log('MercadoPago pay response', response);
                if (response.status === 'created' || response.status === 'paid') {
                    this.$emit('submit');
                } else {
                    nsSnackBar.error(response.message || 'Payment failed').subscribe();
                }
            } catch(e) {
                console.error(e);
                nsSnackBar.error(e.message || 'Payment error').subscribe();
            }
        }
    }
};
</script>
