<script setup lang="ts">
import { ref, computed, watch, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Download,
  Printer,
  Wallet,
  Receipt,
  Tag,
  Landmark,
  TrendingUp,
  CheckCircle2,
  Calendar,
  Search,
  X,
  RotateCcw,
  Eye,
  ChevronLeft,
  ChevronRight,
  Info,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe data untuk kartu statistik keuangan utama di bagian atas ──────────
interface FinanceStatCard {
  title: string
  value: string
  subtitle: string
  trendText?: string
  subtitleIcon?: Component
  icon: Component
  iconBg: string
  iconColor: string
}

// ─── Tipe data untuk kartu rincian metode pembayaran ────────────────────────
interface PaymentMethodCard {
  name: string
  filterKey: string
  trxCount: number
  amount: string
  percentage: number
  dotColor: string
  percentColor: string
  barColor: string
}

// ─── Tipe data untuk setiap item menu dalam struk transaksi ─────────────────
interface ReceiptItem {
  name: string
  qty: number
  price: number
  subtotal: number
}

// ─── Tipe data untuk setiap baris transaksi penjualan ───────────────────────
interface SalesTransaction {
  id: string
  invoiceNo: string
  dateTime: string
  periodKey: 'Hari Ini' | '7 Hari Terakhir' | 'Bulan Ini'
  orderTarget: string
  orderType: 'Dine In' | 'Bawa Pulang'
  cashierName: string
  terminal: 'Kasir 01' | 'Kasir 02'
  subtotal: number
  discount: number
  taxPB1: number
  totalPay: number
  paymentMethod: 'QRIS Dinamis' | 'Kartu Debit' | 'Tunai' | 'Transfer / E-Wallet'
  items: ReceiptItem[]
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar 4 kartu statistik keuangan utama ────────────────────────────────
// `markRaw` digunakan pada komponen ikon agar Vue tidak menjadikannya objek reaktif.
const financeStats = ref<FinanceStatCard[]>([
  {
    title: 'Total Penjualan Bersih',
    value: 'Rp 42.850.000',
    trendText: '+12.4%',
    subtitle: 'dibanding periode lalu',
    subtitleIcon: markRaw(TrendingUp),
    icon: markRaw(Wallet),
    iconBg: 'bg-orange-50',
    iconColor: 'text-[#C2410C]',
  },
  {
    title: 'Total Transaksi (Pesanan)',
    value: '384 Transaksi',
    subtitle: 'Rata-rata Rp 111.588 / nota',
    subtitleIcon: markRaw(TrendingUp),
    icon: markRaw(Receipt),
    iconBg: 'bg-blue-50',
    iconColor: 'text-blue-600',
  },
  {
    title: 'Total Diskon & Promo',
    value: 'Rp 2.150.000',
    subtitle: '48 transaksi gunakan promo',
    subtitleIcon: markRaw(Tag),
    icon: markRaw(Tag),
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    title: 'Total Pajak (PB1 10%)',
    value: 'Rp 4.070.000',
    subtitle: 'Kewajiban setor pajak resto',
    subtitleIcon: markRaw(CheckCircle2),
    icon: markRaw(Landmark),
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-600',
  },
])

// ─── Daftar 4 kartu rincian metode pembayaran ───────────────────────────────
const paymentMethods = ref<PaymentMethodCard[]>([
  {
    name: 'QRIS Dinamis',
    filterKey: 'QRIS Dinamis',
    trxCount: 165,
    amount: 'Rp 18.500.000',
    percentage: 43.2,
    dotColor: 'bg-[#C2410C]',
    percentColor: 'text-blue-600',
    barColor: 'bg-[#C2410C]',
  },
  {
    name: 'Kartu Debit/Kredit',
    filterKey: 'Kartu Debit',
    trxCount: 112,
    amount: 'Rp 13.200.000',
    percentage: 30.8,
    dotColor: 'bg-blue-600',
    percentColor: 'text-blue-600',
    barColor: 'bg-slate-800',
  },
  {
    name: 'Tunai (Cash)',
    filterKey: 'Tunai',
    trxCount: 92,
    amount: 'Rp 9.150.000',
    percentage: 21.3,
    dotColor: 'bg-emerald-500',
    percentColor: 'text-emerald-600',
    barColor: 'bg-[#C2410C]',
  },
  {
    name: 'Transfer / E-Wallet',
    filterKey: 'Transfer / E-Wallet',
    trxCount: 15,
    amount: 'Rp 2.000.000',
    percentage: 4.7,
    dotColor: 'bg-purple-500',
    percentColor: 'text-gray-500',
    barColor: 'bg-slate-500',
  },
])

// ─── Daftar pilihan periode cepat ───────────────────────────────────────────
const periodOptions = ref<string[]>(['Hari Ini', '7 Hari Terakhir', 'Bulan Ini', 'Kustom'])

// ─── Daftar transaksi penjualan (data dummy) ────────────────────────────────
// Berisi 5 transaksi contoh beserta rincian item menu pada struk masing-masing.
// Akan diganti dengan data dari endpoint API Backend Laravel nantinya.
const transactions = ref<SalesTransaction[]>([
  {
    id: '1',
    invoiceNo: 'INV-20241025-089',
    dateTime: '25 Okt 2024, 13:42 WIB',
    periodKey: 'Hari Ini',
    orderTarget: 'Meja 08',
    orderType: 'Dine In',
    cashierName: 'Siti Nurhaliza',
    terminal: 'Kasir 01',
    subtotal: 210_000,
    discount: 20_000,
    taxPB1: 19_000,
    totalPay: 209_000,
    paymentMethod: 'QRIS Dinamis',
    items: [
      { name: 'Ayam Bakar Madu Spesial', qty: 2, price: 38_000, subtotal: 76_000 },
      { name: 'Nasi Goreng Kampung', qty: 2, price: 28_000, subtotal: 56_000 },
      { name: 'Es Kopi Susu Aren', qty: 2, price: 22_000, subtotal: 44_000 },
      { name: 'Es Teh Tarik', qty: 2, price: 17_000, subtotal: 34_000 },
    ],
  },
  {
    id: '2',
    invoiceNo: 'INV-20241025-088',
    dateTime: '25 Okt 2024, 13:28 WIB',
    periodKey: 'Hari Ini',
    orderTarget: 'Take Away #14',
    orderType: 'Bawa Pulang',
    cashierName: 'Ahmad Fauzi',
    terminal: 'Kasir 02',
    subtotal: 95_000,
    discount: 0,
    taxPB1: 9_500,
    totalPay: 104_500,
    paymentMethod: 'Tunai',
    items: [
      { name: 'Soto Betawi Daging', qty: 1, price: 45_000, subtotal: 45_000 },
      { name: 'Sate Ayam Madura (10 Tusuk)', qty: 1, price: 32_000, subtotal: 32_000 },
      { name: 'Jus Alpukat Segar', qty: 1, price: 18_000, subtotal: 18_000 },
    ],
  },
  {
    id: '3',
    invoiceNo: 'INV-20241025-087',
    dateTime: '25 Okt 2024, 13:15 WIB',
    periodKey: 'Hari Ini',
    orderTarget: 'Meja 12',
    orderType: 'Dine In',
    cashierName: 'Siti Nurhaliza',
    terminal: 'Kasir 01',
    subtotal: 480_000,
    discount: 50_000,
    taxPB1: 43_000,
    totalPay: 473_000,
    paymentMethod: 'Kartu Debit',
    items: [
      { name: 'Iga Bakar Sambal Matah', qty: 4, price: 65_000, subtotal: 260_000 },
      { name: 'Gurame Asam Manis', qty: 2, price: 72_000, subtotal: 144_000 },
      { name: 'Es Kopi Susu Aren', qty: 2, price: 22_000, subtotal: 44_000 },
      { name: 'Air Mineral Botol 600ml', qty: 4, price: 8_000, subtotal: 32_000 },
    ],
  },
  {
    id: '4',
    invoiceNo: 'INV-20241025-086',
    dateTime: '25 Okt 2024, 12:55 WIB',
    periodKey: 'Hari Ini',
    orderTarget: 'Meja 03',
    orderType: 'Dine In',
    cashierName: 'Siti Nurhaliza',
    terminal: 'Kasir 01',
    subtotal: 150_000,
    discount: 0,
    taxPB1: 15_000,
    totalPay: 165_000,
    paymentMethod: 'QRIS Dinamis',
    items: [
      { name: 'Nasi Goreng Kampung', qty: 3, price: 28_000, subtotal: 84_000 },
      { name: 'Es Kopi Susu Aren', qty: 3, price: 22_000, subtotal: 66_000 },
    ],
  },
  {
    id: '5',
    invoiceNo: 'INV-20241025-085',
    dateTime: '25 Okt 2024, 12:40 WIB',
    periodKey: 'Hari Ini',
    orderTarget: 'Take Away #13',
    orderType: 'Bawa Pulang',
    cashierName: 'Ahmad Fauzi',
    terminal: 'Kasir 02',
    subtotal: 65_000,
    discount: 10_000,
    taxPB1: 5_500,
    totalPay: 60_500,
    paymentMethod: 'Transfer / E-Wallet',
    items: [
      { name: 'Mie Goreng Jawa Spesial', qty: 1, price: 32_000, subtotal: 32_000 },
      { name: 'Matcha Latte Dingin', qty: 1, price: 25_000, subtotal: 25_000 },
      { name: 'Kerupuk Udang Pack', qty: 1, price: 8_000, subtotal: 8_000 },
    ],
  },
])

// ─── State filter periode, pencarian, metode pembayaran, dan terminal kasir ─
const selectedPeriod = ref<string>('Bulan Ini')
const searchQuery = ref<string>('')
const selectedPaymentMethod = ref<string>('Semua Metode Pembayaran')
const selectedTerminal = ref<string>('Semua Terminal Kasir')
const customDateRange = ref<string>('01 Okt 2024 - 25 Okt 2024')

// ─── State pagination tabel transaksi ───────────────────────────────────────
const currentPage = ref<number>(1)
const itemsPerPage = ref<number>(5)
const simulatedTotalCount = ref<number>(384)

// ─── State modal Detail Struk Transaksi ─────────────────────────────────────
const showReceiptModal = ref<boolean>(false)
const selectedTransaction = ref<SalesTransaction | null>(null)

// ─── State pesan notifikasi aksi ekspor / cetak (opsional toast inline) ─────
const actionFeedback = ref<string>('')

// ═══════════════════════════════════════════════════════════════════════════════
// COMPUTED (Kalkulasi Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Label rentang tanggal aktif sesuai tab periode yang dipilih ────────────
const activeDateRangeLabel = computed<string>(() => {
  if (selectedPeriod.value === 'Hari Ini') return '25 Okt 2024'
  if (selectedPeriod.value === '7 Hari Terakhir') return '19 Okt 2024 - 25 Okt 2024'
  if (selectedPeriod.value === 'Kustom') return customDateRange.value
  return '01 Okt 2024 - 25 Okt 2024'
})

// ─── Daftar transaksi setelah difilter pencarian, metode, dan terminal ──────
const filteredTransactions = computed<SalesTransaction[]>(() => {
  let result = transactions.value

  // Filter berdasarkan metode pembayaran
  if (selectedPaymentMethod.value !== 'Semua Metode Pembayaran') {
    result = result.filter((trx) => trx.paymentMethod === selectedPaymentMethod.value)
  }

  // Filter berdasarkan terminal kasir
  if (selectedTerminal.value !== 'Semua Terminal Kasir') {
    result = result.filter((trx) => trx.terminal === selectedTerminal.value)
  }

  // Filter berdasarkan kata kunci pencarian (case-insensitive, trim spasi)
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    result = result.filter(
      (trx) =>
        trx.invoiceNo.toLowerCase().includes(q) ||
        trx.orderTarget.toLowerCase().includes(q) ||
        trx.cashierName.toLowerCase().includes(q) ||
        trx.terminal.toLowerCase().includes(q),
    )
  }

  return result
})

// ─── Mengecek apakah ada filter aktif yang diterapkan pengguna ──────────────
const isFilterActive = computed<boolean>(
  () =>
    searchQuery.value.trim() !== '' ||
    selectedPaymentMethod.value !== 'Semua Metode Pembayaran' ||
    selectedTerminal.value !== 'Semua Terminal Kasir',
)

// ─── Jumlah transaksi yang ditampilkan pada teks info filter & footer ───────
const displayedTotalCount = computed<number>(() =>
  isFilterActive.value ? filteredTransactions.value.length : simulatedTotalCount.value,
)

// ─── Data transaksi yang tampil pada halaman aktif (pagination) ──────────────
const paginatedTransactions = computed<SalesTransaction[]>(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredTransactions.value.slice(start, start + itemsPerPage.value)
})

// ─── Total halaman berdasarkan data hasil filter ─────────────────────────────
const totalPages = computed<number>(() =>
  Math.max(1, Math.ceil(filteredTransactions.value.length / itemsPerPage.value)),
)

// ─── Sliding window nomor halaman untuk kontrol pagination dinamis ───────────
const visiblePages = computed<(number | string)[]>(() => {
  const total = totalPages.value
  const current = currentPage.value
  const pages: (number | string)[] = []

  if (total <= 5) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    if (current < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

// ═══════════════════════════════════════════════════════════════════════════════
// WATCHER
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Reset halaman ke 1 setiap kali filter atau kata kunci pencarian berubah ──
// Mencegah tabel terlihat kosong karena terjebak di halaman melebihi hasil filter.
watch([searchQuery, selectedPeriod, selectedPaymentMethod, selectedTerminal], () => {
  currentPage.value = 1
})

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI HELPER & AKSI
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Format angka menjadi mata uang Rupiah Indonesia (cth: Rp 210.000) ──────
function formatPrice(value: number): string {
  return `Rp ${value.toLocaleString('id-ID')}`
}

// ─── Mengembalikan kelas warna badge sesuai metode pembayaran ───────────────
function paymentBadgeClass(method: SalesTransaction['paymentMethod']): string {
  const map: Record<SalesTransaction['paymentMethod'], string> = {
    'QRIS Dinamis': 'bg-orange-50 text-[#C2410C]',
    Tunai: 'bg-emerald-50 text-emerald-700',
    'Kartu Debit': 'bg-blue-50 text-blue-700',
    'Transfer / E-Wallet': 'bg-purple-50 text-purple-700',
  }
  return map[method]
}

// ─── Berpindah ke halaman tertentu pada pagination tabel ────────────────────
function goToPage(page: number | string): void {
  if (typeof page === 'string') return
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

// ─── Mereset seluruh filter periode, metode, terminal, dan input pencarian ───
function resetFilters(): void {
  searchQuery.value = ''
  selectedPeriod.value = 'Bulan Ini'
  selectedPaymentMethod.value = 'Semua Metode Pembayaran'
  selectedTerminal.value = 'Semua Terminal Kasir'
}

// ─── Membuka modal Detail Struk Transaksi untuk baris yang dipilih ──────────
function openReceiptModal(trx: SalesTransaction): void {
  selectedTransaction.value = trx
  showReceiptModal.value = true
}

// ─── Menutup modal Detail Struk Transaksi ───────────────────────────────────
function closeReceiptModal(): void {
  showReceiptModal.value = false
  selectedTransaction.value = null
}

// ─── Mencetak ringkasan laporan atau struk transaksi via dialog print browser ─
function printSummary(): void {
  window.print()
}

// ─── Mengambil data laporan penjualan dari API backend Laravel ──────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint GET /api/accountant/sales-report beserta parameter filter periode,
// metode pembayaran, terminal kasir, dan kata kunci pencarian.
async function fetchSalesReport(): Promise<void> {
  // [API-READY] Contoh pemanggilan API:
  // const { data } = await api.get('/accountant/sales-report', {
  //   params: {
  //     period: selectedPeriod.value,
  //     payment_method: selectedPaymentMethod.value,
  //     terminal: selectedTerminal.value,
  //     search: searchQuery.value,
  //   },
  // })
  // financeStats.value = data.stats
  // paymentMethods.value = data.paymentMethods
  // transactions.value = data.transactions
  // simulatedTotalCount.value = data.total
}

// ─── Mengekspor data laporan penjualan ke berkas CSV ─────────────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini dapat memanggil
// endpoint GET /api/accountant/sales-report/export?format=csv atau langsung
// mengunduh file CSV hasil filter dari data yang aktif saat ini.
function exportSalesReport(): void {
  // [API-READY] Contoh pemanggilan API ekspor:
  // window.location.href = `/api/accountant/sales-report/export?period=${selectedPeriod.value}`

  // Simulasi ekspor CSV langsung di sisi client dari transaksi terfilter
  const header = ['No. Struk', 'Waktu', 'Meja / Pesanan', 'Tipe', 'Kasir', 'Terminal', 'Subtotal', 'Diskon', 'PB1 (10%)', 'Total Bayar', 'Metode Pembayaran']
  const rows = filteredTransactions.value.map((trx) => [
    trx.invoiceNo,
    trx.dateTime,
    trx.orderTarget,
    trx.orderType,
    trx.cashierName,
    trx.terminal,
    trx.subtotal,
    trx.discount,
    trx.taxPB1,
    trx.totalPay,
    trx.paymentMethod,
  ])

  const csvContent = [header, ...rows]
    .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `laporan-penjualan-${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)

  actionFeedback.value = `Berhasil mengekspor ${filteredTransactions.value.length} data transaksi ke CSV.`
  setTimeout(() => {
    actionFeedback.value = ''
  }, 4000)
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memanggil fetch data laporan penjualan saat komponen dimuat ─────────────
onMounted(() => {
  fetchSalesReport()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Header Halaman ═══════════ -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="mb-1.5 flex flex-wrap items-center gap-2">
          <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-[#C2410C]">
            AKN-03
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
            Data Real-time Kasir
          </span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Laporan Penjualan</h1>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Rekapitulasi transaksi penjualan restoran, omzet kotor, pajak, diskon, dan pendapatan
          bersih dari seluruh terminal kasir POS.
        </p>
      </div>

      <!-- Tombol aksi kanan: Ekspor Laporan & Cetak Ringkasan -->
      <div class="flex flex-wrap items-center gap-2.5">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
          @click="exportSalesReport"
        >
          <Download class="h-4 w-4 text-gray-500" />
          Ekspor Laporan
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#B45309]"
          @click="printSummary"
        >
          <Printer class="h-4 w-4" />
          Cetak Ringkasan
        </button>
      </div>
    </div>

    <!-- Banner umpan balik setelah ekspor laporan -->
    <div
      v-if="actionFeedback"
      class="mb-4 flex items-center justify-between rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 class="h-4 w-4 shrink-0 text-green-600" />
        <span>{{ actionFeedback }}</span>
      </div>
      <button type="button" class="text-green-600 hover:text-green-800" @click="actionFeedback = ''">
        <X class="h-4 w-4" />
      </button>
    </div>

    <!-- ═══════════ 4 Kartu Statistik Keuangan Utama ═══════════ -->
    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="stat in financeStats"
        :key="stat.title"
        class="flex flex-col justify-between rounded-xl border border-gray-100 bg-white p-5"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="text-xs font-medium text-gray-500">{{ stat.title }}</p>
            <p class="mt-2 whitespace-nowrap text-xl font-bold text-gray-900 xl:text-2xl">
              {{ stat.value }}
            </p>
          </div>
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
            :class="stat.iconBg"
          >
            <component :is="stat.icon" class="h-5 w-5" :class="stat.iconColor" />
          </div>
        </div>

        <div class="mt-3 flex items-center gap-1.5 text-xs">
          <component
            :is="stat.subtitleIcon"
            v-if="stat.subtitleIcon"
            class="h-3.5 w-3.5 shrink-0"
            :class="stat.trendText ? 'text-emerald-600' : 'text-gray-400'"
          />
          <span v-if="stat.trendText" class="font-semibold text-emerald-600">
            {{ stat.trendText }}
          </span>
          <span class="text-gray-500">{{ stat.subtitle }}</span>
        </div>
      </div>
    </div>

    <!-- ═══════════ 4 Kartu Rincian Metode Pembayaran ═══════════ -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="method in paymentMethods"
        :key="method.name"
        class="rounded-xl border border-gray-100 bg-white p-4"
      >
        <!-- Baris atas: titik warna + nama metode + jumlah transaksi -->
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="h-2 w-2 shrink-0 rounded-full" :class="method.dotColor"></span>
            <span class="text-xs font-semibold text-gray-700">{{ method.name }}</span>
          </div>
          <span class="text-xs text-gray-400">{{ method.trxCount }} trx</span>
        </div>

        <!-- Baris tengah: nominal tebal + persentase -->
        <div class="mt-2 flex items-baseline justify-between gap-2">
          <p class="whitespace-nowrap text-base font-bold text-gray-900">{{ method.amount }}</p>
          <span class="text-xs font-semibold" :class="method.percentColor">
            {{ method.percentage }}%
          </span>
        </div>

        <!-- Baris bawah: progress bar proporsi pembayaran -->
        <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
          <div
            class="h-full rounded-full"
            :class="method.barColor"
            :style="{ width: `${method.percentage}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- ═══════════ Bar Filter Periode, Pencarian, & Terminal Kasir ═══════════ -->
    <div class="mb-6 space-y-3 rounded-xl border border-slate-200 bg-white p-4">
      <!-- Baris 1: Tab periode + rentang tanggal + input pencarian -->
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <!-- Tab pilihan periode cepat -->
        <div class="flex flex-wrap items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1">
          <button
            v-for="period in periodOptions"
            :key="period"
            type="button"
            :class="[
              'rounded-md px-3 py-1.5 text-xs font-medium transition',
              selectedPeriod === period
                ? 'bg-[#C2410C] text-white shadow-sm'
                : 'text-gray-600 hover:text-gray-900',
            ]"
            @click="selectedPeriod = period"
          >
            {{ period }}
          </button>
        </div>

        <!-- Kotak rentang tanggal -->
        <div
          class="flex h-10 shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-gray-700"
        >
          <Calendar class="h-4 w-4 text-slate-400" />
          <input
            v-if="selectedPeriod === 'Kustom'"
            v-model="customDateRange"
            type="text"
            aria-label="Rentang tanggal kustom"
            class="w-40 bg-transparent text-xs font-medium text-gray-700 focus:outline-none"
          />
          <span v-else class="whitespace-nowrap">{{ activeDateRangeLabel }}</span>
        </div>

        <!-- Input pencarian di kanan -->
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari No. Struk / Meja / Kasir..."
            class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-9 text-sm text-gray-800 transition focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-slate-400 hover:text-slate-600"
            title="Hapus pencarian"
            @click="searchQuery = ''"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- Baris 2: Dropdown metode pembayaran, dropdown terminal kasir, info, & tombol Reset Filter -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <!-- Dropdown Metode Pembayaran -->
          <select
            v-model="selectedPaymentMethod"
            class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 transition focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30 sm:w-52"
          >
            <option value="Semua Metode Pembayaran">Semua Metode Pembayaran</option>
            <option value="QRIS Dinamis">QRIS Dinamis</option>
            <option value="Kartu Debit">Kartu Debit</option>
            <option value="Tunai">Tunai</option>
            <option value="Transfer / E-Wallet">Transfer / E-Wallet</option>
          </select>

          <!-- Dropdown Terminal Kasir -->
          <select
            v-model="selectedTerminal"
            class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 transition focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30 sm:w-48"
          >
            <option value="Semua Terminal Kasir">Semua Terminal Kasir</option>
            <option value="Kasir 01">Kasir 01</option>
            <option value="Kasir 02">Kasir 02</option>
          </select>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
          <span class="text-xs italic text-gray-400">
            Menyaring {{ displayedTotalCount }} transaksi terverifikasi
          </span>
          <button
            type="button"
            class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-[#C2410C] transition hover:bg-orange-50"
            @click="resetFilters"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            Reset Filter
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ Tabel Daftar Transaksi Penjualan ═══════════ -->
    <div class="rounded-xl border border-gray-100 bg-white">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                No.
              </th>
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                No. Struk &amp; Waktu
              </th>
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Meja / Pesanan
              </th>
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Kasir
              </th>
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Rincian Pembayaran
              </th>
              <th class="px-3.5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Total Bayar
              </th>
              <th class="px-3.5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">
                Aksi
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(trx, idx) in paginatedTransactions"
              :key="trx.id"
              :class="idx < paginatedTransactions.length - 1 ? 'border-b border-gray-50' : ''"
            >
              <!-- Kolom NO. -->
              <td class="px-3.5 py-4 text-sm text-gray-500">
                {{ (currentPage - 1) * itemsPerPage + idx + 1 }}
              </td>

              <!-- Kolom NO. STRUK & WAKTU -->
              <td class="px-3.5 py-4">
                <p class="text-sm font-bold text-gray-900">{{ trx.invoiceNo }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ trx.dateTime }}</p>
              </td>

              <!-- Kolom MEJA / PESANAN -->
              <td class="px-3.5 py-4">
                <p class="text-sm font-semibold text-gray-800">{{ trx.orderTarget }}</p>
                <p
                  class="mt-0.5 flex items-center gap-1 text-xs font-medium"
                  :class="trx.orderType === 'Dine In' ? 'text-[#C2410C]' : 'text-blue-600'"
                >
                  <span>•</span>
                  <span>{{ trx.orderType }}</span>
                </p>
              </td>

              <!-- Kolom KASIR -->
              <td class="px-3.5 py-4">
                <p class="text-sm font-medium text-gray-800">{{ trx.cashierName }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ trx.terminal }}</p>
              </td>

              <!-- Kolom RINCIAN PEMBAYARAN -->
              <td class="px-3.5 py-4">
                <div class="w-44 space-y-0.5 text-xs">
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-400">Subtotal:</span>
                    <span class="font-medium text-gray-700">{{ formatPrice(trx.subtotal) }}</span>
                  </div>
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-400">Diskon:</span>
                    <span
                      :class="trx.discount > 0 ? 'font-medium text-red-500' : 'text-gray-400'"
                    >
                      {{ trx.discount > 0 ? `-${formatPrice(trx.discount)}` : '-' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-400">PB1 (10%):</span>
                    <span class="font-medium text-gray-700">{{ formatPrice(trx.taxPB1) }}</span>
                  </div>
                </div>
              </td>

              <!-- Kolom TOTAL BAYAR -->
              <td class="px-3.5 py-4 text-right">
                <p class="whitespace-nowrap text-base font-bold text-gray-900">
                  {{ formatPrice(trx.totalPay) }}
                </p>
                <span
                  :class="[
                    'mt-1 inline-block rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                    paymentBadgeClass(trx.paymentMethod),
                  ]"
                >
                  {{ trx.paymentMethod }}
                </span>
              </td>

              <!-- Kolom AKSI -->
              <td class="px-3.5 py-4 text-center">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                  @click="openReceiptModal(trx)"
                >
                  <Eye class="h-3.5 w-3.5 text-gray-500" />
                  Detail
                </button>
              </td>
            </tr>

            <!-- Baris kosong jika hasil filter tidak ditemukan -->
            <tr v-if="paginatedTransactions.length === 0">
              <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                Tidak ada transaksi penjualan yang sesuai dengan filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer tabel: Info jumlah data & sliding pagination -->
      <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3.5 sm:flex-row">
        <p class="text-xs text-gray-500">
          Menampilkan
          <span class="font-semibold text-gray-800">
            {{ filteredTransactions.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }} -
            {{ Math.min(currentPage * itemsPerPage, filteredTransactions.length) }}
          </span>
          dari
          <span class="font-semibold text-gray-800">{{ displayedTotalCount }}</span>
          transaksi
        </p>

        <!-- Sliding Pagination -->
        <div class="flex items-center gap-1.5">
          <!-- Tombol Sebelumnya -->
          <button
            type="button"
            :disabled="currentPage <= 1"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage - 1)"
          >
            <ChevronLeft class="h-3.5 w-3.5" /> Sebelumnya
          </button>

          <!-- Nomor Halaman -->
          <template v-for="(page, idx) in visiblePages" :key="'p-' + idx">
            <span v-if="page === '...'" class="px-1.5 text-xs text-gray-400">…</span>
            <button
              v-else
              type="button"
              :class="[
                'inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-semibold transition',
                page === currentPage
                  ? 'bg-[#C2410C] text-white shadow-sm'
                  : 'text-gray-600 hover:bg-gray-100',
              ]"
              @click="goToPage(page as number)"
            >
              {{ page }}
            </button>
          </template>

          <!-- Tombol Berikutnya -->
          <button
            type="button"
            :disabled="currentPage >= totalPages"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage + 1)"
          >
            Berikutnya <ChevronRight class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ Info Box: Ketentuan Sinkronisasi Data Penjualan ═══════════ -->
    <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/50 p-5">
      <div class="flex items-start gap-3">
        <Info class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" />
        <div class="text-sm text-gray-700">
          <p class="font-bold text-gray-900">Ketentuan Sinkronisasi Data Penjualan</p>
          <p class="mt-1 text-xs leading-relaxed text-gray-600">
            Data penjualan disinkronkan otomatis dari seluruh terminal kasir POS Restaurant saat
            shift ditutup atau pembayaran terverifikasi. Tidak termasuk transaksi yang dibatalkan
            (void). Laporan ini telah mencakup perhitungan Pajak Restoran (PB1 10%) sesuai standar
            pembukuan keuangan daerah.
          </p>
        </div>
      </div>
    </div>

    <!-- ═══════════ MODAL: Detail Struk Transaksi ═══════════ -->
    <div
      v-if="showReceiptModal && selectedTransaction"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeReceiptModal"
    >
      <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <!-- Header Modal -->
        <div class="flex items-start justify-between border-b border-gray-100 p-5">
          <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
              <Receipt class="h-5 w-5 text-[#C2410C]" />
            </div>
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-lg font-bold text-gray-900">Detail Struk Transaksi</h3>
                <span
                  :class="[
                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    paymentBadgeClass(selectedTransaction.paymentMethod),
                  ]"
                >
                  {{ selectedTransaction.paymentMethod }}
                </span>
              </div>
              <p class="mt-0.5 text-xs text-gray-500">
                {{ selectedTransaction.invoiceNo }} • {{ selectedTransaction.dateTime }}
              </p>
            </div>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
            @click="closeReceiptModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Body Modal (scrollable) -->
        <div class="flex-1 overflow-y-auto p-5">
          <!-- Ringkasan Info Struk 2 kolom -->
          <div class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm">
            <div>
              <p class="text-[11px] font-medium uppercase text-gray-400">Nomor Invoice</p>
              <p class="mt-0.5 font-bold text-gray-900">{{ selectedTransaction.invoiceNo }}</p>
            </div>
            <div>
              <p class="text-[11px] font-medium uppercase text-gray-400">Waktu Transaksi</p>
              <p class="mt-0.5 font-medium text-gray-800">{{ selectedTransaction.dateTime }}</p>
            </div>
            <div>
              <p class="text-[11px] font-medium uppercase text-gray-400">Meja / Tipe Pesanan</p>
              <p class="mt-0.5 font-semibold text-gray-800">
                {{ selectedTransaction.orderTarget }}
                <span
                  class="ml-1 text-xs font-medium"
                  :class="
                    selectedTransaction.orderType === 'Dine In' ? 'text-[#C2410C]' : 'text-blue-600'
                  "
                >
                  ({{ selectedTransaction.orderType }})
                </span>
              </p>
            </div>
            <div>
              <p class="text-[11px] font-medium uppercase text-gray-400">Kasir &amp; Terminal</p>
              <p class="mt-0.5 font-semibold text-gray-800">
                {{ selectedTransaction.cashierName }}
              </p>
              <p class="text-xs text-gray-400">{{ selectedTransaction.terminal }}</p>
            </div>
          </div>

          <!-- Daftar Item Menu pada Struk -->
          <div class="mt-5">
            <h4 class="text-sm font-bold text-gray-800">Daftar Item Menu Dipesan</h4>
            <div class="mt-2.5 overflow-hidden rounded-xl border border-gray-200">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="px-3.5 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">
                      Menu
                    </th>
                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase text-gray-500">
                      Qty
                    </th>
                    <th class="px-3 py-2.5 text-right text-xs font-semibold uppercase text-gray-500">
                      Harga
                    </th>
                    <th class="px-3.5 py-2.5 text-right text-xs font-semibold uppercase text-gray-500">
                      Subtotal
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, idx) in selectedTransaction.items"
                    :key="idx"
                    :class="idx < selectedTransaction.items.length - 1 ? 'border-b border-gray-100' : ''"
                  >
                    <td class="px-3.5 py-2.5 font-medium text-gray-800">{{ item.name }}</td>
                    <td class="px-3 py-2.5 text-center text-gray-600">{{ item.qty }}x</td>
                    <td class="px-3 py-2.5 text-right text-gray-600">
                      {{ formatPrice(item.price) }}
                    </td>
                    <td class="px-3.5 py-2.5 text-right font-semibold text-gray-800">
                      {{ formatPrice(item.subtotal) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Rincian Subtotal, Diskon, PB1, dan Total Bayar -->
          <div class="mt-4 space-y-2 rounded-xl border border-gray-100 bg-slate-50/70 p-4 text-sm">
            <div class="flex items-center justify-between text-gray-600">
              <span>Subtotal Pesanan</span>
              <span class="font-medium text-gray-800">
                {{ formatPrice(selectedTransaction.subtotal) }}
              </span>
            </div>
            <div class="flex items-center justify-between text-gray-600">
              <span>Diskon &amp; Promo</span>
              <span
                :class="
                  selectedTransaction.discount > 0 ? 'font-medium text-red-500' : 'text-gray-400'
                "
              >
                {{
                  selectedTransaction.discount > 0
                    ? `-${formatPrice(selectedTransaction.discount)}`
                    : 'Rp 0'
                }}
              </span>
            </div>
            <div class="flex items-center justify-between text-gray-600">
              <span>Pajak Restoran (PB1 10%)</span>
              <span class="font-medium text-gray-800">
                {{ formatPrice(selectedTransaction.taxPB1) }}
              </span>
            </div>
            <div class="border-t border-gray-200 pt-2.5">
              <div class="flex items-center justify-between">
                <div>
                  <span class="font-bold text-gray-900">Total Bayar</span>
                  <p class="text-xs text-gray-400">
                    Metode: {{ selectedTransaction.paymentMethod }}
                  </p>
                </div>
                <span class="text-xl font-bold text-[#C2410C]">
                  {{ formatPrice(selectedTransaction.totalPay) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Modal -->
        <div class="flex items-center justify-between border-t border-gray-100 p-5">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="closeReceiptModal"
          >
            Tutup
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#B45309]"
            @click="printSummary"
          >
            <Printer class="h-4 w-4" />
            Cetak Struk
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
