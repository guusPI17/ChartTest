<template>
  <v-card v-if="meta" class="mb-6" variant="outlined">
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2" color="primary">mdi-account-circle</v-icon>
      {{ meta.name || 'Неизвестно' }}
    </v-card-title>
    <v-card-text>
      <div class="d-flex flex-wrap ga-2">
        <v-chip v-if="meta.account" prepend-icon="mdi-identifier" variant="tonal" color="primary">
          Счёт: {{ meta.account }}
        </v-chip>
        <v-chip v-if="meta.currency" prepend-icon="mdi-currency-usd" variant="tonal" color="success">
          {{ meta.currency }}
        </v-chip>
        <v-chip v-if="meta.leverage" prepend-icon="mdi-scale-balance" variant="tonal" color="info">
          {{ meta.leverage }}
        </v-chip>
        <v-chip prepend-icon="mdi-chart-line" variant="tonal" color="secondary">
          {{ operationCount }} операций
        </v-chip>
        <v-chip v-if="finalBalance !== null" prepend-icon="mdi-wallet" variant="tonal" :color="finalBalance >= 0 ? 'success' : 'error'">
          Баланс: {{ finalBalance.toFixed(2) }} {{ meta.currency }}
        </v-chip>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  meta: Object,
  operations: Array,
})

const operationCount = computed(() => props.operations?.length || 0)

const finalBalance = computed(() => {
  if (!props.operations?.length) return null
  let balance = 0
  for (const op of props.operations) {
    balance += op.profit
  }
  return Math.round(balance * 100) / 100
})
</script>
