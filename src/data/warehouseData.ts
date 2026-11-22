
import type { InventoryItem, WarehouseRequest } from '@/types/warehouse'

export const inventoryItems: InventoryItem[] = [
  {
    id: 1,
    name: 'Minyak Sawit PBW',
    description: 'Minyak goreng untuk kebutuhan dapur',
    stock: 4,
    unit: 'KG',
    minimumStock: 10,
  },
  {
    id: 2,
    name: 'Beras Rojo Lele',
    description: 'Beras kualitas premium',
    stock: 18,
    unit: 'KG',
    minimumStock: 20,
  },
  {
    id: 3,
    name: 'Daging Ayam Fillet',
    description: 'Daging ayam tanpa tulang',
    stock: 2,
    unit: 'KG',
    minimumStock: 10,
  },
  {
    id: 4,
    name: 'Cabai Rawit Merah',
    description: 'Cabai rawit segar',
    stock: 18,
    unit: 'KG',
    minimumStock: 5,
  },
]

export const warehouseRequests: WarehouseRequest[] = [
  {
    id: 1,
    itemName: 'Minyak Sawit PBW',
    quantity: 20,
    unit: 'Jerigen (100L)',
    totalPrice: 2800000,
    pic: 'Budi',
    role: 'Cook Head',
    time: '18:50 WIB',
    status: 'Pending',
  },
  {
    id: 2,
    itemName: 'Daging Ayam Fillet',
    quantity: 35,
    unit: 'KG',
    totalPrice: 1995000,
    pic: 'Dimas Arya',
    role: 'Supervisor On-Duty',
    time: '15:40 WIB',
    status: 'Approved',
  },
  {
    id: 3,
    itemName: 'Cabai Rawit Merah',
    quantity: 15,
    unit: 'KG',
    totalPrice: 750000,
    pic: 'Dimas Arya',
    role: 'Supervisor On-Duty',
    time: '15:40 WIB',
    status: 'Pending',
  },
  {
    id: 4,
    itemName: 'Beras Rojo Lele',
    quantity: 5,
    unit: 'Karung (125 KG)',
    totalPrice: 1625000,
    pic: 'Budi',
    role: 'Cook Head',
    time: '11:20 WIB',
    status: 'Rejected',
  },
]
