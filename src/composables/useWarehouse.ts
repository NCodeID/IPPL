
import { computed, ref } from 'vue'
import type {
  CompletionRequestForm,
  InventoryItem,
  PurchaseRequestForm,
  WarehouseRequest,
} from '@/types/warehouse'
import { inventoryItems, warehouseRequests } from '@/data/warehouseData'

export function useWarehouse() {
  const items = ref<InventoryItem[]>([...inventoryItems])
  const requests = ref<WarehouseRequest[]>([...warehouseRequests])

  const searchQuery = ref('')

  const showPurchaseModal = ref(false)
  const showCompletionModal = ref(false)

  const purchaseForm = ref<PurchaseRequestForm>({
    itemId: null,
    quantity: 1,
    unitPrice: 0,
    notes: '',
  })

  const completionForm = ref<CompletionRequestForm>({
    requestId: null,
    quantity: 1,
    unitPrice: 0,
    notes: '',
  })

  const filteredItems = computed(() => {
    const keyword = searchQuery.value.toLowerCase().trim()

    if (!keyword) return items.value

    return items.value.filter((item) =>
      item.name.toLowerCase().includes(keyword),
    )
  })

  const pendingRequests = computed(() =>
    requests.value.filter(
      (request) => request.status !== 'Rejected' && request.status !== 'Done',
    ),
  )

  const purchaseTotal = computed(
    () => purchaseForm.value.quantity * purchaseForm.value.unitPrice,
  )

  const completionTotal = computed(
    () => completionForm.value.quantity * completionForm.value.unitPrice,
  )

  function openPurchaseModal(itemId: number | null = null) {
  purchaseForm.value = {
    itemId,
    quantity: 1,
    unitPrice: 0,
    notes: '',
  }

  showPurchaseModal.value = true
  }

  function closePurchaseModal() {
    showPurchaseModal.value = false
  }

  function submitPurchaseRequest() {
    const selectedItem = items.value.find(
      (item) => item.id === purchaseForm.value.itemId,
    )

    if (!selectedItem || purchaseForm.value.quantity <= 0) return

    const newRequest: WarehouseRequest = {
      id: Date.now(),
      itemName: selectedItem.name,
      quantity: purchaseForm.value.quantity,
      unit: selectedItem.unit,
      totalPrice: purchaseTotal.value,
      pic: 'Dimas Arya',
      role: 'Supervisor On-Duty',
      time: new Date().toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
      }) + ' WIB',
      status: 'Pending',
    }

    requests.value.unshift(newRequest)
    closePurchaseModal()
  }

  function cancelRequest(requestId: number) {
    requests.value = requests.value.filter(
      (request) => request.id !== requestId,
    )
  }

  function openCompletionModal(request: WarehouseRequest) {
    if (request.status !== 'Approved') return

    completionForm.value = {
      requestId: request.id,
      quantity: request.quantity,
      unitPrice: request.quantity > 0
        ? request.totalPrice / request.quantity
        : 0,
      notes: '',
    }

    showCompletionModal.value = true
  }

  function closeCompletionModal() {
    showCompletionModal.value = false
  }

  function submitCompletionRequest() {
    const form = completionForm.value

    const request = requests.value.find(
      (item) => item.id === form.requestId,
    )

    if (!request || request.status !== 'Approved' || form.quantity <= 0) return

    const inventoryItem = items.value.find(
      (item) => item.name === request.itemName,
    )

    if (inventoryItem) {
      inventoryItem.stock += form.quantity
    }

    request.status = 'Done'

    closeCompletionModal()
  }

  return {
    items,
    requests,
    searchQuery,
    filteredItems,
    pendingRequests,
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
  }
}
