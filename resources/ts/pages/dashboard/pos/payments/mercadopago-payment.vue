<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';
import { Popup } from '~/libraries/popup';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import nsPosSimplePopupVue from '~/popups/ns-pos-simple-popup.vue';
import { nsSnackBar } from '~/bootstrap';
import nsPosLoadingPopupVue from '~/popups/ns-pos-loading-popup.vue';

const props = defineProps(['label', 'identifier']);
const emit = defineEmits(['submit']);

const order = ref(null);
const settings = ref({});
const paymentType = ref('credit_card');
const loading = ref(false);

let orderSubscription = null;
let settingsSubscription = null;

onMounted(() => {
  orderSubscription = POS.order.subscribe(value => order.value = value);
  settingsSubscription = POS.settings.subscribe(value => settings.value = value);
});

onBeforeUnmount(() => {
  orderSubscription?.unsubscribe();
  settingsSubscription?.unsubscribe();
});

async function makeFullPayment() {
  const currentOrder = POS.order.getValue();

  Popup.show(nsPosConfirmPopupVue, {
    title: __('Confirm Payment'),
    message: __('Você confirma o pagamento com {paymentType} no valor de {total}?')
        .replace('{paymentType}', props.label)
        .replace('{total}', nsCurrency(currentOrder.total)),
    onAction: async (action) => {
      if (!action) return;

      loading.value = true;

      try {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 30000); // fail-safe

        const response = await nsHttpClient.post('/api/mercadopago/pay', {
          order: currentOrder,
          payment_type: paymentType.value
        }, { signal: controller.signal }).toPromise();

        clearTimeout(timeout);

        if (response.status === 'created' && response.transaction_id) {
          const transactionId = response.transaction_id;

          // 👇 Abre popup de loading
          const loadingPopup = Popup.show(nsPosLoadingPopupVue);

          let attempt = 0;
          let approved = false;

          while (attempt < 10) {
            const check = await nsHttpClient.post('/api/mercadopago/status', {
              transaction_id: transactionId
            }).toPromise();

            if (check.status === 'success') {
              approved = true;
              break;
            }

            await new Promise(resolve => setTimeout(resolve, 3000)); // aguarda 3s
            attempt++;
          }

          loadingPopup?.close?.(); // fecha o loading

          if (approved) {
            const total = currentOrder.total;
            const identifier = props.identifier;
            const label = props.label;

            Popup.show(nsPosSimplePopupVue, {
              title: __('Pagamento aprovado'),
              message: __('O pagamento com Mercado Pago foi confirmado.'),
              onSubmit: (popup) => {
                POS.addPayment({
                  value: total,
                  identifier,
                  selected: false,
                  label,
                  readonly: true
                });

                popup?.close?.();
                emit('submit');
              }
            });

          } else {
            nsSnackBar.error(__('Pagamento não confirmado. Tente novamente.')).subscribe();
          }

        } else {
          nsSnackBar.error(response.message || 'Erro ao processar pagamento').subscribe();
        }

      } catch (e) {
        if (e.name === 'AbortError') {
          nsSnackBar.error('Tempo de resposta excedido. Tente novamente.').subscribe();
        } else {
          console.error(e);
          nsSnackBar.error(e.message || 'Erro na comunicação com o servidor.').subscribe();
        }
      }

      loading.value = false;
    }
  });
}
</script>


<template>
  <div class="h-full w-full py-2">
    <div class="px-2 pb-2" v-if="order">
      <div class="grid grid-cols-2 gap-2">
        <div id="details" class="h-16 flex justify-between items-center border elevation-surface info text-xl md:text-3xl p-2">
          <span>{{ __('Total') }}:</span>
          <span>{{ nsCurrency(order.total) }}</span>
        </div>
        <div id="paid" class="h-16 flex justify-between items-center border elevation-surface success text-xl md:text-3xl p-2">
          <span>{{ __('Paid') }}:</span>
          <span>{{ nsCurrency(order.tendered) }}</span>
        </div>
        <div id="change" class="h-16 flex justify-between items-center border elevation-surface warning text-xl md:text-3xl p-2">
          <span>{{ __('Change') }}:</span>
          <span>{{ nsCurrency(order.change) }}</span>
        </div>
      </div>
    </div>

    <div class="mt-4 px-2">
      <select v-model="paymentType" class="w-full border rounded p-2 mb-4">
        <option value="credit_card">Cartão de Crédito</option>
        <option value="debit_card">Cartão de Débito</option>
        <option value="qr">Qr</option>
      </select>

      <button @click="makeFullPayment" class="bg-primary text-white py-3 w-full rounded text-xl">
        {{ __('Pagar com Mercado Pago') }}
      </button>
    </div>
  </div>
</template>
