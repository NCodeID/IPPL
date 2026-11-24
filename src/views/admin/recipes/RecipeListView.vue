<script setup lang="ts">
// ===========================================================================
// Halaman Manajemen Resep — Daftar, Tambah, Edit, Hapus resep menu restoran
// ===========================================================================

import { ref, computed, watch, markRaw, type Component } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  BookOpen,
  UtensilsCrossed,
  Layers,
  CheckCircle2,
  Plus,
  Search,
  X,
  RotateCcw,
  Eye,
  Pencil,
  Trash2,
  Check,
  Info,
  AlertTriangle,
  Lock,
  Coffee,
  IceCreamCone,
  FileText,
  ChefHat,
  Flame,
  CookingPot,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

// ---------------------------------------------------------------------------
// Tipe data
// ---------------------------------------------------------------------------

/** Bahan baku dalam satu resep */
interface RecipeIngredient {
  rawMaterialId: number
  quantity: number
  unit: string
}

/** Bahan mentah (master data) */
interface RawMaterial {
  id: number
  code: string
  name: string
  unit: string
  status: 'Tersedia' | 'Habis'
}

/** Produk menu (master data) */
interface MenuProduct {
  id: number
  name: string
  category: string
  status: 'Tersedia' | 'Habis'
}

/** Item resep lengkap */
interface RecipeItem {
  id: number
  code: string
  name: string
  menuId: number
  menuName: string
  menuCategory: string
  servingSize: string
  notes: string
  status: 'Aktif' | 'Nonaktif'
  ingredients: RecipeIngredient[]
  fallbackIcon: Component
}

/** Form data untuk tambah / edit resep */
interface RecipeForm {
  name: string
  menuId: number | null
  servingSize: string
  notes: string
  ingredients: RecipeIngredient[]
}

// ---------------------------------------------------------------------------
// Router
// ---------------------------------------------------------------------------
const router = useRouter()

// ---------------------------------------------------------------------------
// Master Data — Bahan Baku (minimal 15 item)
// ---------------------------------------------------------------------------
const rawMaterials = ref<RawMaterial[]>([
  { id: 1, code: 'BB-01', name: 'Daging Ayam Fillet', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 2, code: 'BB-02', name: 'Madu Murni Alami', unit: 'Mililiter (ml)', status: 'Tersedia' },
  { id: 3, code: 'BB-03', name: 'Bumbu Rempah Marinasi', unit: 'Sendok Makan (sdm)', status: 'Tersedia' },
  { id: 4, code: 'BB-04', name: 'Kecap Manis Premium', unit: 'Mililiter (ml)', status: 'Tersedia' },
  { id: 5, code: 'BB-05', name: 'Sambal Terasi Bajak', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 6, code: 'BB-06', name: 'Beras Pulen Premium', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 7, code: 'BB-07', name: 'Minyak Goreng', unit: 'Mililiter (ml)', status: 'Tersedia' },
  { id: 8, code: 'BB-08', name: 'Bawang Merah', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 9, code: 'BB-09', name: 'Bawang Putih', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 10, code: 'BB-10', name: 'Cabai Merah', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 11, code: 'BB-11', name: 'Gula Pasir', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 12, code: 'BB-12', name: 'Garam Halus', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 13, code: 'BB-13', name: 'Kopi Arabica', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 14, code: 'BB-14', name: 'Susu Segar', unit: 'Mililiter (ml)', status: 'Tersedia' },
  { id: 15, code: 'BB-15', name: 'Gula Aren Cair', unit: 'Mililiter (ml)', status: 'Tersedia' },
  { id: 16, code: 'BB-16', name: 'Alpukat Segar', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 17, code: 'BB-17', name: 'Cokelat Bubuk', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 18, code: 'BB-18', name: 'Kacang Tanah', unit: 'Gram (gr)', status: 'Tersedia' },
  { id: 19, code: 'BB-19', name: 'Santan Kelapa', unit: 'Mililiter (ml)', status: 'Tersedia' },
])

// ---------------------------------------------------------------------------
// Master Data — Produk Menu (12 item)
// ---------------------------------------------------------------------------
const menuProducts = ref<MenuProduct[]>([
  { id: 1, name: 'Ayam Bakar Madu Spesial', category: 'Makanan Utama', status: 'Tersedia' },
  { id: 2, name: 'Nasi Goreng Kampung', category: 'Makanan Utama', status: 'Tersedia' },
  { id: 3, name: 'Es Kopi Susu Aren', category: 'Minuman', status: 'Tersedia' },
  { id: 4, name: 'Sate Ayam Madura (10 Tusuk)', category: 'Makanan Utama', status: 'Tersedia' },
  { id: 5, name: 'Jus Alpukat Kocok', category: 'Minuman', status: 'Tersedia' },
  { id: 6, name: 'Sambal Terasi Ekstra', category: 'Sambal & Pelengkap', status: 'Tersedia' },
  { id: 7, name: 'Mie Goreng Spesial', category: 'Makanan Utama', status: 'Tersedia' },
  { id: 8, name: 'Es Teh Manis', category: 'Minuman', status: 'Tersedia' },
  { id: 9, name: 'Kentang Goreng Crispy', category: 'Makanan Ringan', status: 'Tersedia' },
  { id: 10, name: 'Pisang Goreng Keju', category: 'Makanan Ringan', status: 'Habis' },
  { id: 11, name: 'Es Cokelat Blended', category: 'Minuman', status: 'Tersedia' },
  { id: 12, name: 'Kerupuk Udang', category: 'Sambal & Pelengkap', status: 'Tersedia' },
])

// ---------------------------------------------------------------------------
// Data Dummy Resep (6 item)
// ---------------------------------------------------------------------------
const recipes = ref<RecipeItem[]>([
  {
    id: 1,
    code: 'RSP-001',
    name: 'Resep Ayam Bakar Madu',
    menuId: 1,
    menuName: 'Ayam Bakar Madu Spesial',
    menuCategory: 'Makanan Utama',
    servingSize: '1 Porsi Standar',
    notes: 'Bakar dengan api sedang hingga kecoklatan. Olesi madu secara merata.',
    status: 'Aktif',
    ingredients: [
      { rawMaterialId: 1, quantity: 250, unit: 'Gram (gr)' },
      { rawMaterialId: 2, quantity: 30, unit: 'Mililiter (ml)' },
      { rawMaterialId: 3, quantity: 2, unit: 'Sendok Makan (sdm)' },
      { rawMaterialId: 4, quantity: 15, unit: 'Mililiter (ml)' },
      { rawMaterialId: 12, quantity: 5, unit: 'Gram (gr)' },
    ],
    fallbackIcon: markRaw(Flame),
  },
  {
    id: 2,
    code: 'RSP-002',
    name: 'Resep Nasi Goreng Kampung',
    menuId: 2,
    menuName: 'Nasi Goreng Kampung',
    menuCategory: 'Makanan Utama',
    servingSize: '1 Porsi Standar',
    notes: 'Gunakan nasi sisa semalam agar tekstur lebih pulen dan tidak lembek.',
    status: 'Aktif',
    ingredients: [
      { rawMaterialId: 6, quantity: 200, unit: 'Gram (gr)' },
      { rawMaterialId: 7, quantity: 30, unit: 'Mililiter (ml)' },
      { rawMaterialId: 8, quantity: 20, unit: 'Gram (gr)' },
      { rawMaterialId: 9, quantity: 10, unit: 'Gram (gr)' },
      { rawMaterialId: 4, quantity: 10, unit: 'Mililiter (ml)' },
      { rawMaterialId: 12, quantity: 3, unit: 'Gram (gr)' },
    ],
    fallbackIcon: markRaw(CookingPot),
  },
  {
    id: 3,
    code: 'RSP-003',
    name: 'Resep Es Kopi Susu Aren',
    menuId: 3,
    menuName: 'Es Kopi Susu Aren',
    menuCategory: 'Minuman',
    servingSize: '1 Gelas (350 ml)',
    notes: 'Seduh kopi dengan air panas 92°C. Dinginkan sebelum dicampur susu.',
    status: 'Aktif',
    ingredients: [
      { rawMaterialId: 13, quantity: 18, unit: 'Gram (gr)' },
      { rawMaterialId: 14, quantity: 150, unit: 'Mililiter (ml)' },
      { rawMaterialId: 15, quantity: 25, unit: 'Mililiter (ml)' },
      { rawMaterialId: 11, quantity: 5, unit: 'Gram (gr)' },
    ],
    fallbackIcon: markRaw(Coffee),
  },
  {
    id: 4,
    code: 'RSP-004',
    name: 'Resep Sate Ayam Madura',
    menuId: 4,
    menuName: 'Sate Ayam Madura (10 Tusuk)',
    menuCategory: 'Makanan Utama',
    servingSize: '1 Porsi (10 Tusuk)',
    notes: 'Rendam ayam dalam bumbu minimal 1 jam. Bakar di atas arang.',
    status: 'Aktif',
    ingredients: [
      { rawMaterialId: 1, quantity: 300, unit: 'Gram (gr)' },
      { rawMaterialId: 18, quantity: 50, unit: 'Gram (gr)' },
      { rawMaterialId: 4, quantity: 20, unit: 'Mililiter (ml)' },
      { rawMaterialId: 8, quantity: 15, unit: 'Gram (gr)' },
      { rawMaterialId: 9, quantity: 10, unit: 'Gram (gr)' },
      { rawMaterialId: 11, quantity: 10, unit: 'Gram (gr)' },
      { rawMaterialId: 7, quantity: 15, unit: 'Mililiter (ml)' },
    ],
    fallbackIcon: markRaw(Flame),
  },
  {
    id: 5,
    code: 'RSP-005',
    name: 'Resep Jus Alpukat Kocok',
    menuId: 5,
    menuName: 'Jus Alpukat Kocok',
    menuCategory: 'Minuman',
    servingSize: '1 Gelas (400 ml)',
    notes: 'Blender alpukat hingga halus. Tambahkan es batu secukupnya.',
    status: 'Aktif',
    ingredients: [
      { rawMaterialId: 16, quantity: 150, unit: 'Gram (gr)' },
      { rawMaterialId: 14, quantity: 100, unit: 'Mililiter (ml)' },
      { rawMaterialId: 17, quantity: 10, unit: 'Gram (gr)' },
    ],
    fallbackIcon: markRaw(IceCreamCone),
  },
  {
    id: 6,
    code: 'RSP-006',
    name: 'Resep Sambal Terasi Bajak',
    menuId: 6,
    menuName: 'Sambal Terasi Ekstra',
    menuCategory: 'Sambal & Pelengkap',
    servingSize: '1 Porsi Kecil (50 gr)',
    notes: 'Ulek kasar agar tekstur sambal tetap terasa. Goreng sebentar.',
    status: 'Aktif',
    ingredients: [
      { rawMaterialId: 10, quantity: 50, unit: 'Gram (gr)' },
      { rawMaterialId: 5, quantity: 15, unit: 'Gram (gr)' },
      { rawMaterialId: 8, quantity: 10, unit: 'Gram (gr)' },
      { rawMaterialId: 12, quantity: 3, unit: 'Gram (gr)' },
    ],
    fallbackIcon: markRaw(Flame),
  },
])

// ---------------------------------------------------------------------------
// State halaman utama
// ---------------------------------------------------------------------------
const searchQuery = ref('')
const selectedCategory = ref('')
const currentPage = ref(1)
const itemsPerPage = 6

// ---------------------------------------------------------------------------
// Kategori unik untuk filter dropdown
// ---------------------------------------------------------------------------
const uniqueCategories = computed<string[]>(() => {
  const cats = new Set<string>()
  for (const r of recipes.value) {
    cats.add(r.menuCategory)
  }
  return Array.from(cats).sort()
})

// ---------------------------------------------------------------------------
// Data yang telah difilter
// ---------------------------------------------------------------------------
const filteredRecipes = computed<RecipeItem[]>(() => {
  let result = recipes.value

  // Filter pencarian
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    result = result.filter(
      (r) =>
        r.name.toLowerCase().includes(q) ||
        r.menuName.toLowerCase().includes(q) ||
        r.code.toLowerCase().includes(q),
    )
  }

  // Filter kategori
  if (selectedCategory.value) {
    result = result.filter((r) => r.menuCategory === selectedCategory.value)
  }

  return result
})

// ---------------------------------------------------------------------------
// Paginasi
// ---------------------------------------------------------------------------
const totalPages = computed(() => Math.max(1, Math.ceil(filteredRecipes.value.length / itemsPerPage)))

const paginatedRecipes = computed<RecipeItem[]>(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredRecipes.value.slice(start, start + itemsPerPage)
})

/** Indeks awal untuk nomor urut tabel */
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage + 1)

/** Range tampilan "Menampilkan X - Y dari Z" */
const displayRange = computed(() => {
  const total = filteredRecipes.value.length
  if (total === 0) return 'Menampilkan 0 resep'
  const start = startIndex.value
  const end = Math.min(start + itemsPerPage - 1, total)
  return `Menampilkan ${start} - ${end} dari ${total} resep`
})

/** Sliding window pagination — nomor halaman yang terlihat */
const paginationPages = computed<(number | string)[]>(() => {
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

// ---------------------------------------------------------------------------
// Watcher — Reset halaman saat pencarian atau filter kategori resep berubah
// ---------------------------------------------------------------------------
// Mengembalikan nomor halaman pagination ke halaman 1 setiap kali query pencarian
// atau kategori resep diubah, mencegah tampilan tabel kosong jika terjebak di halaman sebelumnya.
watch([searchQuery, selectedCategory], () => {
  currentPage.value = 1
})

function goToPage(page: number | string): void {
  const pageNum = typeof page === 'string' ? parseInt(page, 10) : page
  if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages.value) {
    currentPage.value = pageNum
  }
}

/**
 * Mereset semua filter pencarian dan kategori resep ke kondisi awal.
 */
function resetFilters(): void {
  searchQuery.value = ''
  selectedCategory.value = ''
}

// ---------------------------------------------------------------------------
// Statistik kartu ringkasan
// ---------------------------------------------------------------------------
const statCards = computed(() => [
  {
    label: 'Total Resep',
    value: 42,
    icon: markRaw(BookOpen),
    color: 'text-orange-600',
    bg: 'bg-orange-50',
  },
  {
    label: 'Menu Bersiap',
    value: 39,
    icon: markRaw(UtensilsCrossed),
    color: 'text-emerald-600',
    bg: 'bg-emerald-50',
  },
  {
    label: 'Total Kategori',
    value: 6,
    icon: markRaw(Layers),
    color: 'text-blue-600',
    bg: 'bg-blue-50',
  },
  {
    label: 'Porsi Standar',
    value: 42,
    icon: markRaw(CheckCircle2),
    color: 'text-purple-600',
    bg: 'bg-purple-50',
  },
])

// ---------------------------------------------------------------------------
// Modal state
// ---------------------------------------------------------------------------
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

/** Resep yang sedang diedit / dihapus */
const selectedRecipe = ref<RecipeItem | null>(null)

// ---------------------------------------------------------------------------
// Form helpers
// ---------------------------------------------------------------------------

/** Buat form kosong untuk modal tambah */
function createEmptyForm(): RecipeForm {
  return {
    name: '',
    menuId: null,
    servingSize: '',
    notes: '',
    ingredients: [],
  }
}

const addForm = ref<RecipeForm>(createEmptyForm())
const editForm = ref<RecipeForm>(createEmptyForm())

// Kategori menu otomatis — terisi saat memilih menu terkait
const addMenuCategory = ref('')
const editMenuCategory = ref('')

// ---------------------------------------------------------------------------
// Helpers — cari nama bahan baku dari ID
// ---------------------------------------------------------------------------
function getRawMaterialName(id: number): string {
  const found = rawMaterials.value.find((rm) => rm.id === id)
  return found ? found.name : '-'
}

function getRawMaterialUnit(id: number): string {
  const found = rawMaterials.value.find((rm) => rm.id === id)
  return found ? found.unit : '-'
}

// ---------------------------------------------------------------------------
// Tambah baris bahan baku (BOM) ke form
// ---------------------------------------------------------------------------
function addIngredientRow(form: RecipeForm) {
  form.ingredients.push({ rawMaterialId: 0, quantity: 0, unit: '' })
}

function removeIngredientRow(form: RecipeForm, index: number) {
  form.ingredients.splice(index, 1)
}

/** Saat dropdown bahan baku berubah, isi unit otomatis */
function onIngredientChange(ingredient: RecipeIngredient) {
  ingredient.unit = getRawMaterialUnit(ingredient.rawMaterialId)
}

// ---------------------------------------------------------------------------
// Handler saat menu terkait berubah — isi kategori otomatis
// ---------------------------------------------------------------------------
function onAddMenuChange() {
  if (addForm.value.menuId) {
    const menu = menuProducts.value.find((m) => m.id === addForm.value.menuId)
    addMenuCategory.value = menu ? menu.category : ''
  } else {
    addMenuCategory.value = ''
  }
}

function onEditMenuChange() {
  if (editForm.value.menuId) {
    const menu = menuProducts.value.find((m) => m.id === editForm.value.menuId)
    editMenuCategory.value = menu ? menu.category : ''
  } else {
    editMenuCategory.value = ''
  }
}

// ---------------------------------------------------------------------------
// Buka / tutup modal
// ---------------------------------------------------------------------------
function openAddModal() {
  addForm.value = createEmptyForm()
  addMenuCategory.value = ''
  showAddModal.value = true
}

function closeAddModal() {
  showAddModal.value = false
}

function openEditModal(recipe: RecipeItem) {
  selectedRecipe.value = recipe
  editForm.value = {
    name: recipe.name,
    menuId: recipe.menuId,
    servingSize: recipe.servingSize,
    notes: recipe.notes,
    ingredients: recipe.ingredients.map((ing) => ({ ...ing })),
  }
  editMenuCategory.value = recipe.menuCategory
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  selectedRecipe.value = null
}

function openDeleteModal(recipe: RecipeItem) {
  selectedRecipe.value = recipe
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
  selectedRecipe.value = null
}

// ---------------------------------------------------------------------------
// Navigasi ke halaman detail resep
// ---------------------------------------------------------------------------
function viewRecipeDetail(recipe: RecipeItem) {
  void router.push(`/admin/recipes/${recipe.id}`)
}

// ---------------------------------------------------------------------------
// CRUD — [API-READY]
// ---------------------------------------------------------------------------

/** [API-READY] Ambil daftar resep dari server */
function fetchRecipes(): void {
  // TODO: Panggil API GET /api/recipes dan perbarui state recipes
  console.log('[fetchRecipes] Mengambil data resep dari server...')
}

/** [API-READY] Simpan resep baru ke server */
function createRecipe(): void {
  // TODO: Panggil API POST /api/recipes dengan payload addForm
  console.log('[createRecipe] Menyimpan resep baru:', addForm.value)

  // Simulasi penyimpanan lokal
  const menuId = addForm.value.menuId
  const menu = menuId ? menuProducts.value.find((m) => m.id === menuId) : undefined
  const newId = recipes.value.length > 0
    ? Math.max(...recipes.value.map((r) => r.id)) + 1
    : 1

  const newRecipe: RecipeItem = {
    id: newId,
    code: `RSP-${String(newId).padStart(3, '0')}`,
    name: addForm.value.name,
    menuId: menuId ?? 0,
    menuName: menu ? menu.name : '-',
    menuCategory: menu ? menu.category : '-',
    servingSize: addForm.value.servingSize,
    notes: addForm.value.notes,
    status: 'Aktif',
    ingredients: addForm.value.ingredients.map((ing) => ({ ...ing })),
    fallbackIcon: markRaw(FileText),
  }

  recipes.value.push(newRecipe)
  closeAddModal()
}

/** [API-READY] Perbarui resep yang sudah ada di server */
function updateRecipe(): void {
  // TODO: Panggil API PUT /api/recipes/:id dengan payload editForm
  if (!selectedRecipe.value) return
  console.log('[updateRecipe] Memperbarui resep:', selectedRecipe.value.id, editForm.value)

  const idx = recipes.value.findIndex((r) => r.id === selectedRecipe.value?.id)
  if (idx === -1) return

  const target = recipes.value[idx]
  if (!target) return

  const menuId = editForm.value.menuId
  const menu = menuId ? menuProducts.value.find((m) => m.id === menuId) : undefined

  target.name = editForm.value.name
  target.menuId = menuId ?? 0
  target.menuName = menu ? menu.name : '-'
  target.menuCategory = menu ? menu.category : '-'
  target.servingSize = editForm.value.servingSize
  target.notes = editForm.value.notes
  target.ingredients = editForm.value.ingredients.map((ing) => ({ ...ing }))

  closeEditModal()
}

/** [API-READY] Hapus resep dari server */
function deleteRecipe(): void {
  // TODO: Panggil API DELETE /api/recipes/:id
  if (!selectedRecipe.value) return
  console.log('[deleteRecipe] Menghapus resep:', selectedRecipe.value.id)

  recipes.value = recipes.value.filter((r) => r.id !== selectedRecipe.value?.id)
  closeDeleteModal()
}

// Panggil fetch saat halaman dimuat
fetchRecipes()
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-[#F8FAFC] p-6">
      <!-- ================================================================ -->
      <!-- Header Halaman                                                    -->
      <!-- ================================================================ -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Manajemen Resep</h1>
          <p class="mt-1 text-sm text-gray-500">
            Kelola komposisi resep dan bahan baku untuk seluruh menu restoran Anda.
          </p>
        </div>
        <button
          class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
          @click="openAddModal"
        >
          <Plus :size="18" />
          Tambah Resep
        </button>
      </div>

      <!-- ================================================================ -->
      <!-- Kartu Statistik                                                   -->
      <!-- ================================================================ -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="card in statCards"
          :key="card.label"
          class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
        >
          <div :class="[card.bg, 'flex h-12 w-12 items-center justify-center rounded-xl']">
            <component :is="card.icon" :size="22" :class="card.color" />
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ card.value }}</p>
            <p class="text-sm text-gray-500">{{ card.label }}</p>
          </div>
        </div>
      </div>

      <!-- ================================================================ -->
      <!-- Bar Filter & Pencarian                                            -->
      <!-- ================================================================ -->
      <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <!-- Input Pencarian (Sisi Kiri / flex-1) -->
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama resep atau nama menu..."
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
            v-model="selectedCategory"
            class="h-10 w-full sm:w-48 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30 cursor-pointer"
          >
            <option value="">Semua Kategori</option>
            <option v-for="cat in uniqueCategories" :key="cat" :value="cat">{{ cat }}</option>
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

      <!-- ================================================================ -->
      <!-- Tabel Resep                                                       -->
      <!-- ================================================================ -->
      <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50/80">
                <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  No.
                </th>
                <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Nama Resep
                </th>
                <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Menu Terkait
                </th>
                <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Jumlah Bahan
                </th>
                <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Takaran/Porsi
                </th>
                <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(recipe, idx) in paginatedRecipes"
                :key="recipe.id"
                class="border-b border-gray-50 transition hover:bg-orange-50/30"
              >
                <!-- Nomor urut -->
                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">
                  {{ startIndex + idx }}
                </td>

                <!-- Nama Resep dengan ikon -->
                <td class="whitespace-nowrap px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50">
                      <component :is="recipe.fallbackIcon" :size="18" class="text-orange-600" />
                    </div>
                    <span class="font-semibold text-gray-900">{{ recipe.name }}</span>
                  </div>
                </td>

                <!-- Menu Terkait -->
                <td class="whitespace-nowrap px-5 py-4">
                  <div class="flex items-center gap-2 text-gray-700">
                    <UtensilsCrossed :size="14" class="text-gray-400" />
                    <span>{{ recipe.menuName }}</span>
                  </div>
                </td>

                <!-- Jumlah Bahan -->
                <td class="whitespace-nowrap px-5 py-4">
                  <div class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-orange-500" />
                    <span class="text-gray-700">{{ recipe.ingredients.length }} Bahan Baku</span>
                  </div>
                </td>

                <!-- Takaran / Porsi -->
                <td class="whitespace-nowrap px-5 py-4 text-gray-700">
                  {{ recipe.servingSize }}
                </td>

                <!-- Aksi -->
                <td class="whitespace-nowrap px-5 py-4">
                  <div class="flex items-center gap-1.5">
                    <!-- Lihat detail -->
                    <button
                      class="rounded-lg p-2 text-gray-400 transition hover:bg-blue-50 hover:text-blue-600"
                      title="Lihat Detail"
                      @click="viewRecipeDetail(recipe)"
                    >
                      <Eye :size="16" />
                    </button>
                    <!-- Edit -->
                    <button
                      class="rounded-lg p-2 text-gray-400 transition hover:bg-amber-50 hover:text-amber-600"
                      title="Edit Resep"
                      @click="openEditModal(recipe)"
                    >
                      <Pencil :size="16" />
                    </button>
                    <!-- Hapus -->
                    <button
                      class="rounded-lg p-2 text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                      title="Hapus Resep"
                      @click="openDeleteModal(recipe)"
                    >
                      <Trash2 :size="16" />
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Pesan kosong jika tidak ada data -->
              <tr v-if="paginatedRecipes.length === 0">
                <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                  <div class="flex flex-col items-center gap-2">
                    <BookOpen :size="32" class="text-gray-300" />
                    <p>Tidak ada resep ditemukan.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer tabel — paginasi -->
        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row">
          <p class="text-sm text-gray-500">{{ displayRange }}</p>
          <div class="flex items-center gap-1">
            <!-- Tombol Sebelumnya -->
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
              :disabled="currentPage <= 1"
              @click="goToPage(currentPage - 1)"
            >
              <ChevronLeft class="h-3.5 w-3.5" />
              Sebelumnya
            </button>

            <!-- Nomor Halaman (sliding window) -->
            <template v-for="(page, idx) in paginationPages" :key="'p-' + idx">
              <!-- Elipsis -->
              <span v-if="page === '...'" class="px-1.5 text-xs text-gray-400">…</span>

              <!-- Tombol Nomor Halaman -->
              <button
                v-else
                type="button"
                :class="[
                  'inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-semibold transition',
                  currentPage === page
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
              class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
              :disabled="currentPage >= totalPages"
              @click="goToPage(currentPage + 1)"
            >
              Berikutnya
              <ChevronRight class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================================================== -->
    <!-- MODAL: Tambah Resep Baru                                            -->
    <!-- ================================================================== -->
    <Teleport to="body">
      <div
        v-if="showAddModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeAddModal"
      >
        <div class="max-h-[90vh] w-full max-w-2xl flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
          <!-- Header modal -->
          <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50">
                <Plus :size="20" class="text-orange-600" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-gray-900">Tambah Resep Baru</h2>
                <p class="text-xs text-gray-500">Isi detail resep dan komposisi bahan baku</p>
              </div>
            </div>
            <button
              class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
              @click="closeAddModal"
            >
              <X :size="18" />
            </button>
          </div>

          <!-- Body modal — scrollable -->
          <div class="flex-1 overflow-y-auto p-6">
            <!-- Baris pertama: Nama Resep & Menu Terkait -->
            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Nama Resep *</label>
                <input
                  v-model="addForm.name"
                  type="text"
                  placeholder="Contoh: Resep Ayam Geprek"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Menu Terkait *</label>
                <select
                  v-model="addForm.menuId"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  @change="onAddMenuChange"
                >
                  <option :value="null" disabled>Pilih menu terkait</option>
                  <option v-for="menu in menuProducts" :key="menu.id" :value="menu.id">
                    {{ menu.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Baris kedua: Porsi Standar & Kategori Menu (otomatis / disabled) -->
            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Porsi Standar *</label>
                <input
                  v-model="addForm.servingSize"
                  type="text"
                  placeholder="Contoh: 1 Porsi Standar"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  <span class="flex items-center gap-1.5">
                    Kategori Menu
                    <Lock :size="13" class="text-gray-400" />
                  </span>
                </label>
                <input
                  :value="addMenuCategory || 'Otomatis dari menu terkait'"
                  type="text"
                  disabled
                  class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3.5 py-2.5 text-sm text-gray-500 outline-none"
                />
              </div>
            </div>

            <!-- Komposisi Bahan Baku (BOM) -->
            <div class="mb-5">
              <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-gray-700">Komposisi Bahan Baku (BOM)</span>
                  <span class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">
                    {{ addForm.ingredients.length }} Bahan ditambahkan
                  </span>
                </div>
                <span class="text-xs text-gray-400">(Min. 1 bahan baku wajib)</span>
              </div>

              <!-- Tombol tambah bahan -->
              <button
                class="mb-3 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-orange-300 bg-orange-50 px-3.5 py-2 text-sm font-medium text-orange-700 transition hover:bg-orange-100"
                @click="addIngredientRow(addForm)"
              >
                <Plus :size="14" />
                Tambah Bahan
              </button>

              <!-- Tabel bahan baku dinamis -->
              <div v-if="addForm.ingredients.length > 0" class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full text-left text-sm">
                  <thead>
                    <tr class="bg-gray-50/80">
                      <th class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Nama Bahan Baku
                      </th>
                      <th class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Jumlah
                      </th>
                      <th class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Satuan
                      </th>
                      <th class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Aksi
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(ingredient, iIdx) in addForm.ingredients"
                      :key="iIdx"
                      class="border-t border-gray-50"
                    >
                      <td class="px-4 py-2">
                        <select
                          v-model="ingredient.rawMaterialId"
                          class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                          @change="onIngredientChange(ingredient)"
                        >
                          <option :value="0" disabled>Pilih bahan</option>
                          <option v-for="rm in rawMaterials" :key="rm.id" :value="rm.id">
                            {{ rm.code }} — {{ rm.name }}
                          </option>
                        </select>
                      </td>
                      <td class="px-4 py-2">
                        <input
                          v-model.number="ingredient.quantity"
                          type="number"
                          min="0"
                          placeholder="0"
                          class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                        />
                      </td>
                      <td class="px-4 py-2 text-sm text-gray-500">
                        {{ ingredient.rawMaterialId ? getRawMaterialUnit(ingredient.rawMaterialId) : '-' }}
                      </td>
                      <td class="px-4 py-2">
                        <button
                          class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                          @click="removeIngredientRow(addForm, iIdx)"
                        >
                          <Trash2 :size="15" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Info box aturan resep -->
            <div class="mb-5 flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50/60 p-3.5">
              <Info :size="18" class="mt-0.5 shrink-0 text-blue-500" />
              <div class="text-xs text-blue-700">
                <p class="mb-1 font-semibold">Aturan Resep</p>
                <ul class="list-inside list-disc space-y-0.5">
                  <li>Setiap resep wajib memiliki minimal 1 bahan baku.</li>
                  <li>Pastikan nama resep tidak duplikat dengan resep lain.</li>
                  <li>Menu terkait hanya bisa dihubungkan ke satu resep.</li>
                </ul>
              </div>
            </div>

            <!-- Catatan / Instruksi (opsional) -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                Catatan / Instruksi Memasak (Opsional)
              </label>
              <textarea
                v-model="addForm.notes"
                rows="3"
                placeholder="Tambahkan catatan atau instruksi memasak..."
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
            </div>
          </div>

          <!-- Footer modal -->
          <div class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button
              class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
              @click="closeAddModal"
            >
              Batal
            </button>
            <button
              class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
              @click="createRecipe"
            >
              <Check :size="16" />
              Simpan Resep
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ================================================================== -->
    <!-- MODAL: Edit Resep                                                   -->
    <!-- ================================================================== -->
    <Teleport to="body">
      <div
        v-if="showEditModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeEditModal"
      >
        <div class="max-h-[90vh] w-full max-w-2xl flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
          <!-- Header modal -->
          <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                <Pencil :size="20" class="text-amber-600" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-gray-900">Edit Resep</h2>
                <p class="text-xs text-gray-500">Perbarui detail dan komposisi bahan baku resep</p>
              </div>
            </div>
            <button
              class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
              @click="closeEditModal"
            >
              <X :size="18" />
            </button>
          </div>

          <!-- Body modal — scrollable -->
          <div class="flex-1 overflow-y-auto p-6">
            <!-- Baris pertama: Nama Resep & Menu Terkait -->
            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Nama Resep *</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  placeholder="Contoh: Resep Ayam Geprek"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Menu Terkait *</label>
                <select
                  v-model="editForm.menuId"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  @change="onEditMenuChange"
                >
                  <option :value="null" disabled>Pilih menu terkait</option>
                  <option v-for="menu in menuProducts" :key="menu.id" :value="menu.id">
                    {{ menu.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Baris kedua: Porsi Standar & Kategori Menu (otomatis / disabled) -->
            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Porsi Standar *</label>
                <input
                  v-model="editForm.servingSize"
                  type="text"
                  placeholder="Contoh: 1 Porsi Standar"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  <span class="flex items-center gap-1.5">
                    Kategori Menu
                    <Lock :size="13" class="text-gray-400" />
                  </span>
                </label>
                <input
                  :value="editMenuCategory || 'Otomatis dari menu terkait'"
                  type="text"
                  disabled
                  class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3.5 py-2.5 text-sm text-gray-500 outline-none"
                />
              </div>
            </div>

            <!-- Komposisi Bahan Baku (BOM) -->
            <div class="mb-5">
              <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-gray-700">Komposisi Bahan Baku (BOM)</span>
                  <span class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">
                    {{ editForm.ingredients.length }} Bahan ditambahkan
                  </span>
                </div>
                <span class="text-xs text-gray-400">(Min. 1 bahan baku wajib)</span>
              </div>

              <!-- Tombol tambah bahan -->
              <button
                class="mb-3 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-orange-300 bg-orange-50 px-3.5 py-2 text-sm font-medium text-orange-700 transition hover:bg-orange-100"
                @click="addIngredientRow(editForm)"
              >
                <Plus :size="14" />
                Tambah Bahan
              </button>

              <!-- Tabel bahan baku dinamis -->
              <div v-if="editForm.ingredients.length > 0" class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full text-left text-sm">
                  <thead>
                    <tr class="bg-gray-50/80">
                      <th class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Nama Bahan Baku
                      </th>
                      <th class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Jumlah
                      </th>
                      <th class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Satuan
                      </th>
                      <th class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Aksi
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(ingredient, iIdx) in editForm.ingredients"
                      :key="iIdx"
                      class="border-t border-gray-50"
                    >
                      <td class="px-4 py-2">
                        <select
                          v-model="ingredient.rawMaterialId"
                          class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                          @change="onIngredientChange(ingredient)"
                        >
                          <option :value="0" disabled>Pilih bahan</option>
                          <option v-for="rm in rawMaterials" :key="rm.id" :value="rm.id">
                            {{ rm.code }} — {{ rm.name }}
                          </option>
                        </select>
                      </td>
                      <td class="px-4 py-2">
                        <input
                          v-model.number="ingredient.quantity"
                          type="number"
                          min="0"
                          placeholder="0"
                          class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                        />
                      </td>
                      <td class="px-4 py-2 text-sm text-gray-500">
                        {{ ingredient.rawMaterialId ? getRawMaterialUnit(ingredient.rawMaterialId) : '-' }}
                      </td>
                      <td class="px-4 py-2">
                        <button
                          class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                          @click="removeIngredientRow(editForm, iIdx)"
                        >
                          <Trash2 :size="15" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Info box aturan resep -->
            <div class="mb-5 flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50/60 p-3.5">
              <Info :size="18" class="mt-0.5 shrink-0 text-blue-500" />
              <div class="text-xs text-blue-700">
                <p class="mb-1 font-semibold">Aturan Resep</p>
                <ul class="list-inside list-disc space-y-0.5">
                  <li>Setiap resep wajib memiliki minimal 1 bahan baku.</li>
                  <li>Pastikan nama resep tidak duplikat dengan resep lain.</li>
                  <li>Menu terkait hanya bisa dihubungkan ke satu resep.</li>
                </ul>
              </div>
            </div>

            <!-- Catatan / Instruksi (opsional) -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                Catatan / Instruksi Memasak (Opsional)
              </label>
              <textarea
                v-model="editForm.notes"
                rows="3"
                placeholder="Tambahkan catatan atau instruksi memasak..."
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
            </div>
          </div>

          <!-- Footer modal -->
          <div class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button
              class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
              @click="closeEditModal"
            >
              Batal
            </button>
            <button
              class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
              @click="updateRecipe"
            >
              <Check :size="16" />
              Simpan Perubahan
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ================================================================== -->
    <!-- MODAL: Hapus Resep                                                  -->
    <!-- ================================================================== -->
    <Teleport to="body">
      <div
        v-if="showDeleteModal && selectedRecipe"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeDeleteModal"
      >
        <div class="max-h-[90vh] w-full max-w-md flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
          <!-- Body -->
          <div class="flex-1 overflow-y-auto p-6">
            <!-- Ikon hapus -->
            <div class="mb-5 flex justify-center">
              <div class="flex h-16 w-16 items-center justify-center rounded-full bg-red-50">
                <Trash2 :size="28" class="text-red-500" />
              </div>
            </div>

            <!-- Judul & deskripsi -->
            <div class="mb-5 text-center">
              <h2 class="text-xl font-bold text-gray-900">Hapus Resep?</h2>
              <p class="mt-2 text-sm text-gray-500">
                Tindakan ini akan menghapus resep beserta seluruh komposisi bahan bakunya secara permanen dan tidak
                dapat dikembalikan.
              </p>
            </div>

            <!-- Kartu ringkasan resep -->
            <div class="mb-5 rounded-xl border border-gray-100 bg-gray-50 p-4">
              <div class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-500">Nama Resep</span>
                  <span class="font-semibold text-gray-900">{{ selectedRecipe.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Menu Terkait</span>
                  <span class="font-medium text-gray-700">{{ selectedRecipe.menuName }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Komposisi Bahan</span>
                  <span class="font-medium text-gray-700">{{ selectedRecipe.ingredients.length }} Bahan Baku</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Porsi Standar</span>
                  <span class="font-medium text-gray-700">{{ selectedRecipe.servingSize }}</span>
                </div>
              </div>
            </div>

            <!-- Warning box — proteksi data -->
            <div class="mb-4 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3.5">
              <AlertTriangle :size="18" class="mt-0.5 shrink-0 text-amber-600" />
              <div class="text-xs text-amber-800">
                <p class="mb-1 font-semibold">Proteksi Data & Pesanan Aktif Restoran</p>
                <p>
                  Pastikan tidak ada pesanan aktif yang menggunakan resep ini. Penghapusan resep dapat mempengaruhi
                  kalkulasi stok dan laporan bahan baku.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer modal -->
          <div class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button
              class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
              @click="closeDeleteModal"
            >
              Batal
            </button>
            <button
              class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
              @click="deleteRecipe"
            >
              <Trash2 :size="16" />
              Hapus Resep
            </button>
          </div>

          <!-- Catatan kaki -->
          <p class="shrink-0 px-6 pb-4 text-center text-xs italic text-gray-400">
            Data yang sudah dihapus tidak dapat dipulihkan kembali oleh sistem.
          </p>
        </div>
      </div>
    </Teleport>
  </DashboardLayout>
</template>
