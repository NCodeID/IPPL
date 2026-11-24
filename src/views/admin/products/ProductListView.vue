<script setup lang="ts">
/**
 * ProductListView.vue
 * Halaman manajemen produk — daftar, tambah, edit, hapus produk restoran.
 * Semua komentar dalam Bahasa Indonesia.
 */
import { ref, computed, watch, onMounted, markRaw, type Component } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Plus,
  Search,
  X,
  RotateCcw,
  Eye,
  Pencil,
  Trash2,
  Info,
  UtensilsCrossed,
  Coffee,
  IceCreamCone,
  CheckCircle,
  ShoppingCart,
  LayoutGrid,
  SlidersHorizontal,
  SquarePen,
  Check,
  Save,
  ImagePlus,
  Camera,
  AlertTriangle,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

// ============================================================
// Tipe data
// ============================================================

/** Status ketersediaan produk */
type ProductStatus = 'Tersedia' | 'Habis'

/** Representasi satu item produk */
interface ProductItem {
  id: number
  sku: string
  name: string
  category: string
  price: number
  status: ProductStatus
  imageUrl: string | null // null → gunakan fallbackIcon
  description: string
  fallbackIcon: Component // ikon pengganti gambar, dibungkus markRaw
}

/** Form data untuk tambah / edit produk */
interface ProductForm {
  name: string
  category: string
  price: number
  status: ProductStatus
  imageUrl: string | null
  imageFile: File | null
  imagePreview: string | null
  description: string
}

// ============================================================
// Router
// ============================================================
const router = useRouter()

// ============================================================
// Data dummy produk (12 item unik)
// ============================================================
const products = ref<ProductItem[]>([
  {
    id: 1,
    sku: 'PRD-001',
    name: 'Ayam Bakar Madu Spesial',
    category: 'Makanan Utama',
    price: 38000,
    status: 'Tersedia',
    imageUrl: '/images/ayam-bakar.jpg',
    description: 'Pilihan olahan ayam bakar bumbu madu khas nusantara',
    fallbackIcon: markRaw(UtensilsCrossed),
  },
  {
    id: 2,
    sku: 'PRD-002',
    name: 'Nasi Goreng Kampung',
    category: 'Makanan Utama',
    price: 28000,
    status: 'Tersedia',
    imageUrl: '/images/nasi-goreng.jpg',
    description: 'Nasi goreng dengan bumbu kampung autentik',
    fallbackIcon: markRaw(UtensilsCrossed),
  },
  {
    id: 3,
    sku: 'PRD-003',
    name: 'Es Kopi Susu Aren',
    category: 'Minuman',
    price: 22000,
    status: 'Tersedia',
    imageUrl: '/images/kopi-aren.jpg',
    description: 'Kopi robusta pilihan dengan gula aren asli',
    fallbackIcon: markRaw(Coffee),
  },
  {
    id: 4,
    sku: 'PRD-004',
    name: 'Sate Ayam Madura',
    category: 'Makanan Utama',
    price: 35000,
    status: 'Tersedia',
    imageUrl: null,
    description: '10 tusuk sate ayam bumbu kacang kental',
    fallbackIcon: markRaw(UtensilsCrossed),
  },
  {
    id: 5,
    sku: 'PRD-005',
    name: 'Jus Alpukat Kocok',
    category: 'Minuman',
    price: 20000,
    status: 'Habis',
    imageUrl: null,
    description: 'Jus alpukat segar dengan susu kental',
    fallbackIcon: markRaw(Coffee),
  },
  {
    id: 6,
    sku: 'PRD-006',
    name: 'Pisang Goreng Keju',
    category: 'Dessert',
    price: 18000,
    status: 'Tersedia',
    imageUrl: null,
    description: 'Pisang goreng crispy tabur keju',
    fallbackIcon: markRaw(IceCreamCone),
  },
  {
    id: 7,
    sku: 'PRD-007',
    name: 'Rendang Daging Sapi',
    category: 'Makanan Utama',
    price: 45000,
    status: 'Tersedia',
    imageUrl: null,
    description: 'Rendang otentik Minang',
    fallbackIcon: markRaw(UtensilsCrossed),
  },
  {
    id: 8,
    sku: 'PRD-008',
    name: 'Es Teh Manis',
    category: 'Minuman',
    price: 8000,
    status: 'Tersedia',
    imageUrl: null,
    description: 'Teh manis dingin segar',
    fallbackIcon: markRaw(Coffee),
  },
  {
    id: 9,
    sku: 'PRD-009',
    name: 'Mie Goreng Spesial',
    category: 'Makanan Utama',
    price: 25000,
    status: 'Tersedia',
    imageUrl: null,
    description: 'Mie goreng dengan telur dan sayuran',
    fallbackIcon: markRaw(UtensilsCrossed),
  },
  {
    id: 10,
    sku: 'PRD-010',
    name: 'Tahu Crispy Sambal Matah',
    category: 'Makanan Ringan',
    price: 15000,
    status: 'Habis',
    imageUrl: null,
    description: 'Tahu crispy dengan sambal matah Bali',
    fallbackIcon: markRaw(UtensilsCrossed),
  },
  {
    id: 11,
    sku: 'PRD-011',
    name: 'Es Jeruk Peras',
    category: 'Minuman',
    price: 12000,
    status: 'Tersedia',
    imageUrl: null,
    description: 'Jeruk peras segar tanpa gula buatan',
    fallbackIcon: markRaw(Coffee),
  },
  {
    id: 12,
    sku: 'PRD-012',
    name: 'Puding Coklat',
    category: 'Dessert',
    price: 15000,
    status: 'Tersedia',
    imageUrl: null,
    description: 'Puding coklat lembut dengan vla vanilla',
    fallbackIcon: markRaw(IceCreamCone),
  },
])

// ============================================================
// Daftar kategori (untuk dropdown)
// ============================================================
const categoryList = ref<string[]>(['Makanan Utama', 'Minuman', 'Dessert', 'Makanan Ringan'])

// ============================================================
// Stat cards — angka hardcoded, akan diganti data API [API-READY]
// ============================================================
const statTotalProducts = ref(84)
const statAvailableProducts = ref(78)
const statOutOfStock = ref(6)
const statTotalCategories = ref(12)

// ============================================================
// Filter & pencarian
// ============================================================
const searchQuery = ref('')
const categoryFilter = ref('')
const statusFilter = ref<'' | ProductStatus>('')

// ============================================================
// Paginasi
// ============================================================
const currentPage = ref(1)
const itemsPerPage = ref(6)

// ============================================================
// Modal states
// ============================================================
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedProduct = ref<ProductItem | null>(null)

/** Form data kosong default */
const emptyForm = (): ProductForm => ({
  name: '',
  category: '',
  price: 0,
  status: 'Tersedia',
  imageUrl: null,
  imageFile: null,
  imagePreview: null,
  description: '',
})
const formData = ref<ProductForm>(emptyForm())

// ============================================================
// Computed — filter, paginasi, sliding window
// ============================================================

/** Daftar produk terfilter berdasarkan pencarian, kategori, status */
const filteredProducts = computed<ProductItem[]>(() => {
  let result = products.value

  // Filter pencarian (nama, sku, kategori)
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    result = result.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.sku.toLowerCase().includes(q) ||
        p.category.toLowerCase().includes(q),
    )
  }

  // Filter kategori
  if (categoryFilter.value) {
    result = result.filter((p) => p.category === categoryFilter.value)
  }

  // Filter status
  if (statusFilter.value) {
    result = result.filter((p) => p.status === statusFilter.value)
  }

  return result
})

/** Total halaman berdasarkan data terfilter */
const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredProducts.value.length / itemsPerPage.value)),
)

/** Indeks awal (0-based) untuk slice data */
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)

/** Indeks akhir (eksklusif) untuk slice data */
const endIndex = computed(() =>
  Math.min(startIndex.value + itemsPerPage.value, filteredProducts.value.length),
)

/** Data produk untuk halaman saat ini */
const paginatedProducts = computed(() =>
  filteredProducts.value.slice(startIndex.value, endIndex.value),
)

/** Sliding window nomor halaman yang terlihat (sliding window standar) */
const visiblePages = computed<(number | string)[]>(() => {
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 5) {
    const pages: number[] = []
    for (let i = 1; i <= total; i++) pages.push(i)
    return pages
  }

  if (current <= 3) return [1, 2, 3, '...', total]
  if (current >= total - 2) return [1, '...', total - 2, total - 1, total]
  return [1, '...', current - 1, current, current + 1, '...', total]
})

/** Berpindah ke nomor halaman tertentu */
function goToPage(page: number | string): void {
  const pageNum = typeof page === 'string' ? parseInt(page, 10) : page
  if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages.value) {
    currentPage.value = pageNum
  }
}

// ============================================================
// Watchers — Reset halaman saat pencarian atau filter berubah
// ============================================================

/**
 * Mengembalikan nomor halaman pagination ke halaman 1 setiap kali query pencarian,
 * kategori, atau status produk berubah, mencegah tampilan tabel kosong jika terjebak di halaman sebelumnya.
 */
watch([searchQuery, categoryFilter, statusFilter], () => {
  currentPage.value = 1
})

// ============================================================
// Fungsi-fungsi utama
// ============================================================

/**
 * Mengambil data produk dari API.
 * [API-READY] — saat ini menggunakan data dummy lokal.
 */
function fetchProducts(): void {
  // [API-READY] TODO: Ganti dengan pemanggilan API sesungguhnya
  // const response = await api.get('/products')
  // products.value = response.data
  // statTotalProducts.value = response.meta.total
  // statAvailableProducts.value = response.meta.available
  // statOutOfStock.value = response.meta.outOfStock
  // statTotalCategories.value = response.meta.categories
  console.log('[fetchProducts] Memuat data produk dari sumber lokal.')
}

/**
 * Membuat produk baru dan menambahkannya ke daftar.
 * [API-READY] — saat ini menyimpan ke array lokal.
 */
function createProduct(): void {
  // [API-READY] TODO: Ganti dengan pemanggilan API sesungguhnya
  // await api.post('/products', formData.value)

  // Buat SKU otomatis berdasarkan jumlah produk
  const nextId =
    products.value.length > 0
      ? Math.max(...products.value.map((p) => p.id)) + 1
      : 1
  const skuNumber = String(nextId).padStart(3, '0')

  // Tentukan ikon fallback berdasarkan kategori
  let fallbackIcon: Component = markRaw(UtensilsCrossed)
  if (formData.value.category === 'Minuman') {
    fallbackIcon = markRaw(Coffee)
  } else if (formData.value.category === 'Dessert') {
    fallbackIcon = markRaw(IceCreamCone)
  }

  const newProduct: ProductItem = {
    id: nextId,
    sku: `PRD-${skuNumber}`,
    name: formData.value.name,
    category: formData.value.category,
    price: formData.value.price,
    status: formData.value.status,
    imageUrl: formData.value.imagePreview,
    description: formData.value.description,
    fallbackIcon,
  }

  products.value.push(newProduct)
  closeAllModals()
}

/**
 * Memperbarui produk yang sudah ada.
 * [API-READY] — saat ini memperbarui array lokal.
 */
function updateProduct(): void {
  // [API-READY] TODO: Ganti dengan pemanggilan API sesungguhnya
  // await api.put(`/products/${selectedProduct.value.id}`, formData.value)

  if (!selectedProduct.value) return

  const idx = products.value.findIndex((p) => p.id === selectedProduct.value!.id)
  if (idx === -1) return

  const existing = products.value[idx]
  if (!existing) return

  // Tentukan ikon fallback baru berdasarkan kategori
  let fallbackIcon: Component = markRaw(UtensilsCrossed)
  if (formData.value.category === 'Minuman') {
    fallbackIcon = markRaw(Coffee)
  } else if (formData.value.category === 'Dessert') {
    fallbackIcon = markRaw(IceCreamCone)
  }

  products.value[idx] = {
    ...existing,
    name: formData.value.name,
    category: formData.value.category,
    price: formData.value.price,
    status: formData.value.status,
    imageUrl: formData.value.imagePreview ?? formData.value.imageUrl,
    description: formData.value.description,
    fallbackIcon,
  }

  closeAllModals()
}

/**
 * Menghapus produk dari daftar.
 * [API-READY] — saat ini menghapus dari array lokal.
 */
function deleteProduct(): void {
  // [API-READY] TODO: Ganti dengan pemanggilan API sesungguhnya
  // await api.delete(`/products/${selectedProduct.value.id}`)

  if (!selectedProduct.value) return
  products.value = products.value.filter((p) => p.id !== selectedProduct.value!.id)
  closeAllModals()
}

/** Buka modal tambah produk baru */
function openCreateModal(): void {
  formData.value = emptyForm()
  showCreateModal.value = true
}

/** Buka modal edit produk */
function openEditModal(product: ProductItem): void {
  selectedProduct.value = product
  formData.value = {
    name: product.name,
    category: product.category,
    price: product.price,
    status: product.status,
    imageUrl: product.imageUrl,
    imageFile: null,
    imagePreview: product.imageUrl,
    description: product.description,
  }
  showEditModal.value = true
}

/** Buka modal konfirmasi hapus produk */
function openDeleteModal(product: ProductItem): void {
  selectedProduct.value = product
  showDeleteModal.value = true
}

/** Tutup semua modal dan reset state */
function closeAllModals(): void {
  showCreateModal.value = false
  showEditModal.value = false
  showDeleteModal.value = false
  selectedProduct.value = null
  formData.value = emptyForm()
}

/** Navigasi ke halaman detail produk */
function goToDetail(product: ProductItem): void {
  router.push('/admin/products/' + product.id)
}

/**
 * Format angka menjadi format mata uang Rupiah.
 * Contoh: 38000 → "Rp 38.000"
 */
function formatPrice(n: number): string {
  return 'Rp ' + n.toLocaleString('id-ID')
}

/**
 * Mengembalikan kelas Tailwind untuk badge status.
 * Tersedia → hijau, Habis → merah.
 */
function getStatusClasses(status: ProductStatus): string {
  if (status === 'Tersedia') {
    return 'bg-green-50 text-green-700'
  }
  return 'bg-red-50 text-red-700'
}

/** Mengembalikan kelas warna dot status */
function getStatusDotClass(status: ProductStatus): string {
  if (status === 'Tersedia') {
    return 'text-green-500'
  }
  return 'text-red-500'
}

/**
 * Menangani upload gambar dari input file.
 * Membaca file dan membuat preview URL.
 */
function handleImageUpload(event: Event): void {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (!files || files.length === 0) return

  const file = files[0]
  if (!file) return

  formData.value.imageFile = file
  formData.value.imagePreview = URL.createObjectURL(file)
}

/** Hapus gambar dari form */
function removeImage(): void {
  formData.value.imageFile = null
  formData.value.imagePreview = null
  formData.value.imageUrl = null
}

/**
 * Mereset semua filter pencarian, kategori, dan status produk ke kondisi awal.
 */
function resetFilters(): void {
  searchQuery.value = ''
  categoryFilter.value = ''
  statusFilter.value = ''
}

// ============================================================
// Lifecycle — muat data saat komponen terpasang
// ============================================================
onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-[#F8FAFC] p-4 md:p-6 lg:p-8">
      <!-- ========================================================== -->
      <!-- Header Halaman                                             -->
      <!-- ========================================================== -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Manajemen Produk</h1>
          <p class="mt-1 text-sm text-gray-500">
            Kelola daftar produk, harga jual, kategori, dan status ketersediaan menu restoran.
          </p>
        </div>
        <button
          class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
          @click="openCreateModal"
        >
          <Plus class="h-4 w-4" />
          Tambah Produk
        </button>
      </div>

      <!-- ========================================================== -->
      <!-- Stat Cards (4 kartu ringkasan)                             -->
      <!-- ========================================================== -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Total Produk -->
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">Total Produk</p>
              <p class="mt-1 text-2xl font-bold text-gray-800">{{ statTotalProducts }}</p>
              <p class="mt-1 text-xs text-gray-400">Aktif di katalog menu</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50">
              <UtensilsCrossed class="h-6 w-6 text-[#C2410C]" />
            </div>
          </div>
        </div>

        <!-- Produk Tersedia -->
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">Produk Tersedia</p>
              <p class="mt-1 text-2xl font-bold text-gray-800">{{ statAvailableProducts }}</p>
              <p class="mt-1 text-xs text-gray-400">Siap dipesan kasir</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50">
              <CheckCircle class="h-6 w-6 text-green-600" />
            </div>
          </div>
        </div>

        <!-- Produk Habis -->
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">Produk Habis</p>
              <p class="mt-1 text-2xl font-bold text-red-600">{{ statOutOfStock }}</p>
              <p class="mt-1 text-xs text-gray-400">Stok bahan habis / nonaktif</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50">
              <ShoppingCart class="h-6 w-6 text-red-500" />
            </div>
          </div>
        </div>

        <!-- Total Kategori -->
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">Total Kategori</p>
              <p class="mt-1 text-2xl font-bold text-gray-800">{{ statTotalCategories }}</p>
              <p class="mt-1 text-xs text-gray-400">Kategori terhubung</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50">
              <LayoutGrid class="h-6 w-6 text-blue-600" />
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================== -->
      <!-- Bar Filter & Pencarian                                     -->
      <!-- ========================================================== -->
      <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <!-- Input Pencarian (Sisi Kiri / flex-1) -->
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari produk, SKU, atau kategori..."
              class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-9 text-sm text-gray-800 placeholder-slate-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
            />
            <button
              v-if="searchQuery"
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition-colors hover:text-slate-600"
              title="Hapus pencarian"
              @click="searchQuery = ''"
            >
              <X class="h-4 w-4" />
            </button>
          </div>

          <!-- Dropdown Kategori (Sisi Kanan) -->
          <select
            v-model="categoryFilter"
            class="h-10 w-full sm:w-48 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30 cursor-pointer"
          >
            <option value="">Semua Kategori</option>
            <option v-for="cat in categoryList" :key="cat" :value="cat">{{ cat }}</option>
          </select>

          <!-- Dropdown Status (Sisi Kanan) -->
          <select
            v-model="statusFilter"
            class="h-10 w-full sm:w-48 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30 cursor-pointer"
          >
            <option value="">Semua Status</option>
            <option value="Tersedia">Tersedia</option>
            <option value="Habis">Habis</option>
          </select>

          <!-- Tombol Reset Filter -->
          <button
            type="button"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-800 shrink-0 cursor-pointer"
            title="Reset semua filter"
            @click="resetFilters"
          >
            <RotateCcw class="h-4 w-4" />
            Reset Filter
          </button>
        </div>
      </div>

      <!-- ========================================================== -->
      <!-- Kartu Utama: Tabel + Paginasi                              -->
      <!-- ========================================================== -->
      <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <!-- ====================================================== -->
        <!-- Tabel Produk                                            -->
        <!-- ====================================================== -->
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50/60">
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  No.
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Gambar &amp; Nama Produk
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Kategori
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Harga Jual
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Status
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr
                v-for="(product, rowIndex) in paginatedProducts"
                :key="product.id"
                class="transition hover:bg-gray-50/50"
              >
                <!-- NO -->
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                  {{ startIndex + rowIndex + 1 }}
                </td>

                <!-- GAMBAR & NAMA PRODUK -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <!-- Gambar jika ada -->
                    <img
                      v-if="product.imageUrl"
                      :src="product.imageUrl"
                      :alt="product.name"
                      class="h-11 w-11 rounded-lg object-cover"
                    />
                    <!-- Ikon fallback jika tidak ada gambar -->
                    <div
                      v-else
                      class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-orange-50"
                    >
                      <component :is="product.fallbackIcon" class="h-5 w-5 text-[#C2410C]" />
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-gray-800">{{ product.name }}</p>
                      <p class="text-xs text-gray-400">SKU: {{ product.sku }}</p>
                    </div>
                  </div>
                </td>

                <!-- KATEGORI -->
                <td class="whitespace-nowrap px-4 py-3">
                  <span class="rounded-full bg-slate-100 px-3 py-0.5 text-xs font-medium text-slate-600">
                    {{ product.category }}
                  </span>
                </td>

                <!-- HARGA JUAL -->
                <td class="whitespace-nowrap px-4 py-3">
                  <span class="text-sm font-bold text-gray-800">{{ formatPrice(product.price) }}</span>
                </td>

                <!-- STATUS -->
                <td class="whitespace-nowrap px-4 py-3">
                  <span
                    :class="getStatusClasses(product.status)"
                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                  >
                    <span :class="getStatusDotClass(product.status)">•</span>
                    {{ product.status }}
                  </span>
                </td>

                <!-- AKSI -->
                <td class="whitespace-nowrap px-4 py-3">
                  <div class="flex items-center gap-1">
                    <!-- Lihat Detail -->
                    <button
                      class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                      title="Lihat detail"
                      @click="goToDetail(product)"
                    >
                      <Eye class="h-4 w-4" />
                    </button>
                    <!-- Edit -->
                    <button
                      class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                      title="Edit produk"
                      @click="openEditModal(product)"
                    >
                      <Pencil class="h-4 w-4" />
                    </button>
                    <!-- Hapus -->
                    <button
                      class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                      title="Hapus produk"
                      @click="openDeleteModal(product)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Pesan kosong jika tidak ada hasil -->
              <tr v-if="paginatedProducts.length === 0">
                <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">
                  Tidak ada produk yang ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ====================================================== -->
        <!-- Pagination Footer                                       -->
        <!-- ====================================================== -->
        <div
          class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row"
        >
          <!-- Info teks -->
          <p class="text-sm text-gray-500">
            Menampilkan {{ filteredProducts.length > 0 ? startIndex + 1 : 0 }} -
            {{ endIndex }} dari {{ filteredProducts.length }} produk
          </p>

          <!-- Tombol paginasi -->
          <div class="flex items-center gap-1">
            <!-- Tombol Sebelumnya -->
            <button
              type="button"
              :disabled="currentPage <= 1"
              class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
              @click="goToPage(currentPage - 1)"
            >
              <ChevronLeft class="h-3.5 w-3.5" />
              Sebelumnya
            </button>

            <!-- Nomor halaman (sliding window) -->
            <template v-for="(page, idx) in visiblePages" :key="'p-' + idx">
              <!-- Elipsis -->
              <span v-if="page === '...'" class="px-1.5 text-xs text-gray-400">…</span>

              <!-- Tombol Nomor Halaman -->
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
              Berikutnya
              <ChevronRight class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================== -->
      <!-- Footer Note — info sinkronisasi POS                        -->
      <!-- ========================================================== -->
      <div class="mt-4 flex items-start gap-2 rounded-lg border border-blue-100 bg-blue-50/60 px-4 py-3">
        <Info class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" />
        <p class="text-xs leading-relaxed text-blue-700">
          * Menampilkan data master produk restoran. Perubahan status ketersediaan akan langsung
          tersinkronisasi ke terminal kasir POS secara seketika (real-time).
        </p>
      </div>

      <!-- ========================================================== -->
      <!-- Modal 1: Tambah Produk Baru                                -->
      <!-- ========================================================== -->
      <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeAllModals"
      >
        <div class="max-h-[90vh] w-full max-w-lg flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
          <!-- Header Modal -->
          <div class="flex shrink-0 items-start justify-between border-b border-gray-100 p-6">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50">
                <UtensilsCrossed class="h-5 w-5 text-[#C2410C]" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-gray-800">Tambah Produk Baru</h2>
                <p class="mt-0.5 text-sm text-gray-500">
                  Tambahkan produk menu baru ke katalog restoran dan terminal kasir POS.
                </p>
              </div>
            </div>
            <button
              class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
              @click="closeAllModals"
            >
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Body Modal — Form (scrollable) -->
          <form id="createProductForm" @submit.prevent="createProduct" class="flex-1 space-y-4 overflow-y-auto p-6">
            <!-- Kategori Produk -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                Kategori Produk <span class="text-red-500">*</span>
              </label>
              <select
                v-model="formData.category"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              >
                <option value="" disabled>Pilih kategori</option>
                <option v-for="cat in categoryList" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>

            <!-- Nama Produk -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                Nama Produk <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.name"
                type="text"
                placeholder="Contoh: Ayam Geprek Sambal Korek"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
            </div>

            <!-- Baris: Harga Jual & Status -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- Harga Jual -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Harga Jual (Rp) <span class="text-red-500">*</span>
                </label>
                <div
                  class="flex items-center rounded-lg border border-gray-200 bg-slate-50 px-3 transition focus-within:border-[#C2410C] focus-within:bg-white focus-within:ring-1 focus-within:ring-[#C2410C]/30"
                >
                  <span class="shrink-0 text-sm font-bold text-[#C2410C]">Rp</span>
                  <input
                    v-model.number="formData.price"
                    type="number"
                    min="0"
                    placeholder="0"
                    class="w-full bg-transparent py-2.5 pl-2 pr-1 text-sm font-semibold text-gray-800 outline-none"
                  />
                </div>
                <p class="mt-1 text-xs text-gray-400">Harga sebelum pajak PB1</p>
              </div>

              <!-- Status Ketersediaan -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Status Ketersediaan <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="formData.status"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                >
                  <option value="Tersedia">Tersedia (Bisa Dipesan di POS)</option>
                  <option value="Habis">Habis</option>
                </select>
                <p class="mt-1 text-xs font-medium text-emerald-600">✓ Aktif langsung di semua terminal kasir</p>
              </div>
            </div>

            <!-- Foto Produk (Opsional) -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                Foto Produk (Opsional)
              </label>
              <!-- Preview gambar jika sudah dipilih -->
              <div v-if="formData.imagePreview" class="rounded-xl border border-gray-200 bg-slate-50/50 p-3">
                <div class="flex items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <img
                      :src="formData.imagePreview"
                      alt="Preview"
                      class="h-14 w-14 rounded-lg object-cover border border-gray-200"
                    />
                    <div>
                      <p class="text-xs font-semibold text-gray-800">Foto terpilih</p>
                      <p class="text-[11px] text-gray-400">Format: PNG, JPG maks 2MB</p>
                    </div>
                  </div>
                  <button
                    type="button"
                    class="rounded-lg p-1.5 text-red-500 transition hover:bg-red-50"
                    title="Hapus foto"
                    @click="removeImage"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </div>
              <!-- Area upload -->
              <label
                v-else
                class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-6 transition hover:border-[#C2410C] hover:bg-orange-50/20"
              >
                <Camera class="mb-2 h-7 w-7 text-gray-400" />
                <span class="text-xs font-medium text-gray-600">Klik untuk upload foto produk menu</span>
                <span class="mt-0.5 text-[11px] text-gray-400">Format PNG, JPG maks 2MB</span>
                <input
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="handleImageUpload"
                />
              </label>
            </div>

            <!-- Deskripsi Produk (Opsional) -->
            <div>
              <div class="mb-1.5 flex items-center justify-between">
                <label class="text-sm font-semibold text-gray-700">
                  Deskripsi Produk
                </label>
                <span class="text-xs text-gray-400">Opsional</span>
              </div>
              <textarea
                v-model="formData.description"
                rows="3"
                maxlength="250"
                placeholder="Tuliskan keterangan komposisi, porsi, atau catatan produk..."
                class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
              <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                <span>Akan tampil pada nota ringkasan atau detail menu di tablet POS.</span>
                <span>{{ formData.description.length }}/250</span>
              </div>
            </div>
          </form>

          <!-- Footer Modal -->
          <div class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button
              type="button"
              class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
              @click="closeAllModals"
            >
              Batal
            </button>
            <button
              type="submit"
              form="createProductForm"
              class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
            >
              <Check class="h-4 w-4" />
              Simpan Produk
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================== -->
      <!-- Modal 2: Edit Produk                                       -->
      <!-- ========================================================== -->
      <div
        v-if="showEditModal && selectedProduct"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeAllModals"
      >
        <div class="max-h-[90vh] w-full max-w-lg flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
          <!-- Header Modal -->
          <div class="flex shrink-0 items-start justify-between border-b border-gray-100 p-6">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50">
                <SquarePen class="h-5 w-5 text-[#C2410C]" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h2 class="text-lg font-bold text-gray-800">Edit Produk</h2>
                  <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-[#C2410C]">
                    SKU: {{ selectedProduct.sku }}
                  </span>
                </div>
                <p class="mt-0.5 text-sm text-gray-500">
                  Perbarui informasi produk, harga jual, dan status ketersediaan menu.
                </p>
              </div>
            </div>
            <button
              class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
              @click="closeAllModals"
            >
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Body Modal — Form Edit (scrollable) -->
          <div class="flex-1 space-y-4 overflow-y-auto p-6">
            <!-- Kategori Produk -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                Kategori Produk <span class="text-red-500">*</span>
              </label>
              <select
                v-model="formData.category"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              >
                <option value="" disabled>Pilih kategori</option>
                <option v-for="cat in categoryList" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>

            <!-- Nama Produk -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                Nama Produk Menu <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.name"
                type="text"
                placeholder="Contoh: Ayam Geprek Sambal Korek"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
            </div>

            <!-- Baris: Harga Jual & Status -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- Harga Jual -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Harga Jual (IDR) <span class="text-red-500">*</span>
                </label>
                <div
                  class="flex items-center rounded-lg border border-gray-200 bg-slate-50 px-3 transition focus-within:border-[#C2410C] focus-within:bg-white focus-within:ring-1 focus-within:ring-[#C2410C]/30"
                >
                  <span class="shrink-0 text-sm font-bold text-[#C2410C]">Rp</span>
                  <input
                    v-model.number="formData.price"
                    type="number"
                    min="0"
                    placeholder="0"
                    class="w-full bg-transparent py-2.5 pl-2 pr-1 text-sm font-semibold text-gray-800 outline-none"
                  />
                </div>
                <p class="mt-1 text-xs text-gray-400">Harga kotor sebelum pajak PB1 (10%)</p>
              </div>

              <!-- Status Ketersediaan -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Status Ketersediaan <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="formData.status"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                >
                  <option value="Tersedia">Tersedia (Bisa Dipesan di POS)</option>
                  <option value="Habis">Habis</option>
                </select>
                <p class="mt-1 text-xs font-medium text-emerald-600">✓ Aktif langsung di semua terminal kasir</p>
              </div>
            </div>

            <!-- Foto Produk (Opsional) -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                Foto Tampilan Menu
              </label>
              <!-- Preview jika ada gambar -->
              <div v-if="formData.imagePreview" class="rounded-xl border border-gray-200 bg-slate-50/50 p-3">
                <div class="flex items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-orange-50 border border-orange-100">
                      <img
                        :src="formData.imagePreview"
                        alt="Preview"
                        class="h-full w-full object-cover"
                        @error="(e) => ((e.target as HTMLElement).style.display = 'none')"
                      />
                      <UtensilsCrossed class="h-6 w-6 text-[#C2410C]" />
                    </div>
                    <div>
                      <p class="text-xs font-semibold text-gray-800">Foto terpilih</p>
                      <p class="text-[11px] text-gray-400">Format: PNG, JPG maks 2MB</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <label
                      class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                      <Camera class="h-3.5 w-3.5 text-gray-500" />
                      Ganti Foto
                      <input
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="handleImageUpload"
                      />
                    </label>
                    <button
                      type="button"
                      class="rounded-lg p-1.5 text-red-500 transition hover:bg-red-50"
                      title="Hapus foto"
                      @click="removeImage"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
              <!-- Area upload jika belum ada gambar -->
              <label
                v-else
                class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-6 transition hover:border-[#C2410C] hover:bg-orange-50/20"
              >
                <Camera class="mb-2 h-7 w-7 text-gray-400" />
                <span class="text-xs font-medium text-gray-600">Klik untuk upload foto produk menu</span>
                <span class="mt-0.5 text-[11px] text-gray-400">Format PNG, JPG maks 2MB</span>
                <input
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="handleImageUpload"
                />
              </label>
            </div>

            <!-- Deskripsi Produk (Opsional) dengan counter karakter -->
            <div>
              <div class="mb-1.5 flex items-center justify-between">
                <label class="text-sm font-semibold text-gray-700">
                  Deskripsi Produk
                </label>
                <span class="text-xs text-gray-400">Opsional</span>
              </div>
              <textarea
                v-model="formData.description"
                rows="3"
                maxlength="250"
                placeholder="Tuliskan keterangan komposisi, porsi, atau catatan produk..."
                class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
              <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                <span>Akan tampil pada nota ringkasan atau detail menu di tablet POS.</span>
                <span>{{ formData.description.length }}/250</span>
              </div>
            </div>

            <!-- Info sinkronisasi -->
            <div class="flex items-start gap-2.5 rounded-xl border border-green-200 bg-green-50/70 p-3">
              <Info class="mt-0.5 h-4 w-4 shrink-0 text-green-600" />
              <p class="text-xs leading-relaxed text-green-700">
                Perubahan harga atau ketersediaan akan otomatis disinkronisasi dalam hitungan detik
                ke seluruh terminal POS aktif di restoran tanpa perlu restart aplikasi kasir.
              </p>
            </div>
          </div>

          <!-- Footer Modal Edit -->
          <div class="flex shrink-0 items-center justify-between border-t border-gray-100 px-6 py-4">
            <p class="text-xs text-gray-400">Diedit terakhir: Hari ini 10:42</p>
            <div class="flex items-center gap-3">
              <button
                type="button"
                class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                @click="closeAllModals"
              >
                Batal
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
                @click="updateProduct"
              >
                <Check class="h-4 w-4" />
                Simpan Perubahan
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================== -->
      <!-- Modal 3: Hapus Produk                                      -->
      <!-- ========================================================== -->
      <div
        v-if="showDeleteModal && selectedProduct"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeAllModals"
      >
        <div class="max-h-[90vh] w-full max-w-md flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
          <!-- Body — layout center (scrollable) -->
          <div class="flex-1 overflow-y-auto p-6 text-center">
            <!-- Ikon merah -->
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
              <Trash2 class="h-7 w-7 text-red-500" />
            </div>

            <h2 class="text-lg font-bold text-gray-800">Hapus Produk?</h2>
            <p class="mt-2 text-sm text-gray-500">
              Apakah Anda yakin ingin menghapus
              <strong class="text-gray-700">{{ selectedProduct.name }}</strong>
              dari katalog menu restoran?
            </p>

            <!-- Kartu ringkasan produk -->
            <div class="mt-5 rounded-lg border border-gray-200 text-left">
              <div class="grid grid-cols-2 divide-x divide-gray-100">
                <div class="border-b border-gray-100 px-4 py-3">
                  <p class="text-xs font-medium uppercase text-gray-400">Kode SKU</p>
                  <p class="mt-0.5 text-sm font-semibold text-gray-700">{{ selectedProduct.sku }}</p>
                </div>
                <div class="border-b border-gray-100 px-4 py-3">
                  <p class="text-xs font-medium uppercase text-gray-400">Nama Produk</p>
                  <p class="mt-0.5 text-sm font-semibold text-gray-700">{{ selectedProduct.name }}</p>
                </div>
                <div class="px-4 py-3">
                  <p class="text-xs font-medium uppercase text-gray-400">Kategori</p>
                  <p class="mt-0.5 text-sm font-semibold text-gray-700">{{ selectedProduct.category }}</p>
                </div>
                <div class="px-4 py-3">
                  <p class="text-xs font-medium uppercase text-gray-400">Harga Jual</p>
                  <p class="mt-0.5 text-sm font-semibold text-gray-700">{{ formatPrice(selectedProduct.price) }}</p>
                </div>
              </div>
              <div class="border-t border-gray-100 px-4 py-3">
                <p class="text-xs font-medium uppercase text-gray-400">Status Menu</p>
                <span
                  :class="getStatusClasses(selectedProduct.status)"
                  class="mt-1 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                >
                  <span :class="getStatusDotClass(selectedProduct.status)">•</span>
                  {{ selectedProduct.status }}
                </span>
              </div>
            </div>

            <!-- Peringatan merah -->
            <div class="mt-4 flex items-start gap-2 rounded-lg border border-red-100 bg-red-50/60 px-4 py-3 text-left">
              <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
              <div>
                <p class="text-xs font-semibold text-red-700">Proteksi Data &amp; Riwayat Transaksi:</p>
                <p class="mt-0.5 text-xs leading-relaxed text-red-600">
                  Produk yang telah tercatat dalam riwayat transaksi penjualan tetap tersimpan di
                  laporan keuangan untuk keperluan audit. Namun produk ini tidak akan muncul lagi di
                  terminal kasir POS setelah dihapus.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer Modal Hapus -->
          <div class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button
              class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
              @click="closeAllModals"
            >
              Batal
            </button>
            <button
              class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
              @click="deleteProduct"
            >
              <Trash2 class="h-4 w-4" />
              Hapus Produk
            </button>
          </div>

          <!-- Catatan kaki italic -->
          <p class="px-6 pb-4 text-center text-xs italic text-gray-400">
            (Tindakan ini permanen dan akan menghapus produk dari menu kasir POS)
          </p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
