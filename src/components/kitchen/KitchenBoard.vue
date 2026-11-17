<script setup lang="ts">
import { kitchenSlots } from '@/data/kitchenOrders'
import type { KitchenOrder } from '@/types/kitchen'
import OrderCard from './OrderCard.vue'

defineProps<{
  orders: KitchenOrder[]
}>()

const emit = defineEmits<{
  accept: [id: number]
  complete: [id: number]
}>()

function slotStyle(id: number) {
  const slot = kitchenSlots[id]
  if (!slot) return undefined
  return {
    gridColumn: String(slot.column),
    gridRow: String(slot.row),
  }
}
</script>

<template>
  <section class="kds-board" aria-label="Papan pesanan dapur">
    <OrderCard
      v-for="order in orders"
      :key="order.id"
      :order="order"
      :style="slotStyle(order.id)"
      @accept="emit('accept', $event)"
      @complete="emit('complete', $event)"
    />
  </section>
</template>
