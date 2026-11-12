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

// ─── Navigasi kembali ke halaman daftar kategori ────────────────────────────
function goBack(): void {
  router.push('/admin/categories')
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
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Detail Kategori</h1>
        <p class="mt-1 text-sm text-gray-500">Informasi lengkap kategori produk restoran.</p>
      </div>
      <button
        type="button"
        class="flex shrink-0 items-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
        @click="goBack"
      >
        <ArrowLeft class="h-4 w-4" />
        Kembali ke Daftar
      </button>
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
      <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 sm:flex-row">
        <p class="text-sm text-gray-500">
          Menampilkan {{ startIndex }}–{{ endIndex }} dari {{ totalProducts }} produk
        </p>
        <div class="flex items-center gap-1">
          <button
            type="button"
            :disabled="currentPage <= 1"
            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="currentPage--"
          >
            Sebelumnya
          </button>
          <template v-for="(page, idx) in visiblePages" :key="'p-' + idx">
            <!-- Elipsis (tidak bisa diklik) -->
            <span v-if="page === '...'" class="px-1 text-xs text-gray-400">…</span>
            <!-- Tombol nomor halaman -->
            <button
              v-else
              type="button"
              :class="[
                'h-8 w-8 rounded-lg text-xs font-semibold transition-colors',
                page === currentPage
                  ? 'bg-[#C2410C] text-white shadow-sm'
                  : 'text-gray-600 hover:bg-gray-100',
              ]"
              @click="currentPage = page as number"
            >
              {{ page }}
            </button>
          </template>
          <button
            type="button"
            :disabled="currentPage >= totalPages"
            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="currentPage++"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ Catatan Kaki ═══════════ -->
    <div class="mt-5 flex items-center gap-2 text-xs text-gray-400">
      <Info class="h-3.5 w-3.5 shrink-0" />
      <span>* Menampilkan data contoh produk untuk simulasi antarmuka portal kasir & operasional restoran.</span>
    </div>
  </DashboardLayout>
</template>

