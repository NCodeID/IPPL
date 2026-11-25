<script setup lang="ts">
import { ref, computed, watch, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Download,
  Printer,
  Award,
  Utensils,
  Wallet,
  Layers,
  Calendar,
  Search,
  X,
  RotateCcw,
  Eye,
  ChevronLeft,
  ChevronRight,
  Info,
  TrendingUp,
  CheckCircle2,
  BarChart3,
  Clock,
  ClipboardCheck,
  UtensilsCrossed,
  Coffee,
  Soup,
  ShoppingBag,
  CircleDollarSign,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe data untuk 4 kartu statistik utama produk terlaris ────────────────
interface BestSellingStatCard {
  label: string
  title: string
  value: string
  subtitle: string
  trendText?: string
  icon: Component
  iconBg: string
  iconColor: string
}

// ─── Tipe data untuk distribusi jam penjualan teramai (Peak Hours) ──────────
interface PeakHourItem {
  label: string
  dotColor: string
  qty: number
  unit: string
  percentage: number
}

// ─── Tipe data untuk informasi harga dan profitabilitas menu ────────────────
interface ProductProfitability {
  sellingPrice: number
  hppCost: number
  grossProfit: number
  marginPercent: number
  costPercent: number
}

// ─── Tipe data untuk setiap item menu pada daftar produk terlaris ───────────
interface BestSellingProduct {
  id: string
  name: string
  sku: string
  category: 'Makanan Utama' | 'Minuman'
  unit: 'Porsi' | 'Gelas'
  qtySold: number
  qtySharePercent: number
  unitPrice: number
  totalRevenue: number
  revenueContribution: number
  tierLabel: 'Top 1' | 'Top 2' | 'Top 3' | 'Laris'
  modalBadge: string
  dailyAverage: number
  growthText: string
  icon: Component
  iconBg: string
  iconColor: string
  profitability: ProductProfitability
  peakHours: PeakHourItem[]
  stockNote: string
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── 4 Kartu Statistik Utama ────────────────────────────────────────────────
// Berisi ringkasan produk teratas, total menu terjual, total pendapatan,
// dan kategori paling laris. `markRaw` digunakan agar ikon tidak dijadikan reaktif.
const statCards = ref<BestSellingStatCard[]>([
  {
    label: 'PRODUK TERATAS',
    title: 'Ayam Bakar Madu Spesial',
    value: '642 Porsi',
    trendText: '+8.4%',
    subtitle: '• Kontribusi 24.8% total volume',
    icon: markRaw(Award),
    iconBg: 'bg-orange-50',
    iconColor: 'text-[#C2410C]',
  },
  {
    label: 'TOTAL MENU TERJUAL',
    title: 'Volume Keseluruhan',
    value: '2.580 Porsi',
    subtitle: 'Rata-rata 86 porsi / hari',
    icon: markRaw(Utensils),
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-600',
  },
  {
    label: 'TOTAL PENDAPATAN MENU',
    title: 'Omzet Kotor Akumulatif',
    value: 'Rp 42.850.000',
    subtitle: 'Dari 12 kategori menu aktif',
    icon: markRaw(Wallet),
    iconBg: 'bg-orange-50',
    iconColor: 'text-[#C2410C]',
  },
  {
    label: 'KATEGORI PALING LARIS',
    title: 'Makanan Utama',
    value: '1.450 Porsi',
    subtitle: '56.2% dari seluruh pesanan',
    icon: markRaw(Layers),
    iconBg: 'bg-blue-50',
    iconColor: 'text-blue-600',
  },
])

// ─── Daftar pilihan periode cepat ───────────────────────────────────────────
const periodOptions = ref<string[]>(['Hari Ini', '7 Hari Terakhir', 'Bulan Ini', 'Kustom'])

// ─── Daftar produk terlaris lengkap dengan profitabilitas & peak hours ──────
// Data dummy 5 produk sesuai desain. Akan diganti dengan data API Laravel nantinya.
const products = ref<BestSellingProduct[]>([
  {
    id: '1',
    name: 'Ayam Bakar Madu Spesial',
    sku: 'SKU-ABM-01',
    category: 'Makanan Utama',
    unit: 'Porsi',
    qtySold: 642,
    qtySharePercent: 32,
    unitPrice: 38_000,
    totalRevenue: 24_396_000,
    revenueContribution: 56.9,
    tierLabel: 'Top 1',
    modalBadge: 'Top 1 Terlaris',
    dailyAverage: 25,
    growthText: '+8.4% vs bulan lalu',
    icon: markRaw(UtensilsCrossed),
    iconBg: 'bg-orange-50',
    iconColor: 'text-[#C2410C]',
    profitability: {
      sellingPrice: 38_000,
      hppCost: 19_500,
      grossProfit: 18_500,
      marginPercent: 48.7,
      costPercent: 51.3,
    },
    peakHours: [
      { label: 'Makan Siang (11:30 - 14:00)', dotColor: 'bg-amber-500', qty: 280, unit: 'Porsi', percentage: 43.6 },
      { label: 'Makan Malam (18:00 - 21:00)', dotColor: 'bg-[#C2410C]', qty: 312, unit: 'Porsi', percentage: 48.6 },
      { label: 'Di Luar Jam Sibuk', dotColor: 'bg-slate-400', qty: 50, unit: 'Porsi', percentage: 7.8 },
    ],
    stockNote:
      'Bahan baku ayam marinasi dan madu spesial memiliki perputaran stok (turnover) tinggi. Disarankan menjaga buffer stock minimal 30 kg per hari kerja untuk menghindari potensi out-of-stock saat shift makan malam.',
  },
  {
    id: '2',
    name: 'Nasi Goreng Kampung',
    sku: 'SKU-NGK-02',
    category: 'Makanan Utama',
    unit: 'Porsi',
    qtySold: 485,
    qtySharePercent: 24,
    unitPrice: 28_000,
    totalRevenue: 13_580_000,
    revenueContribution: 31.7,
    tierLabel: 'Top 2',
    modalBadge: 'Top 2 Terlaris',
    dailyAverage: 19,
    growthText: '+6.1% vs bulan lalu',
    icon: markRaw(Soup),
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-600',
    profitability: {
      sellingPrice: 28_000,
      hppCost: 13_200,
      grossProfit: 14_800,
      marginPercent: 52.9,
      costPercent: 47.1,
    },
    peakHours: [
      { label: 'Makan Siang (11:30 - 14:00)', dotColor: 'bg-amber-500', qty: 195, unit: 'Porsi', percentage: 40.2 },
      { label: 'Makan Malam (18:00 - 21:00)', dotColor: 'bg-[#C2410C]', qty: 230, unit: 'Porsi', percentage: 47.4 },
      { label: 'Di Luar Jam Sibuk', dotColor: 'bg-slate-400', qty: 60, unit: 'Porsi', percentage: 12.4 },
    ],
    stockNote:
      'Permintaan beras pandan wangi, telur ayam, dan bumbu terasi meningkat signifikan pada jam makan malam. Pastikan persediaan beras minimal 2 karung tersedia di gudang harian.',
  },
  {
    id: '3',
    name: 'Sate Ayam Madura',
    sku: 'SKU-SAM-03',
    category: 'Makanan Utama',
    unit: 'Porsi',
    qtySold: 320,
    qtySharePercent: 16,
    unitPrice: 35_000,
    totalRevenue: 11_200_000,
    revenueContribution: 26.1,
    tierLabel: 'Top 3',
    modalBadge: 'Top 3 Terlaris',
    dailyAverage: 13,
    growthText: '+4.5% vs bulan lalu',
    icon: markRaw(Utensils),
    iconBg: 'bg-orange-50',
    iconColor: 'text-amber-700',
    profitability: {
      sellingPrice: 35_000,
      hppCost: 17_800,
      grossProfit: 17_200,
      marginPercent: 49.1,
      costPercent: 50.9,
    },
    peakHours: [
      { label: 'Makan Siang (11:30 - 14:00)', dotColor: 'bg-amber-500', qty: 90, unit: 'Porsi', percentage: 28.1 },
      { label: 'Makan Malam (18:00 - 21:00)', dotColor: 'bg-[#C2410C]', qty: 195, unit: 'Porsi', percentage: 60.9 },
      { label: 'Di Luar Jam Sibuk', dotColor: 'bg-slate-400', qty: 35, unit: 'Porsi', percentage: 10.9 },
    ],
    stockNote:
      'Penjualan memuncak pada sesi makan malam (60.9%). Siapkan tusuk sate dan bumbu kacang olahan lebih awal sebelum pukul 17:00 WIB.',
  },
  {
    id: '4',
    name: 'Es Kopi Susu Aren',
    sku: 'SKU-KSA-04',
    category: 'Minuman',
    unit: 'Gelas',
    qtySold: 290,
    qtySharePercent: 14,
    unitPrice: 22_000,
    totalRevenue: 6_380_000,
    revenueContribution: 14.9,
    tierLabel: 'Laris',
    modalBadge: 'Menu Laris',
    dailyAverage: 12,
    growthText: '+11.2% vs bulan lalu',
    icon: markRaw(Coffee),
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-700',
    profitability: {
      sellingPrice: 22_000,
      hppCost: 8_800,
      grossProfit: 13_200,
      marginPercent: 60.0,
      costPercent: 40.0,
    },
    peakHours: [
      { label: 'Makan Siang (11:30 - 14:00)', dotColor: 'bg-amber-500', qty: 135, unit: 'Gelas', percentage: 46.6 },
      { label: 'Makan Malam (18:00 - 21:00)', dotColor: 'bg-[#C2410C]', qty: 90, unit: 'Gelas', percentage: 31.0 },
      { label: 'Di Luar Jam Sibuk', dotColor: 'bg-slate-400', qty: 65, unit: 'Gelas', percentage: 22.4 },
    ],
    stockNote:
      'Margin kotor tertinggi di kategori minuman (60.0%). Jaga stok fresh milk pasteurisasi dan gula aren cair agar tidak kehabisan pada jam makan siang.',
  },
  {
    id: '5',
    name: 'Jus Alpukat Kocok',
    sku: 'SKU-JAK-05',
    category: 'Minuman',
    unit: 'Gelas',
    qtySold: 185,
    qtySharePercent: 9,
    unitPrice: 20_000,
    totalRevenue: 3_700_000,
    revenueContribution: 8.6,
    tierLabel: 'Laris',
    modalBadge: 'Menu Laris',
    dailyAverage: 8,
    growthText: '+3.8% vs bulan lalu',
    icon: markRaw(ShoppingBag),
    iconBg: 'bg-blue-50',
    iconColor: 'text-blue-600',
    profitability: {
      sellingPrice: 20_000,
      hppCost: 9_600,
      grossProfit: 10_400,
      marginPercent: 52.0,
      costPercent: 48.0,
    },
    peakHours: [
      { label: 'Makan Siang (11:30 - 14:00)', dotColor: 'bg-amber-500', qty: 95, unit: 'Gelas', percentage: 51.4 },
      { label: 'Makan Malam (18:00 - 21:00)', dotColor: 'bg-[#C2410C]', qty: 65, unit: 'Gelas', percentage: 35.1 },
      { label: 'Di Luar Jam Sibuk', dotColor: 'bg-slate-400', qty: 25, unit: 'Gelas', percentage: 13.5 },
    ],
    stockNote:
      'Buah alpukat mentega memiliki masa simpan pendek. Lakukan pemesanan bertahap setiap 2 hari sekali sesuai proyeksi kebutuhan harian.',
  },
])

// ─── State filter periode, pencarian, kategori, dan pengurutan ──────────────
const selectedPeriod = ref<string>('Bulan Ini')
const customDateRange = ref<string>('01 Okt 2024 - 25 Okt 2024')
const searchQuery = ref<string>('')
const selectedCategory = ref<string>('Semua Kategori')
const selectedSort = ref<'qty' | 'revenue' | 'contribution'>('qty')

// ─── State pagination ───────────────────────────────────────────────────────
const currentPage = ref<number>(1)
const itemsPerPage = ref<number>(5)
const simulatedTotalMenus = ref<number>(42)

// ─── State modal Detail Analisis Produk ─────────────────────────────────────
const showAnalysisModal = ref<boolean>(false)
const analysisTarget = ref<BestSellingProduct | null>(null)

// ─── State umpan balik setelah ekspor laporan CSV ───────────────────────────
const actionFeedback = ref<string>('')

// ═══════════════════════════════════════════════════════════════════════════════
// COMPUTED (Kalkulasi Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar pilihan kategori unik untuk dropdown filter ─────────────────────
const categoryOptions = computed<string[]>(() => {
  const categories = products.value.map((p) => p.category)
  return ['Semua Kategori', ...new Set(categories)]
})

// ─── Label rentang tanggal aktif sesuai tab periode yang dipilih ────────────
const activeDateRangeLabel = computed<string>(() => {
  if (selectedPeriod.value === 'Hari Ini') return '25 Okt 2024'
  if (selectedPeriod.value === '7 Hari Terakhir') return '19 Okt 2024 - 25 Okt 2024'
  if (selectedPeriod.value === 'Kustom') return customDateRange.value
  return '01 Okt 2024 - 25 Okt 2024'
})

// ─── Daftar produk setelah difilter pencarian & kategori, lalu diurutkan ─────
const filteredAndSortedProducts = computed<BestSellingProduct[]>(() => {
  let result = [...products.value]

  // Filter berdasarkan kategori menu
  if (selectedCategory.value !== 'Semua Kategori') {
    result = result.filter((p) => p.category === selectedCategory.value)
  }

  // Filter berdasarkan kata kunci pencarian (nama, SKU, kategori)
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    result = result.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.sku.toLowerCase().includes(q) ||
        p.category.toLowerCase().includes(q),
    )
  }

  // Urutkan sesuai pilihan dropdown pengurutan
  result.sort((a, b) => {
    if (selectedSort.value === 'revenue') return b.totalRevenue - a.totalRevenue
    if (selectedSort.value === 'contribution') return b.revenueContribution - a.revenueContribution
    return b.qtySold - a.qtySold
  })

  return result
})

// ─── Mengecek apakah ada filter aktif yang sedang diterapkan ────────────────
const isFilterActive = computed<boolean>(
  () => searchQuery.value.trim() !== '' || selectedCategory.value !== 'Semua Kategori',
)

// ─── Total menu aktif yang ditampilkan pada footer tabel ─────────────────────
const displayedTotalMenus = computed<number>(() =>
  isFilterActive.value ? filteredAndSortedProducts.value.length : simulatedTotalMenus.value,
)

// ─── Data produk pada halaman tabel saat ini (pagination) ───────────────────
const paginatedProducts = computed<BestSellingProduct[]>(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredAndSortedProducts.value.slice(start, start + itemsPerPage.value)
})

// ─── Total halaman berdasarkan jumlah data hasil filter ─────────────────────
const totalPages = computed<number>(() =>
  Math.max(1, Math.ceil(filteredAndSortedProducts.value.length / itemsPerPage.value)),
)

// ─── Sliding window nomor halaman untuk pagination dinamis ──────────────────
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

// ─── Reset halaman ke 1 setiap kali filter, pencarian, atau urutan berubah ────
// Mencegah tabel terlihat kosong saat pengguna mengganti filter di halaman > 1.
watch([searchQuery, selectedPeriod, selectedCategory, selectedSort], () => {
  currentPage.value = 1
})

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI HELPER & AKSI
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Format angka menjadi format mata uang Rupiah Indonesia ─────────────────
function formatPrice(value: number): string {
  return `Rp ${value.toLocaleString('id-ID')}`
}

// ─── Mengembalikan kelas warna kotak nomor peringkat ────────────────────────
function rankBoxClass(rank: number): string {
  if (rank === 1) return 'bg-[#C2410C] text-white shadow-sm'
  if (rank === 2) return 'bg-slate-100 text-slate-700'
  if (rank === 3) return 'bg-orange-50 text-[#C2410C]'
  return 'text-gray-500'
}

// ─── Berpindah ke halaman tertentu pada pagination tabel ────────────────────
function goToPage(page: number | string): void {
  if (typeof page === 'string') return
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

// ─── Mereset seluruh filter periode, kategori, pengurutan, dan pencarian ────
function resetFilters(): void {
  searchQuery.value = ''
  selectedPeriod.value = 'Bulan Ini'
  selectedCategory.value = 'Semua Kategori'
  selectedSort.value = 'qty'
}

// ─── Membuka modal Detail Analisis Produk untuk baris yang dipilih ───────────
function openAnalysisModal(product: BestSellingProduct): void {
  analysisTarget.value = product
  showAnalysisModal.value = true
}

// ─── Menutup modal Detail Analisis Produk ───────────────────────────────────
function closeAnalysisModal(): void {
  showAnalysisModal.value = false
  analysisTarget.value = null
}

// ─── Mencetak ringkasan laporan atau analisis produk via print browser ───────
function printSummary(): void {
  window.print()
}

// ─── Mengambil data peringkat produk terlaris dari API backend Laravel ──────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint GET /api/accountant/best-selling dengan parameter filter periode,
// kategori, pengurutan, dan kata kunci pencarian.
async function fetchBestSellingProducts(): Promise<void> {
  // [API-READY] Contoh pemanggilan API:
  // const { data } = await api.get('/accountant/best-selling', {
  //   params: {
  //     period: selectedPeriod.value,
  //     category: selectedCategory.value,
  //     sort: selectedSort.value,
  //     search: searchQuery.value,
  //   },
  // })
  // statCards.value = data.stats
  // products.value = data.products
  // simulatedTotalMenus.value = data.total
}

// ─── Mengekspor data peringkat produk terlaris ke berkas CSV ────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini dapat memanggil
// endpoint GET /api/accountant/best-selling/export?format=csv atau langsung
// mengunduh data hasil filter sebagai berkas CSV.
function exportBestSellingReport(): void {
  // [API-READY] Contoh pemanggilan API ekspor:
  // window.location.href = `/api/accountant/best-selling/export?period=${selectedPeriod.value}`

  const header = ['Peringkat', 'Nama Menu', 'SKU', 'Kategori', 'Kuantitas Terjual', 'Satuan', 'Harga Satuan', 'Total Omzet', 'Kontribusi Omzet (%)']
  const rows = filteredAndSortedProducts.value.map((p, idx) => [
    idx + 1,
    p.name,
    p.sku,
    p.category,
    p.qtySold,
    p.unit,
    p.unitPrice,
    p.totalRevenue,
    `${p.revenueContribution}%`,
  ])

  const csvContent = [header, ...rows]
    .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `produk-terlaris-${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)

  actionFeedback.value = `Berhasil mengekspor ${filteredAndSortedProducts.value.length} data produk terlaris ke CSV.`
  setTimeout(() => {
    actionFeedback.value = ''
  }, 4000)
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memanggil fetch data produk terlaris saat komponen pertama kali dimuat ─
onMounted(() => {
  fetchBestSellingProducts()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Header Halaman ═══════════ -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="mb-1.5 flex flex-wrap items-center gap-2">
          <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-[#C2410C]">
            AKN-04
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
            Data Real-time Penjualan
          </span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Produk Terlaris</h1>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Peringkat dan performa penjualan menu makanan serta minuman berdasarkan volume pesanan dan
          kontribusi omzet kotor restoran.
        </p>
      </div>

      <!-- Tombol aksi kanan: Ekspor Laporan & Cetak Ringkasan -->
      <div class="flex flex-wrap items-center gap-2.5">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
          @click="exportBestSellingReport"
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

    <!-- ═══════════ 4 Kartu Statistik Utama ═══════════ -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="stat in statCards"
        :key="stat.label"
        class="flex flex-col justify-between rounded-xl border border-gray-100 bg-white p-5"
      >
        <div>
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                {{ stat.label }}
              </p>
              <p class="mt-1 truncate text-base font-bold text-gray-900" :title="stat.title">
                {{ stat.title }}
              </p>
            </div>
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              :class="stat.iconBg"
            >
              <component :is="stat.icon" class="h-5 w-5" :class="stat.iconColor" />
            </div>
          </div>

          <p class="mt-3 whitespace-nowrap text-xl font-bold text-gray-900 xl:text-2xl">
            {{ stat.value }}
          </p>
        </div>

        <div class="mt-2 flex items-center gap-1.5 text-xs">
          <TrendingUp v-if="stat.trendText" class="h-3.5 w-3.5 shrink-0 text-emerald-600" />
          <span v-if="stat.trendText" class="font-semibold text-emerald-600">
            {{ stat.trendText }}
          </span>
          <span class="truncate text-gray-500">{{ stat.subtitle }}</span>
        </div>
      </div>
    </div>

    <!-- ═══════════ Bar Filter Periode, Pencarian, & Pengurutan ═══════════ -->
    <div class="mb-6 space-y-3 rounded-xl border border-slate-200 bg-white p-4">
      <!-- Baris 1: Tab periode cepat di kiri + rentang tanggal di kanan -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
      </div>

      <!-- Baris 2: Pencarian + dropdown kategori + dropdown urutkan + Reset Filter -->
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <!-- Input pencarian -->
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama produk, SKU, atau kategori..."
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

        <!-- Dropdown Kategori -->
        <select
          v-model="selectedCategory"
          class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 transition focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30 lg:w-48"
        >
          <option v-for="cat in categoryOptions" :key="cat" :value="cat">{{ cat }}</option>
        </select>

        <!-- Dropdown Pengurutan -->
        <select
          v-model="selectedSort"
          class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 transition focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30 lg:w-64"
        >
          <option value="qty">Urutkan: Kuantitas Terbanyak</option>
          <option value="revenue">Urutkan: Total Omzet Tertinggi</option>
          <option value="contribution">Urutkan: Kontribusi Terbesar</option>
        </select>

        <!-- Tombol Reset Filter -->
        <button
          type="button"
          class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="resetFilters"
        >
          <RotateCcw class="h-3.5 w-3.5" />
          Reset Filter
        </button>
      </div>
    </div>

    <!-- ═══════════ Tabel Peringkat Produk ═══════════ -->
    <div class="rounded-xl border border-gray-100 bg-white">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="px-3.5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">
                Peringkat
              </th>
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Nama Menu &amp; Kategori
              </th>
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Kuantitas Terjual
              </th>
              <th class="px-3.5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Harga Satuan
              </th>
              <th class="px-3.5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Total Omzet
              </th>
              <th class="px-3.5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">
                Kontribusi Omzet
              </th>
              <th class="px-3.5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">
                Aksi
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(product, idx) in paginatedProducts"
              :key="product.id"
              :class="idx < paginatedProducts.length - 1 ? 'border-b border-gray-50' : ''"
            >
              <!-- Kolom PERINGKAT -->
              <td class="px-3.5 py-4 text-center">
                <span
                  :class="[
                    'inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold',
                    rankBoxClass((currentPage - 1) * itemsPerPage + idx + 1),
                  ]"
                >
                  {{ (currentPage - 1) * itemsPerPage + idx + 1 }}
                </span>
              </td>

              <!-- Kolom NAMA MENU & KATEGORI -->
              <td class="px-3.5 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                    :class="product.iconBg"
                  >
                    <component :is="product.icon" class="h-5 w-5" :class="product.iconColor" />
                  </div>
                  <div>
                    <p class="text-sm font-bold text-gray-900">{{ product.name }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">
                      {{ product.category }} • {{ product.sku }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Kolom KUANTITAS TERJUAL -->
              <td class="px-3.5 py-4">
                <div class="w-36">
                  <p class="text-sm font-bold text-gray-900">
                    {{ product.qtySold }} {{ product.unit }}
                  </p>
                  <div class="my-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div
                      class="h-full rounded-full"
                      :class="(currentPage - 1) * itemsPerPage + idx < 4 ? 'bg-[#C2410C]' : 'bg-slate-400'"
                      :style="{ width: `${Math.min(100, product.qtySharePercent * 2.5)}%` }"
                    ></div>
                  </div>
                  <p class="text-[11px] text-gray-400">
                    {{ product.qtySharePercent }}% dari total porsi
                  </p>
                </div>
              </td>

              <!-- Kolom HARGA SATUAN -->
              <td class="px-3.5 py-4 text-right text-sm text-gray-700 whitespace-nowrap">
                {{ formatPrice(product.unitPrice) }}
              </td>

              <!-- Kolom TOTAL OMZET -->
              <td class="px-3.5 py-4 text-right text-sm font-bold text-gray-900 whitespace-nowrap">
                {{ formatPrice(product.totalRevenue) }}
              </td>

              <!-- Kolom KONTRIBUSI OMZET -->
              <td class="px-3.5 py-4 text-center">
                <div class="inline-flex items-center gap-1.5">
                  <span class="text-sm font-bold text-gray-900">
                    {{ product.revenueContribution }}%
                  </span>
                  <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                    {{ product.tierLabel }}
                  </span>
                </div>
              </td>

              <!-- Kolom AKSI -->
              <td class="px-3.5 py-4 text-center">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                  @click="openAnalysisModal(product)"
                >
                  <Eye class="h-3.5 w-3.5 text-gray-500" />
                  Detail Analisis
                </button>
              </td>
            </tr>

            <!-- Baris kosong jika hasil filter tidak ditemukan -->
            <tr v-if="paginatedProducts.length === 0">
              <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                Tidak ada produk yang sesuai dengan filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer tabel: Info jumlah menu & sliding pagination -->
      <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3.5 sm:flex-row">
        <p class="text-xs text-gray-500">
          Menampilkan
          <span class="font-semibold text-gray-800">
            {{ filteredAndSortedProducts.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }} -
            {{ Math.min(currentPage * itemsPerPage, filteredAndSortedProducts.length) }}
          </span>
          dari
          <span class="font-semibold text-gray-800">{{ displayedTotalMenus }}</span>
          menu aktif
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

    <!-- ═══════════ Info Box: Ketentuan Perhitungan Produk Terlaris ═══════════ -->
    <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/50 p-5">
      <div class="flex items-start gap-3">
        <Info class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" />
        <div class="text-sm text-gray-700">
          <p class="font-bold text-gray-900">Ketentuan Perhitungan Produk Terlaris</p>
          <p class="mt-1 text-xs leading-relaxed text-gray-600">
            Data dihitung otomatis berdasarkan total item menu pada transaksi POS yang telah lunas
            dan terverifikasi. Tidak memperhitungkan pesanan yang dibatalkan (void). Laporan ini
            membantu evaluasi ketersediaan bahan baku gudang dan strategi penetapan harga promo
            restoran.
          </p>
        </div>
      </div>
    </div>

    <!-- ═══════════ MODAL: Detail Analisis Produk ═══════════ -->
    <div
      v-if="showAnalysisModal && analysisTarget"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeAnalysisModal"
    >
      <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <!-- Header Modal -->
        <div class="flex items-start justify-between border-b border-gray-100 p-5">
          <div class="flex items-start gap-3">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              :class="analysisTarget.iconBg"
            >
              <BarChart3 class="h-5 w-5 text-[#C2410C]" />
            </div>
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-lg font-bold text-gray-900">Detail Analisis Produk</h3>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                  <CheckCircle2 class="h-3 w-3" />
                  {{ analysisTarget.modalBadge }}
                </span>
              </div>
              <p class="mt-0.5 text-xs text-gray-500">
                {{ analysisTarget.name }} • {{ analysisTarget.sku }}
              </p>
            </div>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
            @click="closeAnalysisModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Body Modal (scrollable) -->
        <div class="flex-1 space-y-5 overflow-y-auto p-5">
          <!-- Grid 3 Kartu Metrik Atas -->
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-slate-50/50 p-4">
              <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                <span>Kuantitas Terjual</span>
                <ShoppingBag class="h-3.5 w-3.5 text-gray-400" />
              </div>
              <p class="mt-1.5 text-2xl font-bold text-gray-900">
                {{ analysisTarget.qtySold }} {{ analysisTarget.unit }}
              </p>
              <p class="mt-1 text-xs text-gray-400">
                {{ analysisTarget.qtySharePercent }}% dari total pesanan
                {{ analysisTarget.category === 'Makanan Utama' ? 'makanan' : 'minuman' }}
              </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-slate-50/50 p-4">
              <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                <span>Kontribusi Omzet</span>
                <Wallet class="h-3.5 w-3.5 text-gray-400" />
              </div>
              <p class="mt-1.5 whitespace-nowrap text-2xl font-bold text-gray-900">
                {{ formatPrice(analysisTarget.totalRevenue) }}
              </p>
              <p class="mt-1 text-xs text-gray-400">
                {{ analysisTarget.revenueContribution }}% total pendapatan resto
              </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-slate-50/50 p-4">
              <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                <span>Rata-rata Harian</span>
                <TrendingUp class="h-3.5 w-3.5 text-emerald-500" />
              </div>
              <p class="mt-1.5 text-2xl font-bold text-gray-900">
                {{ analysisTarget.dailyAverage }} {{ analysisTarget.unit }} / Hari
              </p>
              <p class="mt-1 text-xs font-semibold text-emerald-600">
                {{ analysisTarget.growthText }}
              </p>
            </div>
          </div>

          <!-- Bagian Informasi Harga & Profitabilitas (Gross Margin) -->
          <div class="rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <CircleDollarSign class="h-4 w-4 text-[#C2410C]" />
                <h4 class="text-sm font-bold text-gray-900">
                  Informasi Harga &amp; Profitabilitas (Gross Margin)
                </h4>
              </div>
              <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                {{ analysisTarget.category }}
              </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
              <div>
                <p class="text-xs text-gray-400">Harga Jual Satuan</p>
                <p class="mt-1 text-base font-bold text-gray-900">
                  {{ formatPrice(analysisTarget.profitability.sellingPrice) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-400">HPP Bahan Baku</p>
                <p class="mt-1 text-base font-semibold text-gray-700">
                  {{ formatPrice(analysisTarget.profitability.hppCost) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Gross Profit / Porsi</p>
                <p class="mt-1 text-base font-bold text-emerald-600">
                  {{ formatPrice(analysisTarget.profitability.grossProfit) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Margin Persentase</p>
                <p class="mt-1 text-base font-bold text-emerald-600">
                  {{ analysisTarget.profitability.marginPercent }}%
                </p>
              </div>
            </div>

            <!-- Bar Visual Biaya Bahan Baku vs Margin Kotor -->
            <div class="mt-4">
              <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                <div
                  class="bg-[#C2410C]"
                  :style="{ width: `${analysisTarget.profitability.costPercent}%` }"
                ></div>
                <div
                  class="bg-emerald-500"
                  :style="{ width: `${analysisTarget.profitability.marginPercent}%` }"
                ></div>
              </div>
              <div class="mt-2 flex items-center justify-between text-xs">
                <span class="text-gray-500">
                  Biaya Bahan Baku: {{ analysisTarget.profitability.costPercent }}%
                </span>
                <span class="font-semibold text-emerald-600">
                  Margin Kotor: {{ analysisTarget.profitability.marginPercent }}%
                </span>
              </div>
            </div>
          </div>

          <!-- Bagian Waktu Penjualan Teramai (Peak Hours) -->
          <div class="rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Clock class="h-4 w-4 text-[#C2410C]" />
                <h4 class="text-sm font-bold text-gray-900">
                  Waktu Penjualan Teramai (Peak Hours)
                </h4>
              </div>
              <span class="text-xs text-gray-400">Berdasarkan data jam order</span>
            </div>

            <div class="mt-4 space-y-3.5">
              <div v-for="peak in analysisTarget.peakHours" :key="peak.label">
                <div class="mb-1.5 flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2 font-medium text-gray-700">
                    <span class="h-2 w-2 rounded-full" :class="peak.dotColor"></span>
                    <span>{{ peak.label }}</span>
                  </div>
                  <span class="font-semibold text-gray-800">
                    {{ peak.qty }} {{ peak.unit }}
                    <span class="font-normal text-gray-400">({{ peak.percentage }}%)</span>
                  </span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                  <div
                    class="h-full rounded-full bg-[#C2410C]"
                    :style="{ width: `${peak.percentage}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Kotak Info Kuning/Krem: Catatan Akuntansi & Evaluasi Stok Gudang -->
          <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
            <div class="flex items-start gap-3">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                <ClipboardCheck class="h-4 w-4" />
              </div>
              <div>
                <p class="text-sm font-bold text-gray-900">
                  Catatan Akuntansi &amp; Evaluasi Stok Gudang
                </p>
                <p class="mt-1 text-xs leading-relaxed text-amber-900/80">
                  {{ analysisTarget.stockNote }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Modal -->
        <div class="flex items-center justify-between border-t border-gray-100 p-5">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="printSummary"
          >
            <Printer class="h-4 w-4" />
            Cetak Laporan Produk
          </button>
          <button
            type="button"
            class="rounded-lg bg-[#C2410C] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#B45309]"
            @click="closeAnalysisModal"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
