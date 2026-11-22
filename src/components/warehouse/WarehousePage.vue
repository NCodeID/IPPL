
<script setup lang="ts">
import { useWarehouse } from '@/composables/useWarehouse'

import WarehouseSidebar from './WarehouseSidebar.vue'
import WarehouseTopbar from './WarehouseTopbar.vue'
import WarehouseInventory from './WarehouseInventory.vue'
import WarehouseRequestPanel from './WarehouseRequestPanel.vue'
import WarehouseModals from './WarehouseModals.vue'

const {
  items,
  requests,
  searchQuery,
  filteredItems,
  showPurchaseModal,
  showCompletionModal,
  purchaseForm,
  completionForm,
  purchaseTotal,
  completionTotal,
  openPurchaseModal,
  closePurchaseModal,
  submitPurchaseRequest,
  cancelRequest,
  openCompletionModal,
  closeCompletionModal,
  submitCompletionRequest,
} = useWarehouse()

function updateSearchQuery(value: string) {
  searchQuery.value = value
}

function handleRequestStock(itemId: number) {
  openPurchaseModal(itemId)
}
</script>

<template>
  <div class="warehouse-stage">
    <div class="warehouse-frame">
      <WarehouseSidebar />

      <main class="warehouse-main">
        <WarehouseTopbar />

        <div class="warehouse-content">
          <label class="warehouse-search" aria-label="Cari bahan baku">
            <svg class="warehouse-search-icon" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="10.8" cy="10.8" r="6.8" />
              <path d="m16 16 4.5 4.5" />
            </svg>
            <input
              :value="searchQuery"
              type="search"
              placeholder="Cari Nama Bahan / Kode SKU (cth: Minyak Sawit, SKU-092)..."
              @input="updateSearchQuery(($event.target as HTMLInputElement).value)"
            />
            <button
              class="warehouse-search-clear"
              type="button"
              aria-label="Hapus pencarian"
              @click="updateSearchQuery('')"
            >
              ×
            </button>
          </label>

          <WarehouseInventory
            :items="filteredItems"
            :search-query="searchQuery"
            @update:search-query="updateSearchQuery"
            @request-stock="handleRequestStock"
          />

          <WarehouseRequestPanel
            :requests="requests"
            @create="openPurchaseModal"
            @cancel="cancelRequest"
            @complete="openCompletionModal"
          />
        </div>
      </main>
    </div>

    <WarehouseModals
      :show-purchase-modal="showPurchaseModal"
      :show-completion-modal="showCompletionModal"
      :items="items"
      :requests="requests"
      :purchase-form="purchaseForm"
      :completion-form="completionForm"
      :purchase-total="purchaseTotal"
      :completion-total="completionTotal"
      @close-purchase="closePurchaseModal"
      @close-completion="closeCompletionModal"
      @submit-purchase="submitPurchaseRequest"
      @submit-completion="submitCompletionRequest"
    />
  </div>
</template>
