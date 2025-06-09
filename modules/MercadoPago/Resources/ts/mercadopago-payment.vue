<template>
    <sample-payment :identifier="identifier" :label="label" @submit="processPayment"></sample-payment>
</template>
<script>
import samplePayment from "~/pages/dashboard/pos/payments/sample-payment.vue";
import { nsHttpClient } from "~/bootstrap";

export default {
    name: 'mercadopago-payment',
    props: ['identifier', 'label'],
    components: { samplePayment },
    methods: {
        async processPayment() {
            try {
                const order = POS.order.getValue();
                const response = await nsHttpClient.post('/api/mercadopago/pay', { order }).toPromise();
                if (response.status === 'created' || response.status === 'paid') {
                    this.$emit('submit');
                } else {
                    nsSnackBar.error(response.message || 'Payment failed').subscribe();
                }
            } catch(e) {
                nsSnackBar.error(e.message || 'Payment error').subscribe();
            }
        }
    }
};
</script>
