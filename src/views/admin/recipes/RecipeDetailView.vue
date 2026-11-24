<script setup lang="ts">
import { ref, computed, watch, onMounted, markRaw, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  ArrowLeft,
  Pencil,
  Check,
  X,
  Info,
  ExternalLink,
  ClipboardCheck,
  Lock,
  Trash2,
  Plus,
  AlertTriangle,
  BookOpen,
  UtensilsCrossed,
  Layers,
  CheckCircle2,
  Scale,
  FileText,
  ChefHat,
  Flame,
} from 'lucide-vue-next'

// ==================== Tipe Data ====================

/** Bahan baku mentah dari master data gudang */
interface RawMaterial {
  id: number
  code: string
  name: string
  unit: string
}

/** Bahan baku dalam komposisi resep */
interface RecipeIngredient {
  rawMaterialId: number
  rawMaterialCode: string
  rawMaterialName: string
  quantity: number
  unit: string
  status: string
}

/** Detail lengkap resep Bill of Materials */
interface RecipeDetail {
  id: number
  code: string
  name: string
  menuName: string
  menuCategory: string
  servingSize: string
  notes: string
  status: string
  ingredients: RecipeIngredient[]
}

/** Form data untuk edit resep */
interface RecipeEditForm {
  name: string
  menuProductId: number | null
  menuCategory: string
  servingSize: string
  notes: string
  ingredients: RecipeIngredient[]
}

/** Produk menu untuk dropdown pilihan */
interface MenuProduct {
  id: number
  name: string
  category: string
}

// ==================== Ikon (markRaw) ====================

/** Ikon yang digunakan di dalam data reaktif, dibungkus markRaw agar tidak di-proxy Vue */
const icons = {
  ChefHat: markRaw(ChefHat) as Component,
  ClipboardCheck: markRaw(ClipboardCheck) as Component,
  Pencil: markRaw(Pencil) as Component,
  BookOpen: markRaw(BookOpen) as Component,
  UtensilsCrossed: markRaw(UtensilsCrossed) as Component,
  Layers: markRaw(Layers) as Component,
  CheckCircle2: markRaw(CheckCircle2) as Component,
  Scale: markRaw(Scale) as Component,
  FileText: markRaw(FileText) as Component,
  Flame: markRaw(Flame) as Component,
  AlertTriangle: markRaw(AlertTriangle) as Component,
  Info: markRaw(Info) as Component,
}

// ==================== Route & Router ====================

const route = useRoute()
const router = useRouter()

/** ID resep dari parameter route */
const recipeId = computed<string>(() => {
  const id = route.params['id']
  return Array.isArray(id) ? (id[0] ?? '') : (id ?? '')
})

// ==================== Master Data ====================

/** Master data bahan baku tersedia di gudang */
const rawMaterials = ref<RawMaterial[]>([
  { id: 1, code: 'BB-01', name: 'Daging Ayam Fillet', unit: 'Gram (gr)' },
  { id: 2, code: 'BB-02', name: 'Beras Premium Pulen', unit: 'Gram (gr)' },
  { id: 3, code: 'BB-03', name: 'Minyak Goreng Sawit', unit: 'Mililiter (ml)' },
  { id: 4, code: 'BB-04', name: 'Madu Murni Alami', unit: 'Mililiter (ml)' },
  { id: 5, code: 'BB-05', name: 'Gula Pasir Putih', unit: 'Gram (gr)' },
  { id: 6, code: 'BB-06', name: 'Garam Halus Beryodium', unit: 'Gram (gr)' },
  { id: 7, code: 'BB-07', name: 'Tepung Terigu Serbaguna', unit: 'Gram (gr)' },
  { id: 8, code: 'BB-08', name: 'Susu Segar UHT Full Cream', unit: 'Mililiter (ml)' },
  { id: 9, code: 'BB-09', name: 'Telur Ayam Negeri', unit: 'Butir (pcs)' },
  { id: 10, code: 'BB-10', name: 'Kecap Manis Premium', unit: 'Mililiter (ml)' },
  { id: 11, code: 'BB-11', name: 'Cabai Merah Keriting Segar', unit: 'Gram (gr)' },
  { id: 12, code: 'BB-12', name: 'Bumbu Rempah Marinasi', unit: 'Gram (gr)' },
  { id: 13, code: 'BB-13', name: 'Santan Kelapa Murni', unit: 'Mililiter (ml)' },
  { id: 14, code: 'BB-14', name: 'Bawang Merah Kupas', unit: 'Gram (gr)' },
  { id: 15, code: 'BB-15', name: 'Bawang Putih Kupas', unit: 'Gram (gr)' },
  { id: 16, code: 'BB-16', name: 'Daun Bawang Segar', unit: 'Gram (gr)' },
])

/** Master data produk menu yang tersedia */
const menuProducts = ref<MenuProduct[]>([
  { id: 1, name: 'Ayam Bakar Madu Spesial', category: 'Makanan Utama' },
  { id: 2, name: 'Nasi Goreng Spesial', category: 'Makanan Utama' },
  { id: 3, name: 'Mie Goreng Seafood', category: 'Makanan Utama' },
  { id: 4, name: 'Soto Ayam Lamongan', category: 'Makanan Utama' },
  { id: 5, name: 'Es Teh Manis Segar', category: 'Minuman' },
  { id: 6, name: 'Jus Jeruk Peras Asli', category: 'Minuman' },
  { id: 7, name: 'Puding Coklat Premium', category: 'Dessert' },
  { id: 8, name: 'Kentang Goreng Crispy', category: 'Makanan Ringan' },
  { id: 9, name: 'Ayam Geprek Sambal Matah', category: 'Makanan Utama' },
  { id: 10, name: 'Rendang Daging Sapi', category: 'Makanan Utama' },
  { id: 11, name: 'Es Kopi Susu Gula Aren', category: 'Minuman' },
  { id: 12, name: 'Pisang Goreng Keju', category: 'Makanan Ringan' },
  { id: 13, name: 'Capcay Sayuran Segar', category: 'Makanan Utama' },
  { id: 14, name: 'Teh Tarik Hangat', category: 'Minuman' },
  { id: 15, name: 'Sup Iga Sapi Rempah', category: 'Makanan Utama' },
])

// ==================== State ====================

/** Data detail resep (dummy untuk pengembangan) */
const recipe = ref<RecipeDetail>({
  id: 1,
  code: 'RSP-ABM-001',
  name: 'Resep Ayam Bakar Madu Spesial',
  menuName: 'Ayam Bakar Madu Spesial',
  menuCategory: 'Makanan Utama',
  servingSize: '1 Porsi Standar',
  notes:
    'Ayam dimarinasi selama minimal 2 jam dengan campuran madu, bumbu rempah, dan kecap manis sebelum dibakar. Proses marinasi sangat penting untuk menghasilkan cita rasa yang meresap sempurna ke dalam daging. Pastikan api bara tidak terlalu besar agar madu tidak gosong saat proses pembakaran.',
  status: 'Aktif',
  ingredients: [
    {
      rawMaterialId: 1,
      rawMaterialCode: 'BB-01',
      rawMaterialName: 'Daging Ayam Fillet',
      quantity: 250,
      unit: 'Gram (gr)',
      status: 'Tersedia di Gudang',
    },
    {
      rawMaterialId: 4,
      rawMaterialCode: 'BB-04',
      rawMaterialName: 'Madu Murni Alami',
      quantity: 30,
      unit: 'Mililiter (ml)',
      status: 'Tersedia di Gudang',
    },
    {
      rawMaterialId: 12,
      rawMaterialCode: 'BB-12',
      rawMaterialName: 'Bumbu Rempah Marinasi',
      quantity: 50,
      unit: 'Gram (gr)',
      status: 'Stok Terbatas',
    },
  ],
})

/** Kontrol visibilitas modal edit resep */
const showEditModal = ref<boolean>(false)

/** Data form untuk modal edit resep */
const formData = ref<RecipeEditForm>({
  name: '',
  menuProductId: null,
  menuCategory: '',
  servingSize: '',
  notes: '',
  ingredients: [],
})

// ==================== Computed ====================

/** Jumlah total bahan baku dalam resep */
const totalIngredients = computed<number>(() => recipe.value.ingredients.length)

/** Jumlah karakter catatan pada form edit */
const notesLength = computed<number>(() => formData.value.notes.length)

// ==================== Fungsi Pembantu ====================

/**
 * Mendapatkan kelas CSS untuk badge status bahan baku
 * Tersedia di Gudang → hijau, Stok Terbatas → kuning/amber
 */
function getIngredientStatusClasses(status: string): string {
  if (status === 'Tersedia di Gudang') {
    return 'bg-green-50 text-green-700 border border-green-200'
  }
  if (status === 'Stok Terbatas') {
    return 'bg-amber-50 text-amber-700 border border-amber-200'
  }
  return 'bg-gray-50 text-gray-600 border border-gray-200'
}

/**
 * Mendapatkan kelas warna dot indikator status bahan baku
 */
function getIngredientStatusDot(status: string): string {
  if (status === 'Tersedia di Gudang') return 'bg-green-500'
  if (status === 'Stok Terbatas') return 'bg-amber-500'
  return 'bg-gray-400'
}

/** Navigasi kembali ke halaman manajemen resep */
function goBack(): void {
  router.push('/admin/recipes')
}

/** Membuka modal edit dan mengisi form dengan data resep saat ini */
function openEditModal(): void {
  // Mencari ID produk menu yang sesuai dengan nama menu resep
  const matchedProduct = menuProducts.value.find(
    (p) => p.name === recipe.value.menuName,
  )
  formData.value = {
    name: recipe.value.name,
    menuProductId: matchedProduct?.id ?? null,
    menuCategory: recipe.value.menuCategory,
    servingSize: recipe.value.servingSize,
    notes: recipe.value.notes,
    ingredients: recipe.value.ingredients.map((ing) => ({ ...ing })),
  }
  showEditModal.value = true
}

/** Menutup modal edit resep */
function closeEditModal(): void {
  showEditModal.value = false
}

/**
 * Menambahkan baris bahan baku baru ke dalam form edit
 * Baris baru memiliki nilai kosong/default
 */
function addIngredientRow(): void {
  formData.value.ingredients.push({
    rawMaterialId: 0,
    rawMaterialCode: '',
    rawMaterialName: '',
    quantity: 0,
    unit: '',
    status: 'Tersedia di Gudang',
  })
}

/**
 * Menghapus baris bahan baku dari form edit berdasarkan index
 * Validasi index agar aman dengan noUncheckedIndexedAccess
 */
function removeIngredientRow(index: number): void {
  const item = formData.value.ingredients[index]
  if (item !== undefined) {
    formData.value.ingredients.splice(index, 1)
  }
}

/**
 * Menangani perubahan dropdown bahan baku pada baris tertentu
 * Otomatis mengisi kode, nama, dan satuan dari master data
 */
function onIngredientChange(index: number, rawMaterialId: number): void {
  const ingredient = formData.value.ingredients[index]
  if (ingredient === undefined) return

  const material = rawMaterials.value.find((m) => m.id === rawMaterialId)
  if (material) {
    ingredient.rawMaterialId = material.id
    ingredient.rawMaterialCode = material.code
    ingredient.rawMaterialName = material.name
    ingredient.unit = material.unit
  }
}

/**
 * Menangani perubahan dropdown menu terkait
 * Otomatis mengisi kategori menu dari master data produk
 */
function onMenuProductChange(menuProductId: number): void {
  const product = menuProducts.value.find((p) => p.id === menuProductId)
  if (product) {
    formData.value.menuCategory = product.category
  } else {
    formData.value.menuCategory = ''
  }
}

/**
 * Mengambil data detail resep dari server berdasarkan ID
 * [API-READY] — Akan diganti dengan panggilan API GET ke backend
 */
function fetchRecipeDetail(_id: string): void {
  // [API-READY] TODO: Panggil API GET /api/recipes/:id untuk mengambil detail resep
  // Saat ini menggunakan data dummy yang sudah diinisialisasi di state
  console.log(`Mengambil detail resep dengan ID: ${_id}`)
}

/**
 * Menyimpan perubahan resep dari modal edit
 * [API-READY] — Akan diganti dengan panggilan API PUT/PATCH ke backend
 */
function updateRecipe(): void {
  // [API-READY] TODO: Kirim request PUT/PATCH ke endpoint update resep

  // Mencari nama menu dari produk yang dipilih
  const selectedProduct = formData.value.menuProductId
    ? menuProducts.value.find((p) => p.id === formData.value.menuProductId)
    : undefined

  recipe.value = {
    ...recipe.value,
    name: formData.value.name,
    menuName: selectedProduct?.name ?? recipe.value.menuName,
    menuCategory: formData.value.menuCategory,
    servingSize: formData.value.servingSize,
    notes: formData.value.notes,
    ingredients: formData.value.ingredients.map((ing) => ({ ...ing })),
  }
  closeEditModal()
}

// ==================== Watchers ====================

/** Watch perubahan dropdown menu terkait untuk auto-fill kategori */
watch(
  () => formData.value.menuProductId,
  (newId) => {
    if (newId !== null && newId > 0) {
      onMenuProductChange(newId)
    }
  },
)

// ==================== Lifecycle ====================

onMounted(() => {
  fetchRecipeDetail(recipeId.value)
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-[#F8FAFC] p-6 lg:p-8">
      <!-- ============ Header Halaman ============ -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ recipe.name }}
            </h1>
            <!-- Badge status resep -->
            <span
              class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-green-500" />
              {{ recipe.status }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-500">
            Detail lengkap komposisi bahan baku dan instruksi resep untuk produksi dapur.
          </p>
        </div>

        <!-- Tombol aksi header -->
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
            Edit Resep
          </button>
        </div>
      </div>

      <!-- ============ Kartu 1: Informasi Resep ============ -->
      <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Header kartu -->
        <div
          class="flex flex-col gap-3 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50">
              <component :is="icons.BookOpen" class="h-5 w-5 text-[#C2410C]" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-gray-900">Informasi Resep</h2>
              <p class="text-sm text-gray-500">Data umum dan keterangan resep terkait produk menu.</p>
            </div>
          </div>
          <!-- Badge ID resep -->
          <span
            class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600"
          >
            ID: {{ recipe.code }}
          </span>
        </div>

        <div class="p-6">
          <!-- Grid 2x2 informasi resep -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Nama Resep -->
            <div class="rounded-lg bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Nama Resep
              </p>
              <p class="text-sm font-bold text-gray-900">{{ recipe.name }}</p>
            </div>

            <!-- Menu Terkait -->
            <div class="rounded-lg bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Menu Terkait
              </p>
              <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1 text-sm font-bold text-[#C2410C]">
                  {{ recipe.menuName }}
                  <ExternalLink class="h-3.5 w-3.5" />
                </span>
                <span
                  class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-[11px] font-semibold text-green-700"
                >
                  Produk Siap Jual
                </span>
              </div>
            </div>

            <!-- Kategori Menu -->
            <div class="rounded-lg bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Kategori Menu
              </p>
              <div class="flex items-center gap-2">
                <component :is="icons.Layers" class="h-4 w-4 text-gray-400" />
                <span class="text-sm font-bold text-gray-900">{{ recipe.menuCategory }}</span>
                <span class="text-xs text-gray-400">(Otomatis dari produk)</span>
              </div>
            </div>

            <!-- Porsi Standar -->
            <div class="rounded-lg bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Porsi Standar
              </p>
              <div class="flex items-center gap-2">
                <component :is="icons.Scale" class="h-4 w-4 text-gray-400" />
                <span class="text-sm font-bold text-gray-900">{{ recipe.servingSize }}</span>
              </div>
            </div>
          </div>

          <!-- Catatan / Instruksi Memasak (lebar penuh) -->
          <div class="mt-4 rounded-lg bg-slate-50 p-4">
            <div class="mb-2 flex items-center gap-2">
              <component :is="icons.Flame" class="h-4 w-4 text-orange-500" />
              <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                Catatan / Instruksi Memasak
              </p>
            </div>
            <p class="text-sm leading-relaxed text-gray-700">{{ recipe.notes }}</p>
          </div>
        </div>
      </div>

      <!-- ============ Kartu 2: Komposisi Bahan Baku (Bill of Materials) ============ -->
      <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Header kartu -->
        <div
          class="flex flex-col gap-3 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50">
              <component :is="icons.ClipboardCheck" class="h-5 w-5 text-[#C2410C]" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-gray-900">Komposisi Bahan Baku (Bill of Materials)</h2>
              <p class="text-sm text-gray-500">
                Daftar bahan baku dan takaran yang dibutuhkan untuk memproduksi resep ini.
              </p>
            </div>
          </div>
          <!-- Badge jumlah bahan dan takaran -->
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
            >
              <component :is="icons.UtensilsCrossed" class="h-3.5 w-3.5" />
              {{ totalIngredients }} Bahan Baku Terdaftar
            </span>
            <span
              class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600"
            >
              <component :is="icons.Scale" class="h-3.5 w-3.5" />
              Takaran per {{ recipe.servingSize }}
            </span>
          </div>
        </div>

        <!-- Tabel daftar bahan baku -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50/50">
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                  style="width: 60px"
                >
                  No
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                >
                  Nama Bahan Baku
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                >
                  Jumlah Kebutuhan Per Porsi
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                >
                  Satuan
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                >
                  Keterangan / Status
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(ingredient, index) in recipe.ingredients"
                :key="ingredient.rawMaterialId"
                class="transition-colors hover:bg-gray-50/50"
              >
                <!-- Nomor urut -->
                <td class="px-6 py-3.5 text-sm text-gray-500">
                  {{ index + 1 }}
                </td>
                <!-- Nama bahan baku + badge kode -->
                <td class="px-6 py-3.5">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900">
                      {{ ingredient.rawMaterialName }}
                    </span>
                    <span
                      class="rounded bg-gray-100 px-1.5 py-0.5 text-[11px] font-medium text-gray-500"
                    >
                      {{ ingredient.rawMaterialCode }}
                    </span>
                  </div>
                </td>
                <!-- Jumlah kebutuhan (rata kanan, bold) -->
                <td class="px-6 py-3.5 text-right text-sm font-bold text-gray-900">
                  {{ ingredient.quantity }}
                </td>
                <!-- Satuan -->
                <td class="px-6 py-3.5 text-sm text-gray-600">
                  {{ ingredient.unit }}
                </td>
                <!-- Status badge -->
                <td class="px-6 py-3.5">
                  <span
                    :class="getIngredientStatusClasses(ingredient.status)"
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                  >
                    <span
                      :class="getIngredientStatusDot(ingredient.status)"
                      class="h-1.5 w-1.5 rounded-full"
                    />
                    {{ ingredient.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Baris ringkasan bawah tabel -->
        <div
          class="flex flex-col gap-3 border-t border-gray-100 px-6 py-3.5 sm:flex-row sm:items-center sm:justify-between"
        >
          <p class="text-sm text-gray-500">
            Ringkasan Bahan Baku: Total
            <span class="font-semibold text-gray-700">{{ totalIngredients }} Bahan Baku Terdaftar</span>
            · {{ recipe.servingSize }}
          </p>
          <span
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-green-600"
          >
            <CheckCircle2 class="h-4 w-4" />
            Data terverifikasi untuk produksi dapur
          </span>
        </div>
      </div>

      <!-- ============ Info Box: Aturan Sistem & Validasi Resep ============ -->
      <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/50 p-5">
        <div class="mb-3 flex items-center gap-2">
          <Info class="h-5 w-5 text-blue-600" />
          <h3 class="text-sm font-bold text-blue-900">Aturan Sistem &amp; Validasi Resep (Bill of Materials)</h3>
        </div>
        <ul class="space-y-2 text-sm leading-relaxed text-blue-800">
          <li class="flex items-start gap-2">
            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400" />
            Setiap resep harus terhubung dengan minimal satu produk menu aktif agar dapat digunakan dalam sistem produksi dapur.
          </li>
          <li class="flex items-start gap-2">
            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400" />
            Perubahan komposisi bahan baku akan otomatis memengaruhi perhitungan HPP (Harga Pokok Penjualan) dan kebutuhan stok gudang.
          </li>
          <li class="flex items-start gap-2">
            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400" />
            Bahan baku dengan status "Stok Terbatas" tetap dapat digunakan dalam resep namun akan memunculkan peringatan saat proses produksi.
          </li>
        </ul>
      </div>

      <!-- ============ Footer / Catatan Kaki ============ -->
      <div class="mt-6 flex flex-col gap-1 text-xs text-gray-400 sm:flex-row sm:items-center sm:justify-between">
        <p>
          Data resep ini dikelola oleh sistem manajemen dapur dan tersinkronisasi dengan modul inventori gudang.
        </p>
        <p>Terakhir diselaraskan: Hari ini, 08:30 WIB</p>
      </div>

      <!-- ============ Modal Edit Resep (Teleport ke body) ============ -->
      <Teleport to="body">
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          @click.self="closeEditModal"
        >
          <div
            class="max-h-[90vh] w-full max-w-2xl flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
          >
            <!-- Header modal (shrink-0) -->
            <div class="shrink-0 border-b border-gray-100 px-6 py-5">
              <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50"
                  >
                    <component :is="icons.Pencil" class="h-5 w-5 text-[#C2410C]" />
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="text-lg font-bold text-gray-900">Edit Resep</h3>
                      <span
                        class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                      >
                        ID: {{ recipe.code }}
                      </span>
                    </div>
                    <p class="mt-0.5 text-sm text-gray-500">
                      Perbarui informasi komposisi dan detail resep ini.
                    </p>
                  </div>
                </div>
                <!-- Tombol tutup modal -->
                <button
                  class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                  @click="closeEditModal"
                >
                  <X class="h-5 w-5" />
                </button>
              </div>
            </div>

            <!-- Konten form modal (scrollable) -->
            <div class="flex-1 space-y-5 overflow-y-auto p-6">
              <!-- Baris 1: Nama Resep & Menu Terkait -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Nama Resep -->
                <div>
                  <label
                    for="edit-recipe-name"
                    class="mb-1.5 block text-sm font-semibold text-gray-700"
                  >
                    Nama Resep <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="edit-recipe-name"
                    v-model="formData.name"
                    type="text"
                    placeholder="Masukkan nama resep"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  />
                </div>

                <!-- Menu Terkait (dropdown) -->
                <div>
                  <label
                    for="edit-menu-product"
                    class="mb-1.5 block text-sm font-semibold text-gray-700"
                  >
                    Menu Terkait <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="edit-menu-product"
                    v-model.number="formData.menuProductId"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  >
                    <option :value="null" disabled>Pilih produk menu...</option>
                    <option
                      v-for="product in menuProducts"
                      :key="product.id"
                      :value="product.id"
                    >
                      {{ product.name }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Baris 2: Porsi Standar & Kategori Menu (disabled) -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Porsi Standar -->
                <div>
                  <label
                    for="edit-serving-size"
                    class="mb-1.5 block text-sm font-semibold text-gray-700"
                  >
                    Porsi Standar <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="edit-serving-size"
                    v-model="formData.servingSize"
                    type="text"
                    placeholder="Contoh: 1 Porsi Standar"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  />
                </div>

                <!-- Kategori Menu (disabled, otomatis dari menu terkait) -->
                <div>
                  <label
                    for="edit-menu-category"
                    class="mb-1.5 block text-sm font-semibold text-gray-700"
                  >
                    Kategori Menu
                  </label>
                  <div class="relative">
                    <input
                      id="edit-menu-category"
                      :value="formData.menuCategory"
                      type="text"
                      disabled
                      class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3.5 py-2.5 pr-10 text-sm text-gray-500 outline-none"
                    />
                    <Lock class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                  </div>
                  <p class="mt-1 text-xs text-gray-400">Otomatis terisi dari menu terkait yang dipilih.</p>
                </div>
              </div>

              <!-- ===== Bagian BOM (Komposisi Bahan Baku) ===== -->
              <div>
                <div class="mb-3 flex items-center justify-between">
                  <label class="text-sm font-semibold text-gray-700">
                    Komposisi Bahan Baku (BOM) <span class="text-red-500">*</span>
                  </label>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-[#C2410C]/20 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-[#C2410C] transition-colors hover:bg-orange-100"
                    @click="addIngredientRow"
                  >
                    <Plus class="h-3.5 w-3.5" />
                    Tambah Bahan
                  </button>
                </div>

                <!-- Daftar baris bahan baku dinamis -->
                <div class="space-y-3">
                  <div
                    v-for="(ingredient, index) in formData.ingredients"
                    :key="index"
                    class="flex items-start gap-3 rounded-lg border border-gray-200 bg-slate-50/50 p-3"
                  >
                    <!-- Nomor baris -->
                    <span class="mt-2.5 shrink-0 text-xs font-bold text-gray-400">
                      {{ index + 1 }}.
                    </span>

                    <!-- Dropdown bahan baku -->
                    <div class="flex-1">
                      <label class="mb-1 block text-xs font-medium text-gray-500">Bahan Baku</label>
                      <select
                        :value="ingredient.rawMaterialId"
                        class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                        @change="onIngredientChange(index, Number(($event.target as HTMLSelectElement).value))"
                      >
                        <option :value="0" disabled>Pilih bahan...</option>
                        <option
                          v-for="material in rawMaterials"
                          :key="material.id"
                          :value="material.id"
                        >
                          {{ material.name }} ({{ material.code }})
                        </option>
                      </select>
                    </div>

                    <!-- Input jumlah -->
                    <div class="w-24 shrink-0">
                      <label class="mb-1 block text-xs font-medium text-gray-500">Jumlah</label>
                      <input
                        v-model.number="ingredient.quantity"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                      />
                    </div>

                    <!-- Satuan (auto, readonly) -->
                    <div class="w-28 shrink-0">
                      <label class="mb-1 block text-xs font-medium text-gray-500">Satuan</label>
                      <input
                        :value="ingredient.unit"
                        type="text"
                        disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3.5 py-2.5 text-sm text-gray-500 outline-none"
                      />
                    </div>

                    <!-- Tombol hapus baris -->
                    <button
                      type="button"
                      class="mt-6 shrink-0 rounded-lg p-1.5 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                      title="Hapus bahan baku"
                      @click="removeIngredientRow(index)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>

                  <!-- Pesan jika belum ada bahan baku -->
                  <div
                    v-if="formData.ingredients.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-200 py-6 text-center"
                  >
                    <component :is="icons.UtensilsCrossed" class="mb-2 h-7 w-7 text-gray-300" />
                    <p class="text-sm font-medium text-gray-500">Belum ada bahan baku ditambahkan.</p>
                    <p class="mt-0.5 text-xs text-gray-400">Klik "Tambah Bahan" untuk menambahkan komposisi.</p>
                  </div>
                </div>
              </div>

              <!-- Info box aturan resep di dalam modal -->
              <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50/70 p-3">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                <p class="text-xs leading-relaxed text-amber-700">
                  <span class="font-semibold">Aturan Resep:</span>
                  Setiap resep wajib memiliki minimal satu bahan baku. Jumlah takaran harus lebih dari nol.
                  Perubahan komposisi bahan akan memperbarui kalkulasi HPP secara otomatis.
                </p>
              </div>

              <!-- Textarea catatan / instruksi memasak -->
              <div>
                <div class="mb-1.5 flex items-center justify-between">
                  <label
                    for="edit-notes"
                    class="text-sm font-semibold text-gray-700"
                  >
                    Catatan / Instruksi Memasak
                  </label>
                  <span class="text-xs text-gray-400">Opsional</span>
                </div>
                <textarea
                  id="edit-notes"
                  v-model="formData.notes"
                  rows="4"
                  maxlength="500"
                  placeholder="Tuliskan instruksi, tips, atau catatan khusus untuk proses memasak..."
                  class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
                <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                  <span>Catatan ini akan ditampilkan di layar produksi dapur.</span>
                  <span>{{ notesLength }}/500</span>
                </div>
              </div>
            </div>

            <!-- Footer modal (shrink-0) -->
            <div
              class="flex shrink-0 flex-col items-center justify-between gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row"
            >
              <!-- Informasi terakhir diedit -->
              <p class="text-xs text-gray-400">Diedit terakhir: Hari ini 10:42</p>

              <!-- Tombol aksi modal -->
              <div class="flex items-center gap-3">
                <button
                  class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                  @click="closeEditModal"
                >
                  Batal
                </button>
                <button
                  class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#B45309]"
                  @click="updateRecipe"
                >
                  <Check class="h-4 w-4" />
                  Simpan Perubahan
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </DashboardLayout>
</template>
