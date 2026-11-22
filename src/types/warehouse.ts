
export type InventoryItem = {
  id: number
  name: string
  description: string
  stock: number
  unit: string
  minimumStock: number
}

export type RequestStatus = 'Pending' | 'Approved' | 'Rejected' | 'Done'

export type WarehouseRequest = {
  id: number
  itemName: string
  quantity: number
  unit: string
  totalPrice: number
  pic: string
  role: string
  time: string
  status: RequestStatus
}

export type PurchaseRequestForm = {
  itemId: number | null
  quantity: number
  unitPrice: number
  notes: string
}

export type CompletionRequestForm = {
  requestId: number | null
  quantity: number
  unitPrice: number
  notes: string
}
