<script setup lang="ts">
import { ref, computed, onMounted, markRaw, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  ArrowLeft,
  UtensilsCrossed,
  Pencil,
  ExternalLink,
  ClipboardCheck,
  SquarePen,
  X,
  Check,
  Camera,
  Trash2,
  Info,
} from 'lucide-vue-next'

// ==================== Tipe Data ====================

/** Status ketersediaan produk */
type ProductStatus = 'Tersedia' | 'Habis'

/** Bahan baku dalam resep */
interface RecipeIngredient {
  id: number
  name: string
  quantity: string
}

/** Detail lengkap produk */
interface ProductDetail {
  id: number
  sku: string
  name: string
  category: string
  price: number
  status: ProductStatus
  imageUrl: string | null
  description: string
  recipeName: string
  ingredients: RecipeIngredient[]
}

/** Form data untuk edit produk */
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

// ==================== Ikon (markRaw) ====================

/** Ikon yang digunakan di dalam data reaktif, dibungkus markRaw agar tidak di-proxy Vue */
const icons = {
  UtensilsCrossed: markRaw(UtensilsCrossed) as Component,
  ClipboardCheck: markRaw(ClipboardCheck) as Component,
  SquarePen: markRaw(SquarePen) as Component,
}

// ==================== Route & Router ====================

const route = useRoute()
const router = useRouter()

/** ID produk dari parameter route */
const productId = computed<string>(() => {
  const id = route.params['id']
  return Array.isArray(id) ? (id[0] ?? '') : (id ?? '')
})

// ==================== State ====================

/** Data detail produk (dummy untuk pengembangan) */
const product = ref<ProductDetail>({
  id: 1,
  sku: 'PRD-001',
  name: 'Ayam Bakar Madu Spesial',
  category: 'Makanan Utama',
  price: 38000,
  status: 'Tersedia',
  imageUrl: '/images/ayam-bakar.jpg',
  description:
    'Pilihan olahan ayam bakar bumbu madu khas nusantara yang disajikan dengan sambal dan lalapan segar.',
  recipeName: 'Resep Ayam Bakar Madu (1 Porsi)',
  ingredients: [
    { id: 1, name: 'Daging Ayam Broiler', quantity: '0.25 kg (1 potong)' },
    { id: 2, name: 'Madu Murni Hutan', quantity: '25 ml' },
    { id: 3, name: 'Bumbu Kuning Marinasi', quantity: '30 gr' },
    { id: 4, name: 'Kecap Manis Premium', quantity: '20 ml' },
    { id: 5, name: 'Sambal Terasi Bajak', quantity: '25 gr' },
  ],
})

/** Daftar kategori produk untuk dropdown */
const categoryList = ref<string[]>([
  'Makanan Utama',
  'Minuman',
  'Dessert',
  'Makanan Ringan',
])

/** Kontrol visibilitas modal edit */
const showEditModal = ref<boolean>(false)

/** Data form untuk modal edit produk */
const formData = ref<ProductForm>({
  name: '',
  category: '',
  price: 0,
  status: 'Tersedia',
  imageUrl: null,
  imageFile: null,
  imagePreview: null,
  description: '',
})

// ==================== Fungsi Pembantu ====================

/**
 * Format angka menjadi format mata uang Rupiah
 * Contoh: 38000 → "Rp 38.000"
 */
function formatPrice(n: number): string {
  return `Rp ${n.toLocaleString('id-ID')}`
}

/**
 * Mendapatkan kelas CSS berdasarkan status ketersediaan produk
 * Tersedia → hijau, Habis → merah
 */
function getStatusClasses(status: ProductStatus): string {
  if (status === 'Tersedia') {
    return 'bg-green-50 text-green-700 border border-green-200'
  }
  return 'bg-red-50 text-red-700 border border-red-200'
}

/**
 * Mendapatkan kelas warna dot indikator status
 */
function getStatusDotClass(status: ProductStatus): string {
  return status === 'Tersedia' ? 'bg-green-500' : 'bg-red-500'
}

/**
 * Mendapatkan label deskriptif untuk status produk
 * Tersedia → "Tersedia (Bisa Dipesan)", Habis → "Habis / Tidak Tersedia"
 */
function getStatusLabel(status: ProductStatus): string {
  if (status === 'Tersedia') {
    return 'Tersedia (Bisa Dipesan)'
  }
  return 'Habis / Tidak Tersedia'
}

/** Navigasi kembali ke halaman daftar produk */
function goBack(): void {
  router.push('/admin/products')
}

/** Navigasi ke halaman manajemen resep */
function goToRecipes(): void {
  router.push('/admin/recipes')
}

/** Membuka modal edit dan mengisi form dengan data produk saat ini */
function openEditModal(): void {
  formData.value = {
    name: product.value.name,
    category: product.value.category,
    price: product.value.price,
    status: product.value.status,
    imageUrl: product.value.imageUrl,
    imageFile: null,
    imagePreview: product.value.imageUrl,
    description: product.value.description,
  }
  showEditModal.value = true
}

/** Menutup modal edit produk */
function closeEditModal(): void {
  showEditModal.value = false
}

/**
 * Menyimpan perubahan dari modal edit ke data produk
 * [API-READY] — Akan diganti dengan panggilan API PATCH/PUT ke backend
 */
function saveEdit(): void {
  // [API-READY] TODO: Kirim request PUT/PATCH ke endpoint update produk
  product.value = {
    ...product.value,
    name: formData.value.name,
    category: formData.value.category,
    price: formData.value.price,
    status: formData.value.status,
    imageUrl: formData.value.imagePreview ?? product.value.imageUrl,
    description: formData.value.description,
  }
  closeEditModal()
}

/** Menangani upload gambar dari input file */
function handleImageUpload(event: Event): void {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    formData.value.imageFile = file
    const reader = new FileReader()
    reader.onload = (e: ProgressEvent<FileReader>) => {
      formData.value.imagePreview = (e.target?.result as string) ?? null
    }
    reader.readAsDataURL(file)
  }
}

/** Menghapus gambar yang telah dipilih dari form */
function removeImage(): void {
  formData.value.imageFile = null
  formData.value.imagePreview = null
  formData.value.imageUrl = null
}

/**
 * Mengambil data detail produk dari server berdasarkan ID
 * [API-READY] — Akan diganti dengan panggilan API GET ke backend
 */
function fetchProductDetail(_id: string): void {
  // [API-READY] TODO: Panggil API GET /api/products/:id untuk mengambil detail produk
  // Saat ini menggunakan data dummy yang sudah diinisialisasi di state
  console.log(`Mengambil detail produk dengan ID: ${_id}`)
}

/** Computed: jumlah karakter deskripsi pada form */
const descriptionLength = computed<number>(() => formData.value.description.length)

// ==================== Lifecycle ====================

onMounted(() => {
  fetchProductDetail(productId.value)
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gray-50/50 p-6 lg:p-8">
      <!-- ============ Header Halaman ============ -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ product.name }}
            </h1>
            <!-- Badge status produk -->
            <span
              :class="getStatusClasses(product.status)"
              class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
            >
              <span :class="getStatusDotClass(product.status)" class="h-1.5 w-1.5 rounded-full" />
              {{ getStatusLabel(product.status) }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-500">
            Informasi lengkap rincian produk menu dan kebutuhan bahan baku resep.
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
            Edit Produk
          </button>
        </div>
      </div>

      <!-- ============ Kartu 1: Informasi Produk ============ -->
      <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Header kartu -->
        <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50">
            <component :is="icons.UtensilsCrossed" class="h-5 w-5 text-[#C2410C]" />
          </div>
          <h2 class="text-lg font-bold text-gray-900">Informasi Produk</h2>
        </div>

        <div class="p-6">
          <!-- Grid 2x2 informasi produk -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Nama Produk -->
            <div class="rounded-lg bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Nama Produk
              </p>
              <p class="text-sm font-bold text-gray-900">{{ product.name }}</p>
            </div>

            <!-- Kategori -->
            <div class="rounded-lg bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Kategori
              </p>
              <p class="text-sm font-bold text-gray-900">{{ product.category }}</p>
            </div>

            <!-- Harga Jual -->
            <div class="rounded-lg bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Harga Jual
              </p>
              <p class="text-sm font-bold text-[#C2410C]">{{ formatPrice(product.price) }}</p>
            </div>

            <!-- Status Ketersediaan -->
            <div class="rounded-lg bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Status Ketersediaan
              </p>
              <div class="mt-0.5">
                <span
                  :class="getStatusClasses(product.status)"
                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                >
                  <span
                    :class="getStatusDotClass(product.status)"
                    class="h-1.5 w-1.5 rounded-full"
                  />
                  {{ getStatusLabel(product.status) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Deskripsi Menu (lebar penuh) -->
          <div class="mt-4 rounded-lg bg-slate-50 p-4">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
              Deskripsi Menu
            </p>
            <p class="text-sm leading-relaxed text-gray-700">{{ product.description }}</p>
          </div>
        </div>
      </div>

      <!-- ============ Kartu 2: Ringkasan Resep ============ -->
      <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Header kartu dengan tombol aksi -->
        <div
          class="flex flex-col gap-3 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50">
              <component :is="icons.ClipboardCheck" class="h-5 w-5 text-[#C2410C]" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-gray-900">Ringkasan Resep</h2>
              <p class="text-sm text-gray-500">{{ product.recipeName }}</p>
            </div>
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
                  Kebutuhan Per Porsi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(ingredient, index) in product.ingredients"
                :key="ingredient.id"
                class="transition-colors hover:bg-gray-50/50"
              >
                <td class="px-6 py-3.5 text-sm text-gray-500">
                  {{ index + 1 }}
                </td>
                <td class="px-6 py-3.5 text-sm font-semibold text-gray-900">
                  {{ ingredient.name }}
                </td>
                <td class="px-6 py-3.5 text-right text-sm text-gray-600">
                  {{ ingredient.quantity }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ============ Modal Edit Produk (Teleport ke body) ============ -->
      <Teleport to="body">
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          @click.self="closeEditModal"
        >
          <div
            class="max-h-[90vh] w-full max-w-lg flex flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
          >
            <!-- Header modal -->
            <div class="shrink-0 border-b border-gray-100 px-6 py-5">
              <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50"
                  >
                    <component :is="icons.SquarePen" class="h-5 w-5 text-[#C2410C]" />
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="text-lg font-bold text-gray-900">Edit Produk</h3>
                      <span
                        class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                      >
                        SKU: {{ product.sku }}
                      </span>
                    </div>
                    <p class="mt-0.5 text-sm text-gray-500">
                      Perbarui informasi detail produk menu ini.
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
            <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
              <!-- Kategori Produk -->
              <div>
                <label
                  for="edit-category"
                  class="mb-1.5 block text-sm font-semibold text-gray-700"
                >
                  Kategori Produk <span class="text-red-500">*</span>
                </label>
                <select
                  id="edit-category"
                  v-model="formData.category"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                >
                  <option v-for="cat in categoryList" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
              </div>

              <!-- Nama Produk -->
              <div>
                <label
                  for="edit-name"
                  class="mb-1.5 block text-sm font-semibold text-gray-700"
                >
                  Nama Produk Menu <span class="text-red-500">*</span>
                </label>
                <input
                  id="edit-name"
                  v-model="formData.name"
                  type="text"
                  placeholder="Masukkan nama produk"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
              </div>

              <!-- Baris: Harga Jual & Status Ketersediaan -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Harga Jual -->
                <div>
                  <label
                    for="edit-price"
                    class="mb-1.5 block text-sm font-semibold text-gray-700"
                  >
                    Harga Jual (IDR) <span class="text-red-500">*</span>
                  </label>
                  <div
                    class="flex items-center rounded-lg border border-gray-200 bg-slate-50 px-3 transition focus-within:border-[#C2410C] focus-within:bg-white focus-within:ring-1 focus-within:ring-[#C2410C]/30"
                  >
                    <span class="shrink-0 text-sm font-bold text-[#C2410C]">Rp</span>
                    <input
                      id="edit-price"
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
                  <label
                    for="edit-status"
                    class="mb-1.5 block text-sm font-semibold text-gray-700"
                  >
                    Status Ketersediaan <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="edit-status"
                    v-model="formData.status"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  >
                    <option value="Tersedia">Tersedia (Bisa Dipesan di POS)</option>
                    <option value="Habis">Habis</option>
                  </select>
                  <p class="mt-1 text-xs font-medium text-emerald-600">✓ Aktif langsung di semua terminal kasir</p>
                </div>
              </div>

              <!-- Foto Tampilan Menu -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Foto Tampilan Menu
                </label>
                <!-- Preview jika ada foto -->
                <div v-if="formData.imagePreview" class="rounded-xl border border-gray-200 bg-slate-50/50 p-3">
                  <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-orange-50 border border-orange-100">
                        <img
                          :src="formData.imagePreview"
                          alt="Foto produk"
                          class="h-full w-full object-cover"
                          @error="(e) => ((e.target as HTMLElement).style.display = 'none')"
                        />
                        <UtensilsCrossed class="h-6 w-6 text-[#C2410C]" />
                      </div>
                      <div>
                        <p class="text-xs font-semibold text-gray-800">ayam_bakar_madu_hq.jpg</p>
                        <p class="text-[11px] text-gray-400">Ukuran file: 1.2 MB · 1200x1200 px</p>
                        <p class="text-[11px] text-gray-400">Format: PNG, JPG maks 2MB</p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
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
                <!-- Box upload jika belum ada foto -->
                <label
                  v-else
                  class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-6 transition hover:border-[#C2410C] hover:bg-orange-50/20"
                >
                  <Camera class="mb-2 h-7 w-7 text-gray-400" />
                  <p class="text-xs font-medium text-gray-600">Klik untuk upload foto produk menu</p>
                  <p class="mt-0.5 text-[11px] text-gray-400">Format PNG, JPG maks 2MB</p>
                  <input
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handleImageUpload"
                  />
                </label>
              </div>

              <!-- Deskripsi Produk -->
              <div>
                <div class="mb-1.5 flex items-center justify-between">
                  <label
                    for="edit-description"
                    class="text-sm font-semibold text-gray-700"
                  >
                    Deskripsi Produk
                  </label>
                  <span class="text-xs text-gray-400">Opsional</span>
                </div>
                <textarea
                  id="edit-description"
                  v-model="formData.description"
                  rows="3"
                  maxlength="250"
                  placeholder="Tuliskan keterangan komposisi, porsi, atau catatan produk..."
                  class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
                <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                  <span>Akan tampil pada nota ringkasan atau detail menu di tablet POS.</span>
                  <span>{{ descriptionLength }}/250</span>
                </div>
              </div>

              <!-- Kotak informasi sinkronisasi -->
              <div class="flex items-start gap-2.5 rounded-xl border border-green-200 bg-green-50/70 p-3">
                <Info class="mt-0.5 h-4 w-4 shrink-0 text-green-600" />
                <p class="text-xs leading-relaxed text-green-700">
                  Perubahan harga atau ketersediaan akan otomatis disinkronisasi dalam hitungan detik
                  ke seluruh terminal POS aktif di restoran tanpa perlu restart aplikasi kasir.
                </p>
              </div>
            </div>

            <!-- Footer modal -->
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
                  @click="saveEdit"
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
