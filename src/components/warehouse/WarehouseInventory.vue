
<script setup lang="ts">
import type { InventoryItem } from '@/types/warehouse'
import InventoryCard from './InventoryCard.vue'

defineProps<{
  items: InventoryItem[]
  searchQuery: string
}>()

const emit = defineEmits<{
  'update:searchQuery': [value: string]
  requestStock: [itemId: number]
}>()
</script>

<template>
  <section class="warehouse-inventory">
    <div class="warehouse-section-heading">
      <div>
        <span class="warehouse-section-eyebrow">INVENTORY MANAGEMENT</span>
        <h1>Daftar Bahan Baku & Stok Fisik</h1>
        <p>Pantau ketersediaan bahan baku operasional restoran.</p>
      </div>

    </div>

    <span class="warehouse-item-count">
      {{ items.length }} Bahan Baku
    </span>

    <div v-if="items.length > 0" class="warehouse-inventory-grid">
      <InventoryCard
        v-for="item in items"
        :key="item.id"
        :item="item"
        @request-stock="emit('requestStock', $event)"
      />
    </div>

    <div v-else class="warehouse-empty-inventory">
      <span>S</span>
      <h3>Bahan baku tidak ditemukan</h3>
      <p>Coba gunakan kata kunci pencarian yang berbeda.</p>
    </div>
  </section>
</template>
