
<script setup lang="ts">
import { computed, ref } from 'vue'
import type { RequestStatus, WarehouseRequest } from '@/types/warehouse'

const props = defineProps<{
  requests: WarehouseRequest[]
}>()

type RequestFilter = 'Semua' | RequestStatus

const filterOptions: RequestFilter[] = ['Semua', 'Pending', 'Approved', 'Rejected', 'Done']
const activeFilter = ref<RequestFilter>('Semua')

const filteredRequests = computed(() =>
  activeFilter.value === 'Semua'
    ? props.requests
    : props.requests.filter((request) => request.status === activeFilter.value),
)

const emit = defineEmits<{
  cancel: [requestId: number]
  complete: [request: WarehouseRequest]
  create: []
}>()

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}
</script>

<template>
  <aside class="warehouse-request-panel">
    <div class="warehouse-request-heading">
      <div>
        <span class="warehouse-section-eyebrow">PROCUREMENT</span>
        <h2>Daftar Request Pembelian Gudang</h2>
      </div>

      <span class="warehouse-request-count">
        {{ requests.length }}
      </span>
    </div>

    <div class="warehouse-request-filters" aria-label="Filter status request">
      <button
        v-for="filter in filterOptions"
        :key="filter"
        type="button"
        :class="{ active: activeFilter === filter }"
        :aria-pressed="activeFilter === filter"
        @click="activeFilter = filter"
      >
        <span>{{ filter }}</span>
      </button>
    </div>

    <button
      class="warehouse-create-request"
      type="button"
      @click="emit('create')"
    >
      <span>+</span>
      Request Stock
    </button>

    <div v-if="filteredRequests.length > 0" class="warehouse-request-list">
      <article
        v-for="request in filteredRequests"
        :key="request.id"
        class="warehouse-request-card"
      >
        <div class="warehouse-request-card-top">
          <span class="warehouse-request-category">PURCHASE REQUEST</span>

          <span
            class="warehouse-request-status"
            :class="request.status.toLowerCase()"
          >
            {{ request.status }}
          </span>
        </div>

        <h3>{{ request.itemName }}</h3>

        <div class="warehouse-request-quantity">
          <span>Jumlah Pengajuan</span>
          <strong>{{ request.quantity }} {{ request.unit }}</strong>
        </div>

        <div class="warehouse-request-price">
          <span>Estimasi Total</span>
          <strong>{{ formatCurrency(request.totalPrice) }}</strong>
        </div>

        <div class="warehouse-request-divider"></div>

        <div class="warehouse-request-pic">
          <div class="warehouse-request-avatar">
            {{ request.pic.split(' ').map((word) => word[0]).join('').slice(0, 2) }}
          </div>

          <div class="warehouse-request-pic-info">
            <strong>{{ request.pic }}</strong>
            <span>{{ request.role }}</span>
          </div>

          <time>{{ request.time }}</time>
        </div>

        <div
          v-if="request.status === 'Pending' || request.status === 'Approved'"
          class="warehouse-request-actions"
        >
          <button
            v-if="request.status === 'Pending'"
            class="warehouse-request-cancel"
            type="button"
            @click="emit('cancel', request.id)"
          >
            Batal
          </button>

          <button
            v-if="request.status === 'Approved'"
            class="warehouse-request-complete"
            type="button"
            @click="emit('complete', request)"
          >
            Selesaikan
          </button>
        </div>
      </article>
    </div>

    <div v-else class="warehouse-empty-requests">
      <span>▤</span>
      <p>Tidak ada request</p>
    </div>
  </aside>
</template>
