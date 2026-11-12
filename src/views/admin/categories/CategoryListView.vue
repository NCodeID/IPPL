<script setup lang="ts">
import { ref, computed, watch, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Plus,
  Search,
  X,
  Eye,
  Pencil,
  Trash2,
  Info,
  AlertTriangle,
  Utensils,
  Coffee,
  Soup,
  IceCreamCone,
  PlusCircle,
  Tag,
  Package,
} from 'lucide-vue-next'

// ─── Router untuk navigasi ke halaman detail ────────────────────────────────
const router = useRouter()

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Representasi satu entitas kategori menu ────────────────────────────────
// `code` digunakan sebagai kode unik untuk identifikasi visual (contoh: KAT-001)
// `productCount` menyimpan jumlah produk terkait untuk validasi hapus
interface Category {
  id: number
  code: string
  name: string
  description: string
  status: 'Aktif' | 'Nonaktif'
  productCount: number
}

// ─── Data form untuk membuat atau mengedit kategori ─────────────────────────
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
// Jika tidak ada yang cocok, mengembalikan ikon Tag sebagai fallback.
function getCategoryIcon(name: string): Component {
  const lower = name.toLowerCase()
  for (const [keyword, icon] of Object.entries(categoryIconMap)) {
    if (lower.includes(keyword)) return icon
  }
  return fallbackIcon
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar seluruh kategori (sumber data utama) ────────────────────────────
// Data dummy awal lengkap dengan `code` dan `productCount`.
// `productCount > 0` akan memblokir penghapusan di modal hapus.
const categories = ref<Category[]>([
  { id: 1, code: 'KAT-001', name: 'Makanan Utama', description: 'Hidangan utama seperti nasi goreng, ayam bakar, dan pasta', status: 'Aktif', productCount: 28 },
  { id: 2, code: 'KAT-002', name: 'Minuman', description: 'Aneka kopi, teh, jus buah segar, dan minuman dingin', status: 'Aktif', productCount: 15 },
  { id: 3, code: 'KAT-003', name: 'Makanan Pembuka', description: 'Hidangan ringan pembuka selera seperti sup dan salad', status: 'Aktif', productCount: 8 },
  { id: 4, code: 'KAT-004', name: 'Dessert', description: 'Hidangan penutup manis seperti es krim, puding, dan kue', status: 'Aktif', productCount: 12 },
  { id: 5, code: 'KAT-005', name: 'Tambahan', description: 'Menu pelengkap seperti nasi putih, sambal, dan kerupuk', status: 'Aktif', productCount: 0 },
])

// ─── Kata kunci pencarian untuk memfilter tabel ─────────────────────────────
const searchQuery = ref('')

// ─── Halaman aktif saat ini pada pagination ─────────────────────────────────
const currentPage = ref(1)

// ─── Jumlah baris per halaman pada tabel ────────────────────────────────────
const itemsPerPage = ref(5)

// ─── Visibilitas masing-masing modal (true = tampil) ────────────────────────
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

// ─── Kategori yang sedang dipilih untuk aksi detail / edit / hapus ──────────
const selectedCategory = ref<Category | null>(null)

// ─── Data form yang digunakan di modal tambah & edit ────────────────────────
const formData = ref<CategoryForm>({
  name: '',
  description: '',
  status: 'Aktif',
})

// ═══════════════════════════════════════════════════════════════════════════════
// COMPUTED (Data Turunan)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar kategori setelah difilter berdasarkan kata kunci pencarian ──────
const filteredCategories = computed<Category[]>(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) return categories.value
  return categories.value.filter((c) => c.name.toLowerCase().includes(q))
})

// ─── Jumlah total halaman berdasarkan hasil filter ──────────────────────────
const totalPages = computed<number>(() =>
  Math.max(1, Math.ceil(filteredCategories.value.length / itemsPerPage.value)),
)

// ─── Indeks awal baris yang ditampilkan di halaman aktif ────────────────────
const startIndex = computed<number>(() =>
  (currentPage.value - 1) * itemsPerPage.value,
)

// ─── Indeks akhir baris yang ditampilkan di halaman aktif ───────────────────
const endIndex = computed<number>(() =>
  Math.min(startIndex.value + itemsPerPage.value, filteredCategories.value.length),
)

// ─── Potongan data kategori yang tampil di halaman aktif (paginated) ────────
const paginatedCategories = computed<Category[]>(() =>
  filteredCategories.value.slice(startIndex.value, endIndex.value),
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

// ─── Cek apakah kategori terpilih boleh dihapus (produk terkait = 0) ───────
const canDelete = computed<boolean>(() =>
  selectedCategory.value !== null && selectedCategory.value.productCount === 0,
)

// ═══════════════════════════════════════════════════════════════════════════════
// WATCHER — Reset halaman saat pencarian berubah
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Setiap kali kata kunci pencarian berubah, kembalikan ke halaman 1 ──────
watch(searchQuery, () => {
  currentPage.value = 1
})

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI CRUD
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Mengambil daftar kategori dari API ─────────────────────────────────────
// [API-READY] Akan memanggil GET /api/admin/categories dan mengisi
// `categories` dengan response data dari server.
async function fetchCategories(): Promise<void> {
  // [API-READY] const { data } = await api.get('/admin/categories')
  // categories.value = data
}

// ─── Membuat kode kategori baru secara otomatis ─────────────────────────────
// Mengambil angka tertinggi dari kode yang ada, lalu menambahkan 1.
function generateCode(): string {
  const maxNum = categories.value.reduce((max, c) => {
    const num = parseInt(c.code.replace('KAT-', ''), 10)
    return isNaN(num) ? max : Math.max(max, num)
  }, 0)
  return `KAT-${String(maxNum + 1).padStart(3, '0')}`
}

// ─── Membuat kategori baru dan menambahkannya ke daftar ─────────────────────
// [API-READY] Akan memanggil POST /api/admin/categories dengan formData,
// lalu menambahkan response ke array categories.
async function createCategory(): Promise<void> {
  if (!formData.value.name.trim()) return

  // [API-READY] const { data } = await api.post('/admin/categories', formData.value)
  // categories.value.push(data)

  const newId =
    categories.value.length > 0
      ? Math.max(...categories.value.map((c) => c.id)) + 1
      : 1

  categories.value.push({
    id: newId,
    code: generateCode(),
    name: formData.value.name.trim(),
    description: formData.value.description.trim(),
    status: formData.value.status,
    productCount: 0,
  })

  closeAllModals()
}

// ─── Memperbarui data kategori yang sudah ada ───────────────────────────────
// [API-READY] Akan memanggil PUT /api/admin/categories/{id} dengan formData,
// lalu memperbarui item di array categories.
async function updateCategory(): Promise<void> {
  if (!selectedCategory.value || !formData.value.name.trim()) return

  // [API-READY] const { data } = await api.put(`/admin/categories/${selectedCategory.value.id}`, formData.value)

  const index = categories.value.findIndex((c) => c.id === selectedCategory.value!.id)
  if (index !== -1) {
    categories.value[index] = {
      id: selectedCategory.value.id,
      code: selectedCategory.value.code,
      name: formData.value.name.trim(),
      description: formData.value.description.trim(),
      status: formData.value.status,
      productCount: selectedCategory.value.productCount,
    }
  }

  closeAllModals()
}

// ─── Menghapus kategori dari daftar berdasarkan ID yang dipilih ─────────────
// [API-READY] Akan memanggil DELETE /api/admin/categories/{id},
// lalu menghapus item dari array categories.
// Hanya bisa dijalankan jika productCount === 0.
async function deleteCategory(): Promise<void> {
  if (!selectedCategory.value || !canDelete.value) return

  // [API-READY] await api.delete(`/admin/categories/${selectedCategory.value.id}`)

  categories.value = categories.value.filter((c) => c.id !== selectedCategory.value!.id)

  // Jika halaman aktif menjadi kosong setelah hapus, mundur 1 halaman
  if (currentPage.value > totalPages.value) {
    currentPage.value = Math.max(1, totalPages.value)
  }

  closeAllModals()
}

// ═══════════════════════════════════════════════════════════════════════════════
// MODAL HANDLERS — Fungsi buka/tutup modal
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Membuka modal tambah kategori baru dengan form kosong ──────────────────
function openCreateModal(): void {
  formData.value = { name: '', description: '', status: 'Aktif' }
  showCreateModal.value = true
}

// ─── Membuka modal edit dengan data kategori yang dipilih ───────────────────
function openEditModal(category: Category): void {
  selectedCategory.value = category
  formData.value = {
    name: category.name,
    description: category.description,
    status: category.status,
  }
  showEditModal.value = true
}

// ─── Membuka modal detail untuk melihat informasi lengkap kategori ──────────
// Sekarang mengarahkan ke halaman detail terpisah alih-alih modal
function openDetailModal(category: Category): void {
  router.push(`/admin/categories/${category.id}`)
}

// ─── Membuka modal konfirmasi hapus kategori ────────────────────────────────
function openDeleteModal(category: Category): void {
  selectedCategory.value = category
  showDeleteModal.value = true
}

// ─── Menutup semua modal dan mereset state terpilih ─────────────────────────
function closeAllModals(): void {
  showCreateModal.value = false
  showEditModal.value = false
  showDeleteModal.value = false
  selectedCategory.value = null
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memuat data kategori saat komponen pertama kali dimuat ─────────────────
onMounted(() => {
  fetchCategories()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Page Header ═══════════ -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Manajemen Kategori</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola kategori menu dan kelompok hidangan restoran.</p>
      </div>
      <button
        type="button"
        class="flex shrink-0 items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#B45309]"
        @click="openCreateModal"
      >
        <Plus class="h-4 w-4" />
        Tambah Kategori
      </button>
    </div>

    <!-- ═══════════ Search + Table Card ═══════════ -->
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white">
      <!-- Search Bar -->
      <div class="border-b border-gray-100 px-5 py-4">
        <div class="relative max-w-md">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama kategori..."
            class="w-full rounded-lg border border-gray-200 py-2.5 pl-10 pr-10 text-sm text-gray-700 placeholder-gray-400 outline-none transition-colors focus:border-[#C2410C] focus:ring-1 focus:ring-[#C2410C]/30"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            @click="searchQuery = ''"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full min-w-[650px]">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="w-16 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">No.</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nama Kategori</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Deskripsi</th>
              <th class="w-24 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Status</th>
              <th class="w-32 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedCategories.length === 0">
              <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">
                Tidak ada kategori yang ditemukan.
              </td>
            </tr>
            <tr
              v-for="(category, index) in paginatedCategories"
              :key="category.id"
              class="border-b border-gray-50 transition-colors hover:bg-gray-50/50"
            >
              <td class="px-5 py-4 text-sm text-gray-500">{{ startIndex + index + 1 }}</td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-50">
                    <component :is="getCategoryIcon(category.name)" class="h-4 w-4 text-[#C2410C]" />
                  </div>
                  <span class="text-sm font-semibold text-gray-800">{{ category.name }}</span>
                </div>
              </td>
              <td class="max-w-xs px-5 py-4 text-sm leading-relaxed text-gray-500">{{ category.description }}</td>
              <td class="px-5 py-4 text-center">
                <span
                  :class="[
                    'inline-block rounded-full px-3 py-0.5 text-xs font-medium',
                    category.status === 'Aktif' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700',
                  ]"
                >{{ category.status }}</span>
              </td>
              <td class="px-5 py-4">
                <div class="flex items-center justify-center gap-1">
                  <button type="button" title="Detail" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600" @click="openDetailModal(category)"><Eye class="h-4 w-4" /></button>
                  <button type="button" title="Edit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600" @click="openEditModal(category)"><Pencil class="h-4 w-4" /></button>
                  <button type="button" title="Hapus" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" @click="openDeleteModal(category)"><Trash2 class="h-4 w-4" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 sm:flex-row">
        <p class="text-sm text-gray-500">
          Menampilkan {{ filteredCategories.length > 0 ? startIndex + 1 : 0 }} -
          {{ endIndex }} dari {{ filteredCategories.length }} kategori
        </p>
        <div class="flex items-center gap-1">
          <button type="button" :disabled="currentPage <= 1" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40" @click="currentPage--">Sebelumnya</button>
          <template v-for="(page, idx) in visiblePages" :key="'p-' + idx">
            <!-- Elipsis (tidak bisa diklik) -->
            <span v-if="page === '...'" class="px-1 text-xs text-gray-400">…</span>
            <!-- Tombol nomor halaman -->
            <button
              v-else
              type="button"
              :class="['h-8 w-8 rounded-lg text-xs font-semibold transition-colors', page === currentPage ? 'bg-[#C2410C] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100']"
              @click="currentPage = page as number"
            >{{ page }}</button>
          </template>
          <button type="button" :disabled="currentPage >= totalPages" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40" @click="currentPage++">Berikutnya</button>
        </div>
      </div>
    </div>

    <!-- ═══════════ Catatan Kaki ═══════════ -->
    <div class="mt-5 flex items-center gap-2 text-xs text-gray-400">
      <Info class="h-3.5 w-3.5 shrink-0" />
      <span>* Menampilkan data contoh kategori produk untuk simulasi antarmuka.</span>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Tambah Kategori Baru (Desain Figma Gambar 1)                   -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeAllModals"></div>
        <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-xl" @click.stop>
          <!-- Header dengan ikon brand dan judul -->
          <div class="flex items-start justify-between px-6 pt-6">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                <Tag class="h-5 w-5 text-[#C2410C]" />
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900">Tambah Kategori Baru</h3>
                <p class="text-sm text-gray-500">Buat pengelompokan menu restoran baru</p>
              </div>
            </div>
            <button type="button" class="mt-1 text-gray-400 hover:text-gray-600" @click="closeAllModals">
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Form Body -->
          <form @submit.prevent="createCategory" class="px-6 pb-6 pt-5">
            <div class="space-y-4">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Nama Kategori <span class="text-red-500">*</span></label>
                <input
                  v-model="formData.name"
                  type="text"
                  required
                  placeholder="Contoh : Makanan Utama"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 outline-none transition-colors focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Status Kategori</label>
                <select
                  v-model="formData.status"
                  class="w-full appearance-none rounded-lg border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition-colors focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                >
                  <option value="Aktif">Aktif (Ditampilkan di POS)</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea
                  v-model="formData.description"
                  rows="3"
                  placeholder="Contoh : Aneka hidangan pokok dan olahan nasi, ayam, serta daging utama restoran."
                  class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 outline-none transition-colors focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                ></textarea>
              </div>
            </div>

            <!-- Footer Buttons -->
            <div class="mt-6 flex items-center justify-end gap-3">
              <button
                type="button"
                class="rounded-lg px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100"
                @click="closeAllModals"
              >
                Batal
              </button>
              <button
                type="submit"
                class="rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#B45309]"
              >
                Simpan Kategori
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Edit Kategori (Desain Figma Gambar 3)                          -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeAllModals"></div>
        <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-xl" @click.stop>
          <!-- Header dengan ikon brand dan judul -->
          <div class="flex items-start justify-between px-6 pt-6">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                <Tag class="h-5 w-5 text-[#C2410C]" />
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900">Edit Kategori</h3>
                <p class="text-sm text-gray-500">Perbarui informasi kategori produk restoran</p>
              </div>
            </div>
            <button type="button" class="mt-1 text-gray-400 hover:text-gray-600" @click="closeAllModals">
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Form Body (pre-filled) -->
          <form @submit.prevent="updateCategory" class="px-6 pb-6 pt-5">
            <div class="space-y-4">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Nama Kategori <span class="text-red-500">*</span></label>
                <input
                  v-model="formData.name"
                  type="text"
                  required
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition-colors focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Status Kategori</label>
                <select
                  v-model="formData.status"
                  class="w-full appearance-none rounded-lg border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition-colors focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                >
                  <option value="Aktif">Aktif (Ditampilkan di POS)</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea
                  v-model="formData.description"
                  rows="3"
                  class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition-colors focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                ></textarea>
              </div>
            </div>

            <!-- Footer Buttons -->
            <div class="mt-6 flex items-center justify-end gap-3">
              <button
                type="button"
                class="rounded-lg px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100"
                @click="closeAllModals"
              >
                Batal
              </button>
              <button
                type="submit"
                class="rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#B45309]"
              >
                Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Konfirmasi Hapus Kategori (Desain Figma Gambar 2)              -->
    <!-- Validasi cerdas: tombol hapus dinonaktifkan jika productCount > 0     -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showDeleteModal && selectedCategory" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeAllModals"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-xl" @click.stop>
          <!-- Tombol tutup pojok kanan atas -->
          <button
            type="button"
            class="absolute right-4 top-4 text-gray-400 hover:text-gray-600"
            @click="closeAllModals"
          >
            <X class="h-5 w-5" />
          </button>

          <div class="px-6 pb-6 pt-6">
            <!-- Ikon tempat sampah merah muda di tengah atas -->
            <div class="mb-4 flex justify-center">
              <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50">
                <Trash2 class="h-7 w-7 text-red-500" />
              </div>
            </div>

            <!-- Judul dan subjudul -->
            <h3 class="text-center text-lg font-bold text-gray-900">Hapus Kategori?</h3>
            <p class="mt-1 text-center text-sm text-gray-500">
              Apakah Anda yakin ingin menghapus kategori
              <span class="font-semibold text-gray-800">{{ selectedCategory.name }}</span>?
            </p>

            <!-- Kartu ringkasan detail kategori -->
            <div class="mt-5 rounded-lg border border-gray-200 px-4 py-3">
              <div class="flex items-center justify-between border-b border-gray-100 py-2">
                <span class="text-sm text-gray-500">Kode Kategori</span>
                <span class="text-sm font-bold text-gray-800">{{ selectedCategory.code }}</span>
              </div>
              <div class="flex items-center justify-between border-b border-gray-100 py-2">
                <span class="text-sm text-gray-500">Nama Kategori</span>
                <span class="text-sm font-bold text-gray-800">{{ selectedCategory.name }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-500">Jumlah Produk Terkait</span>
                <span class="flex items-center gap-1.5 text-sm font-bold" :class="selectedCategory.productCount > 0 ? 'text-[#C2410C]' : 'text-gray-800'">
                  <Package class="h-3.5 w-3.5" />
                  {{ selectedCategory.productCount }} Produk
                </span>
              </div>
            </div>

            <!-- Peringatan kuning jika masih ada produk terkait -->
            <div
              v-if="selectedCategory.productCount > 0"
              class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3"
            >
              <div class="flex items-start gap-2.5">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                <div>
                  <p class="text-sm font-semibold text-amber-800">
                    Kategori ini masih memiliki {{ selectedCategory.productCount }} produk terkait.
                  </p>
                  <p class="mt-0.5 text-sm leading-relaxed text-amber-700">
                    Periksa dan pindahkan seluruh produk ke kategori lain sebelum menghapus kategori ini untuk mencegah inkonsistensi menu POS.
                  </p>
                </div>
              </div>
            </div>

            <!-- Tombol aksi -->
            <div class="mt-5 flex items-center gap-3">
              <button
                type="button"
                class="flex-1 rounded-lg border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                @click="closeAllModals"
              >
                Batal
              </button>
              <button
                type="button"
                :disabled="!canDelete"
                :class="[
                  'flex flex-1 items-center justify-center gap-2 rounded-lg border py-2.5 text-sm font-semibold transition-colors',
                  canDelete
                    ? 'border-red-600 bg-red-600 text-white hover:bg-red-700'
                    : 'cursor-not-allowed border-red-200 bg-white text-red-300',
                ]"
                @click="deleteCategory"
              >
                <Trash2 class="h-4 w-4" />
                Hapus Kategori
              </button>
            </div>

            <!-- Teks bantuan jika tombol hapus dinonaktifkan -->
            <p
              v-if="!canDelete"
              class="mt-2 text-center text-xs italic text-gray-400"
            >
              (Tombol hapus dinonaktifkan: Pindahkan {{ selectedCategory.productCount }} produk terlebih dahulu)
            </p>
          </div>
        </div>
      </div>
    </Teleport>
  </DashboardLayout>
</template>
