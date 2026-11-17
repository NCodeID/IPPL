export type KitchenStatus = 'pending' | 'cooking' | 'ready'

export type ServiceType = 'dine_in' | 'takeaway'

export type FulfillmentRole = 'runner' | 'driver' | 'drive_thru'

export interface KitchenFulfillment {
  role: FulfillmentRole
  name: string
  context?: string
}

export interface KitchenOrderItem {
  id: number
  menuName: string
  quantity: number
  modifiers: string[]
}

export interface KitchenTag {
  id: number
  label: string
  tone: 'neutral' | 'alert'
}

export interface KitchenOrder {
  id: number
  orderNumber: string
  displayName: string
  serviceType: ServiceType
  status: KitchenStatus
  orderedAt: string
  orderedAtLabel: string
  fulfillment?: KitchenFulfillment
  stationLabel?: string
  areaLabel?: string
  channelLabel?: string
  items: KitchenOrderItem[]
  customerNote?: string
  kitchenNote?: string
  tags: KitchenTag[]
}

export interface KitchenSlot {
  column: number
  row: number
}
