<script setup lang="ts">
import { useKitchenBoard } from '@/composables/useKitchenBoard'
import KitchenBoard from './KitchenBoard.vue'
import KitchenSidebar from './KitchenSidebar.vue'
import KitchenTopbar from './KitchenTopbar.vue'

const { visibleOrders, acceptOrder, completeOrder } = useKitchenBoard()
</script>

<template>
  <div class="kds-stage">
    <div class="kds-frame">
      <KitchenSidebar />
      <div class="kds-main">
        <KitchenTopbar />
        <KitchenBoard :orders="visibleOrders" @accept="acceptOrder" @complete="completeOrder" />
      </div>
    </div>
  </div>
</template>
