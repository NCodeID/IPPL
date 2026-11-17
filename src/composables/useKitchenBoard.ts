import { computed, ref } from 'vue'
import { kitchenOrders } from '@/data/kitchenOrders'
import type { KitchenOrder } from '@/types/kitchen'

export function useKitchenBoard() {
  const orders = ref<KitchenOrder[]>(kitchenOrders.map((order) => ({ ...order })))

  const visibleOrders = computed(() => orders.value.filter((order) => order.status !== 'ready'))

  function acceptOrder(id: number) {
    const order = orders.value.find((entry) => entry.id === id)
    if (order?.status === 'pending') {
      order.status = 'cooking'
    }
  }

  function completeOrder(id: number) {
    const order = orders.value.find((entry) => entry.id === id)
    if (order?.status === 'cooking') {
      order.status = 'ready'
    }
  }

  return { orders, visibleOrders, acceptOrder, completeOrder }
}
