<template>
  <div class="w-full h-full flex items-center justify-center bg-primary/10 dark:bg-primary-dark/10">
    <div class="bg-white dark:bg-gray-800 rounded shadow-lg p-6 max-w-md w-full text-center">
      <h2 class="text-2xl font-bold text-primary mb-4">
        {{ title || __('Sucesso') }}
      </h2>
      <p class="text-lg text-gray-700 dark:text-gray-300 mb-6">
        {{ message || __('Pagamento realizado com sucesso.') }}
      </p>
      <button
          class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-6 rounded text-lg"
          @click="submit"
      >
        {{ __('Imprimir e continuar') }}
      </button>
    </div>
  </div>
</template>

<script>
import { __ } from '~/libraries/lang';

export default {
  name: 'ns-pos-simple-popup',
  props: ['title', 'message', 'onSubmit', 'popup'],
  methods: {
    __,
    submit() {
      if (typeof this.onSubmit === 'function') {
        this.onSubmit(this.popup);
      }
    }
  }
};
</script>
