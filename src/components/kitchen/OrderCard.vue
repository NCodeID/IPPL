<script setup lang="ts">
import { computed } from 'vue'
import type { KitchenOrder, KitchenTag } from '@/types/kitchen'
import KitchenIcon from './KitchenIcon.vue'

const props = defineProps<{
  order: KitchenOrder
}>()

const emit = defineEmits<{
  accept: [id: number]
  complete: [id: number]
}>()

const isPending = computed(() => props.order.status === 'pending')

const serviceLabel = computed(() => (props.order.serviceType === 'dine_in' ? 'DINE IN' : 'TAKE AWAY'))

const whoText = computed(() => {
  const fulfillment = props.order.fulfillment
  if (!fulfillment) return ''
  if (fulfillment.role === 'driver') return `Driver: ${fulfillment.name}`
  if (fulfillment.role === 'drive_thru') return `Drive-Thru • Runner: ${fulfillment.name}`
  if (fulfillment.context) return `${fulfillment.context} • Runner: ${fulfillment.name}`
  return `Runner: ${fulfillment.name}`
})

const whoIcon = computed(() => {
  const role = props.order.fulfillment?.role
  if (role === 'driver' || role === 'drive_thru') return 'car' as const
  return 'person' as const
})

const hasMeta = computed(
  () =>
    Boolean(whoText.value) ||
    Boolean(props.order.stationLabel) ||
    Boolean(props.order.channelLabel) ||
    Boolean(props.order.areaLabel),
)

function tagIcon(tag: KitchenTag) {
  const label = tag.label.toLowerCase()
  if (label.includes('daun')) return 'leaf' as const
  if (label.includes('klip')) return 'clip' as const
  if (label.includes('bakar')) return 'flame' as const
  if (label.includes('tauge')) return 'sprout' as const
  if (label.includes('plastik') || label.includes('bungkus') || label.includes('sendok')) return 'bag' as const
  return 'bag' as const
}

function onAction() {
  if (isPending.value) {
    emit('accept', props.order.id)
    return
  }
  emit('complete', props.order.id)
}
</script>

<template>
  <article class="order-card">
    <header class="order-card__head">
      <h2 class="order-card__name">{{ order.displayName }}</h2>
      <div class="order-card__badges">
        <span class="order-pill" :class="order.serviceType === 'dine_in' ? 'order-pill--dine' : 'order-pill--away'">
          {{ serviceLabel }}
        </span>
        <span class="order-status" :class="isPending ? 'order-status--pending' : 'order-status--cooking'">
          <KitchenIcon v-if="isPending" name="pending" />
          <span v-else class="order-status__dot" />
          {{ isPending ? 'Pending' : 'Cooking' }}
        </span>
      </div>
    </header>

    <p class="order-card__when">
      <span class="order-card__ord">Ord - <strong>{{ order.orderNumber }}</strong></span>
      <span class="order-card__clock">
        <KitchenIcon name="clock" />
        {{ order.orderedAtLabel }}
      </span>
    </p>

    <p v-if="hasMeta" class="order-card__meta">
      <span v-if="whoText" class="order-card__who">
        <KitchenIcon :name="whoIcon" />
        {{ whoText }}
      </span>
      <span v-if="order.stationLabel" class="order-chip">Station: {{ order.stationLabel }}</span>
      <span v-if="order.channelLabel" class="order-channel">{{ order.channelLabel }}</span>
      <span v-if="order.areaLabel" class="order-area">{{ order.areaLabel }}</span>
    </p>

    <div class="order-card__body">
      <ul class="order-items">
        <li v-for="item in order.items" :key="item.id" class="order-item">
          <span class="order-item__qty">{{ item.quantity }}x</span>
          <span class="order-item__copy">
            <strong>{{ item.menuName }}</strong>
            <em v-if="item.modifiers.length">{{ item.modifiers.join(', ') }}</em>
          </span>
        </li>
      </ul>

      <aside v-if="order.customerNote || order.kitchenNote" class="order-aside">
        <div v-if="order.customerNote" class="order-note">
          <p class="order-note__label">
            <KitchenIcon name="note" />
            Catatan
          </p>
          <p class="order-note__text">"{{ order.customerNote }}"</p>
          <ul v-if="order.tags.length" class="order-tags">
            <li v-for="tag in order.tags" :key="tag.id">
              <KitchenIcon :name="tagIcon(tag)" />
              <span>{{ tag.label }}</span>
            </li>
          </ul>
        </div>
        <p v-if="order.kitchenNote" class="order-chef">
          <KitchenIcon name="chili" />
          <span><b>Catatan Koki:</b> {{ order.kitchenNote }}</span>
        </p>
      </aside>
    </div>

    <button
      class="order-action"
      :class="isPending ? 'order-action--accept' : 'order-action--done'"
      type="button"
      @click="onAction"
    >
      <KitchenIcon :name="isPending ? 'hand' : 'check'" />
      {{ isPending ? 'Terima' : 'Selesai' }}
    </button>
  </article>
</template>
