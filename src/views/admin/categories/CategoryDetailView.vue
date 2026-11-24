<script setup lang="ts">
import { ref, computed, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  ArrowLeft,
  Utensils,
  Coffee,
  Soup,
  IceCreamCone,
  PlusCircle,
  Tag,
  Package,
  Calendar,
  Clock,
  AlignLeft,
  Info,
  ChefHat,
  CookingPot,
  Beef,
  Pencil,
  X,
  Check,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Representasi lengkap satu entitas kategori untuk halaman detail ────────
interface CategoryDetail {
  id: number
  code: string
  name: string
  description: string
  status: 'Aktif' | 'Nonaktif'
  productCount: number
  displayOrder: number
  department: string
  createdAt: string
  updatedAt: string
  updatedBy: string
}

// ─── Representasi satu produk yang terkait dengan kategori ──────────────────
interface CategoryProduct {
  id: number
  name: string
  subtitle: string
  sku: string
  price: number
  status: 'Tersedia' | 'Habis'
  icon: Component
}

// ─── Data form yang digunakan di modal edit kategori ─────────────────────────
interface CategoryForm {
  name: string
  description: string
  status: 'Aktif' | 'Nonaktif'
}

// ═══════════════════════════════════════════════════════════════════════════════
// ICON HELPER — Peta ikon kategori berdasarkan kata kunci nama
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Peta kata kunci → ikon Lucide (di-markRaw agar tidak reaktif) ──────────
const categoryIconMap: Record<string, Component> = {
  utama: markRaw(Utensils),
  minuman: markRaw(Coffee),
  pembuka: markRaw(Soup),
  dessert: markRaw(IceCreamCone),
  tambahan: markRaw(PlusCircle),
}
const fallbackIcon: Component = markRaw(Tag)

// ─── Memilih ikon yang sesuai berdasarkan nama kategori ─────────────────────
// Mencocokkan kata kunci dalam nama (case-insensitive) dengan peta ikon.
function getCategoryIcon(name: string): Component {
  const lower = name.toLowerCase()
  for (const [keyword, icon] of Object.entries(categoryIconMap)) {
    if (lower.includes(keyword)) return icon
  }
  return fallbackIcon
}

// ═══════════════════════════════════════════════════════════════════════════════
// ROUTING
// ═══════════════════════════════════════════════════════════════════════════════

const route = useRoute()
const router = useRouter()

// ─── ID kategori dari parameter rute ────────────────────────────────────────
const categoryId = computed<string>(() => {
  const id = route.params['id']
  return Array.isArray(id) ? (id[0] ?? '') : (id ?? '')
})

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Data detail kategori yang sedang ditampilkan ───────────────────────────
const category = ref<CategoryDetail>({
  id: 1,
  code: 'KAT-001',
  name: 'Makanan Utama',
  description:
    'Aneka hidangan pokok dan olahan nasi, ayam, serta daging utama restoran. Disajikan hangat langsung dari stasiun dapur utama dengan standar porsi standar resto.',
  status: 'Aktif',
  productCount: 28,
  displayOrder: 1,
  department: 'Departemen Dapur Panas',
  createdAt: '15 Oktober 2026',
  updatedAt: '18 Oktober 2026 · 14:32 WIB',
  updatedBy: 'Admin Restoran',
})

// ─── Daftar produk dalam kategori ini (halaman pertama) ─────────────────────
const products = ref<CategoryProduct[]>([
  {
    id: 1,
    name: 'Ayam Bakar Madu Spesial',
    subtitle: 'Porsi Lengkap + Sambal Bajak',
    sku: 'SKU-MK-0012',
    price: 38000,
    status: 'Tersedia',
    icon: markRaw(ChefHat),
  },
  {
    id: 2,
    name: 'Nasi Goreng Kampung',
    subtitle: 'Dengan Telur Ceplok & Kerupuk',
    sku: 'SKU-MK-0013',
    price: 28000,
    status: 'Tersedia',
    icon: markRaw(CookingPot),
  },
  {
    id: 3,
    name: 'Sate Ayam Madura',
    subtitle: '10 Tusuk + Bumbu Kacang Kental',
    sku: 'SKU-MK-0014',
    price: 35000,
    status: 'Tersedia',
    icon: markRaw(Beef),
  },
  {
    id: 4,
    name: 'Rendang Daging Sapi',
    subtitle: 'Bumbu Padang Asli + Daun Jeruk',
    sku: 'SKU-MK-0015',
    price: 45000,
    status: 'Tersedia',
    icon: markRaw(Utensils),
  },
  {
    id: 5,
    name: 'Bebek Goreng Sambal Ijo',
    subtitle: 'Bebek Utuh + Lalapan Segar',
    sku: 'SKU-MK-0016',
    price: 42000,
    status: 'Tersedia',
    icon: markRaw(ChefHat),
  },
])

// ─── Visibilitas modal edit (true = tampil) ─────────────────────────────────
const showEditModal = ref(false)

// ─── Data form yang digunakan di modal edit ─────────────────────────────────
const formData = ref<CategoryForm>({
  name: '',
  description: '',
  status: 'Aktif',
})

// ─── Halaman aktif saat ini pada pagination tabel produk ────────────────────
const currentPage = ref(1)

// ─── Jumlah total halaman produk (simulasi: 28 produk / 5 per halaman = 6) ──
const totalPages = ref(6)

// ─── Total produk (untuk info pagination) ───────────────────────────────────
const totalProducts = computed<number>(() => category.value.productCount)

// ─── Jumlah produk per halaman ──────────────────────────────────────────────
const itemsPerPage = 5

// ─── Indeks awal data yang ditampilkan ──────────────────────────────────────
const startIndex = computed<number>(() => (currentPage.value - 1) * itemsPerPage + 1)

// ─── Indeks akhir data yang ditampilkan ─────────────────────────────────────
const endIndex = computed<number>(() =>
  Math.min(currentPage.value * itemsPerPage, totalProducts.value),
)

// ─── Daftar nomor halaman yang terlihat (sliding window) ────────────────────
// Menghasilkan array berisi angka halaman dan string '...' (elipsis).
// Aturan sliding window:
//   - Jika currentPage <= 3       → [1, 2, 3, '...', totalPages]
//   - Jika currentPage >= total-2  → [1, '...', total-2, total-1, total]
//   - Di tengah                    → [1, '...', cur-1, cur, cur+1, '...', total]
// Jika totalPages <= 5, tampilkan semua halaman tanpa elipsis.
const visiblePages = computed<(number | string)[]>(() => {
  const total = totalPages.value
  const current = currentPage.value

  // Jika total halaman sedikit, tampilkan semua tanpa elipsis
  if (total <= 5) {
    const pages: number[] = []
    for (let i = 1; i <= total; i++) pages.push(i)
    return pages
  }

  // Posisi awal: tampilkan 3 halaman pertama + elipsis + halaman terakhir
  if (current <= 3) {
    return [1, 2, 3, '...', total]
  }

  // Posisi akhir: tampilkan halaman pertama + elipsis + 3 halaman terakhir
  if (current >= total - 2) {
    return [1, '...', total - 2, total - 1, total]
  }

  // Posisi tengah: halaman pertama + elipsis + jendela 3 halaman + elipsis + halaman terakhir
  return [1, '...', current - 1, current, current + 1, '...', total]
})

// ═══════════════════════════════════════════════════════════════════════════════
// FORMAT HELPER
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memformat angka menjadi format mata uang Rupiah ────────────────────────
function formatCurrency(value: number): string {
  return `Rp ${value.toLocaleString('id-ID')}`
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI NAVIGASI & DATA
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Berpindah ke nomor halaman tertentu ────────────────────────────────────
function goToPage(page: number | string): void {
  const pageNum = typeof page === 'string' ? parseInt(page, 10) : page
  if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages.value) {
    currentPage.value = pageNum
  }
}

// ─── Navigasi kembali ke halaman daftar kategori ────────────────────────────
function goBack(): void {
  router.push('/admin/categories')
}

// ─── Membuka modal edit dengan data kategori yang sedang dilihat ─────────────
function openEditModal(): void {
  formData.value = {
    name: category.value.name,
    description: category.value.description,
    status: category.value.status,
  }
  showEditModal.value = true
}

// ─── Menutup modal edit kategori ─────────────────────────────────────────────
function closeEditModal(): void {
  showEditModal.value = false
}

// ─── Menyimpan perubahan data kategori dari modal edit ───────────────────────
// [API-READY] Akan memanggil PUT /api/admin/categories/{id} dengan formData,
// lalu memperbarui data detail kategori di halaman.
function updateCategory(): void {
  if (!formData.value.name.trim()) return

  // [API-READY] const { data } = await api.put(`/admin/categories/${category.value.id}`, formData.value)
  // category.value = data

  category.value = {
    ...category.value,
    name: formData.value.name.trim(),
    description: formData.value.description.trim(),
    status: formData.value.status,
  }

  closeEditModal()
}

// ─── Mengambil detail kategori dari API berdasarkan ID ──────────────────────
// [API-READY] Akan memanggil GET /api/admin/categories/{id} dan mengisi
// `category` serta `products` dengan response data dari server.
async function fetchCategoryDetail(_id: string): Promise<void> {
  // [API-READY] const { data } = await api.get(`/admin/categories/${_id}`)
  // category.value = data.category
  // products.value = data.products
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memuat data detail kategori saat komponen pertama kali dimuat ───────────
onMounted(() => {
  fetchCategoryDetail(categoryId.value)
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Page Header ═══════════ -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Detail Kategori</h1>
        <p class="mt-1 text-sm text-gray-500">Informasi lengkap kategori produk restoran.</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="goBack"
        >
          <ArrowLeft class="h-4 w-4" />
          Kembali
        </button>
        <button
          type="button"
          class="flex items-center gap-2 rounded-lg bg-[#C2410C] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#B45309]"
          @click="openEditModal"
        >
          <Pencil class="h-4 w-4" />
          Edit Kategori
        </button>
      </div>
    </div>

    <!-- ═══════════ Kartu Informasi Utama Kategori ═══════════ -->
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white">
      <!-- Header kartu: Ikon, nama, badge status, kode kategori -->
      <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
          <!-- Kotak ikon kategori berwarna lembut -->
          <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-orange-50">
            <component :is="getCategoryIcon(category.name)" class="h-7 w-7 text-[#C2410C]" />
          </div>
          <div>
            <div class="flex items-center gap-2.5">
              <!-- Nama kategori besar -->
              <h2 class="text-xl font-bold text-gray-900">{{ category.name }}</h2>
              <!-- Badge status hijau -->
              <span
                v-if="category.status === 'Aktif'"
                class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                Aktif
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                Nonaktif
              </span>
            </div>
            <!-- Subjudul departemen -->
            <p class="mt-0.5 text-sm text-gray-500">{{ category.department }} · Master Menu</p>
          </div>
        </div>
        <!-- Kode kategori di pojok kanan atas -->
        <div class="text-right">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Kode Kategori</p>
          <p class="mt-0.5 text-xl font-bold text-[#C2410C]">{{ category.code }}</p>
        </div>
      </div>

      <!-- Grid Metadata Baris 1: ID, Nama, Urutan, Status (4 kolom) -->
      <div class="grid grid-cols-1 gap-px border-b border-gray-100 bg-gray-100 md:grid-cols-4">
        <!-- ID Kategori -->
        <div class="bg-white px-5 py-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-400">ID Kategori</p>
          <p class="mt-1.5 text-sm font-bold text-gray-800">{{ category.code }}</p>
        </div>
        <!-- Nama Kategori -->
        <div class="bg-white px-5 py-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Nama Kategori</p>
          <p class="mt-1.5 text-sm font-bold text-gray-800">{{ category.name }}</p>
        </div>
        <!-- Urutan Tampilan -->
        <div class="bg-white px-5 py-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Urutan Tampilan</p>
          <div class="mt-1.5 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-[#C2410C] text-xs font-bold text-white">
              {{ category.displayOrder }}
            </span>
            <span class="text-sm font-medium text-gray-600">Prioritas Utama</span>
          </div>
        </div>
        <!-- Status Kategori -->
        <div class="bg-white px-5 py-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Status Kategori</p>
          <div class="mt-1.5">
            <span
              v-if="category.status === 'Aktif'"
              class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
              Aktif Digunakan
            </span>
            <span
              v-else
              class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
              Nonaktif
            </span>
          </div>
        </div>
      </div>

      <!-- Grid Metadata Baris 2: Jumlah Produk, Tanggal Dibuat, Terakhir Diperbarui (3 kolom) -->
      <div class="grid grid-cols-1 gap-px border-b border-gray-100 bg-gray-100 md:grid-cols-3">
        <!-- Jumlah Produk Terkait -->
        <div class="bg-white px-5 py-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Jumlah Produk Terkait</p>
          <div class="mt-1.5 flex items-center gap-2 text-sm font-bold text-gray-800">
            <Package class="h-4 w-4 text-[#C2410C]" />
            {{ category.productCount }} produk
          </div>
        </div>
        <!-- Tanggal Dibuat -->
        <div class="bg-white px-5 py-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Tanggal Dibuat</p>
          <div class="mt-1.5 flex items-center gap-2 text-sm font-bold text-gray-800">
            <Calendar class="h-4 w-4 text-gray-400" />
            {{ category.createdAt }}
          </div>
        </div>
        <!-- Terakhir Diperbarui -->
        <div class="bg-white px-5 py-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Terakhir Diperbarui</p>
          <div class="mt-1.5 flex items-center gap-2 text-sm font-bold text-gray-800">
            <Clock class="h-4 w-4 text-gray-400" />
            {{ category.updatedAt }} oleh {{ category.updatedBy }}
          </div>
        </div>
      </div>

      <!-- Deskripsi Kategori -->
      <div class="px-6 py-5">
        <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500">
          <AlignLeft class="h-4 w-4" />
          Deskripsi Kategori
        </div>
        <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ category.description }}</p>
      </div>
    </div>

    <!-- ═══════════ Tabel Produk dalam Kategori ═══════════ -->
    <div class="mt-6 overflow-hidden rounded-xl border border-gray-100 bg-white">
      <!-- Header tabel produk -->
      <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <div class="flex items-center gap-2.5">
            <h3 class="text-lg font-bold text-gray-900">Produk dalam Kategori Ini</h3>
            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-700">
              {{ totalProducts }} Total
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-500">
            Daftar produk yang terhubung dengan kategori {{ category.name }}.
          </p>
        </div>
      </div>

      <!-- Tabel -->
      <div class="overflow-x-auto">
        <table class="w-full min-w-[700px]">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="w-16 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">No.</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nama Produk</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">SKU</th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Harga</th>
              <th class="w-28 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(product, index) in products"
              :key="product.id"
              class="border-b border-gray-50 transition-colors hover:bg-gray-50/50"
            >
              <!-- Nomor urut -->
              <td class="px-5 py-4 text-sm text-gray-500">{{ startIndex + index }}</td>
              <!-- Nama produk + subtitle -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-50">
                    <component :is="product.icon" class="h-4 w-4 text-[#C2410C]" />
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-800">{{ product.name }}</p>
                    <p class="text-xs text-gray-400">{{ product.subtitle }}</p>
                  </div>
                </div>
              </td>
              <!-- Kode SKU dalam badge -->
              <td class="px-5 py-4">
                <span class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 font-mono text-xs font-medium text-gray-600">
                  {{ product.sku }}
                </span>
              </td>
              <!-- Harga -->
              <td class="px-5 py-4 text-right text-sm font-semibold text-gray-800">
                {{ formatCurrency(product.price) }}
              </td>
              <!-- Status -->
              <td class="px-5 py-4 text-center">
                <span
                  :class="[
                    'inline-flex items-center gap-1 text-xs font-medium',
                    product.status === 'Tersedia' ? 'text-green-600' : 'text-red-500',
                  ]"
                >
                  <span
                    :class="[
                      'h-1.5 w-1.5 rounded-full',
                      product.status === 'Tersedia' ? 'bg-green-500' : 'bg-red-500',
                    ]"
                  ></span>
                  {{ product.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row">
        <p class="text-sm text-gray-500">
          Menampilkan {{ totalProducts > 0 ? startIndex : 0 }} - {{ endIndex }} dari {{ totalProducts }} produk
        </p>
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

          <!-- Nomor Halaman (sliding window) -->
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

    <!-- ═══════════ Catatan Kaki ═══════════ -->
    <div class="mt-5 flex items-center gap-2 text-xs text-gray-400">
      <Info class="h-3.5 w-3.5 shrink-0" />
      <span>* Menampilkan data contoh produk untuk simulasi antarmuka portal kasir & operasional restoran.</span>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Edit Kategori                                                  -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="absolute inset-0" @click="closeEditModal"></div>
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl" @click.stop>
          <!-- Header modal -->
          <div class="flex shrink-0 items-start justify-between border-b border-gray-100 p-6">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                <Pencil class="h-5 w-5 text-[#C2410C]" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-lg font-bold text-gray-900">Edit Kategori</h3>
                  <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-[#C2410C]">{{ category.code }}</span>
                </div>
                <p class="mt-0.5 text-sm text-gray-500">Perbarui informasi kategori produk restoran.</p>
              </div>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" @click="closeEditModal">
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Form Body (scrollable) -->
          <form id="editCategoryDetailForm" class="flex-1 space-y-4 overflow-y-auto p-6" @submit.prevent="updateCategory">
            <!-- Nama Kategori -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Nama Kategori <span class="text-red-500">*</span></label>
              <input
                v-model="formData.name"
                type="text"
                required
                placeholder="Masukkan nama kategori"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
            </div>

            <!-- Deskripsi -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Deskripsi Kategori</label>
              <textarea
                v-model="formData.description"
                rows="3"
                placeholder="Tuliskan deskripsi kategori..."
                class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              ></textarea>
            </div>

            <!-- Status -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Status Kategori</label>
              <select
                v-model="formData.status"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              >
                <option value="Aktif">Aktif</option>
                <option value="Nonaktif">Nonaktif</option>
              </select>
            </div>
          </form>

          <!-- Footer Buttons -->
          <div class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button
              type="button"
              class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
              @click="closeEditModal"
            >
              Batal
            </button>
            <button
              type="submit"
              form="editCategoryDetailForm"
              class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
            >
              <Check class="h-4 w-4" />
              Simpan Perubahan
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </DashboardLayout>
</template>

