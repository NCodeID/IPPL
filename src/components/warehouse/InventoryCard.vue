
<script setup lang="ts">
import type { InventoryItem } from '@/types/warehouse'

defineProps<{
  item: InventoryItem
}>()

const emit = defineEmits<{
  requestStock: [itemId: number]
}>()
</script>

<template>
  <article
    class="warehouse-inventory-card"
    :class="{ 'is-low-stock': item.stock <= item.minimumStock }"
  >
    <div class="warehouse-inventory-info">
      <h3>{{ item.name }}</h3>
      <div class="warehouse-inventory-divider"></div>
      <p>{{ item.description }}</p>
    </div>

    <button
      class="warehouse-inventory-request"
      type="button"
      @click="emit('requestStock', item.id)"
    >
      Request Stock
    </button>

    <div class="warehouse-inventory-stock">
      <strong>Stock tersedia</strong>
      <div>
        <span>{{ item.unit }}</span>
        <strong>{{ item.stock }}</strong>
      </div>
    </div>
  </article>
</template>
