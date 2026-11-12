<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { Home, Bell } from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()

// ─── Peta path → nama halaman untuk breadcrumb ─────────────────────────────
// Digunakan untuk menampilkan label halaman aktif di sisi kiri header
const pageNameMap: Record<string, string> = {
  '/admin/dashboard': 'Dashboard',
  '/admin/categories': 'Manajemen Kategori',
  '/admin/tables': 'Manajemen Meja',
  '/admin/products': 'Manajemen Produk',
  '/admin/recipes': 'Manajemen Resep',
  '/admin/users': 'Manajemen Pengguna',
  '/accountant/dashboard': 'Dashboard',
  '/accountant/approvals': 'Approval Purchase Request',
  '/accountant/settlements': 'Settlement',
  '/accountant/sales-report': 'Laporan Penjualan',
  '/accountant/best-selling': 'Produk Terlaris',
}

// ─── Peta pola path dinamis → nama halaman untuk breadcrumb ─────────────────
// Digunakan untuk route dengan parameter seperti /admin/categories/:id
const dynamicPagePatterns: { pattern: RegExp; name: string; parent?: string }[] = [
  { pattern: /^\/admin\/categories\/[^/]+$/, name: 'Detail Kategori', parent: 'Manajemen Kategori' },
]

// ─── Nama halaman aktif saat ini, diambil dari peta path ────────────────────
// Mendukung path statis maupun path dinamis dengan parameter.
// Fallback ke 'Dashboard' jika path tidak ditemukan di peta maupun pola.
const currentPageName = computed<string>(() => {
  // Cek di peta statis terlebih dahulu
  const staticName = pageNameMap[route.path]
  if (staticName) return staticName

  // Cek di pola path dinamis
  for (const entry of dynamicPagePatterns) {
    if (entry.pattern.test(route.path)) return entry.name
  }

  return 'Dashboard'
})

// ─── Nama halaman induk untuk breadcrumb bertingkat ─────────────────────────
// Jika halaman saat ini adalah halaman detail, tampilkan parent sebagai breadcrumb kedua
const parentPageName = computed<string | null>(() => {
  for (const entry of dynamicPagePatterns) {
    if (entry.pattern.test(route.path) && entry.parent) return entry.parent
  }
  return null
})

// ─── Path halaman induk untuk link breadcrumb ───────────────────────────────
const parentPagePath = computed<string | null>(() => {
  if (!parentPageName.value) return null
  // Cari path statis yang sesuai dengan nama parent
  for (const [path, name] of Object.entries(pageNameMap)) {
    if (name === parentPageName.value) return path
  }
  return null
})

// ─── Teks sapaan dinamis berdasarkan role pengguna ──────────────────────────
// Menampilkan "Halo, Administrator" untuk admin atau "Halo, Akuntan" untuk accountant
const greeting = computed<string>(() =>
  authStore.userRole === 'admin' ? 'Halo, Administrator' : 'Halo, Akuntan',
)

// ─── Inisial avatar di sisi kanan header ────────────────────────────────────
const avatarInitials = computed<string>(() =>
  authStore.userRole === 'admin' ? 'AD' : 'AK',
)
</script>

<template>
  <header class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6">
    <!-- ===== Breadcrumb (kiri) ===== -->
    <!-- Menampilkan ikon rumah diikuti nama halaman yang sedang aktif -->
    <!-- Mendukung breadcrumb bertingkat: Home / Parent / Current -->
    <nav class="flex items-center gap-2 text-sm text-gray-500">
      <Home class="h-4 w-4 text-gray-400" />
      <!-- Breadcrumb parent (jika ada) -->
      <template v-if="parentPageName && parentPagePath">
        <span class="text-gray-300">/</span>
        <router-link :to="parentPagePath" class="font-medium text-gray-500 transition-colors hover:text-[#C2410C]">
          {{ parentPageName }}
        </router-link>
      </template>
      <span class="text-gray-300">/</span>
      <span class="font-medium text-gray-700">{{ currentPageName }}</span>
    </nav>

    <!-- ===== Right Section (kanan) ===== -->
    <div class="flex items-center gap-4">
      <!-- Ikon notifikasi lonceng dengan titik merah penanda notifikasi baru -->
      <button
        type="button"
        class="relative rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700"
        title="Notifikasi"
      >
        <Bell class="h-5 w-5" />
        <!-- Titik merah kecil di pojok kanan atas ikon lonceng -->
        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
      </button>

      <!-- Pemisah garis vertikal tipis antara notifikasi dan info pengguna -->
      <div class="h-8 w-px bg-gray-200"></div>

      <!-- Info sapaan dan status shift pengguna -->
      <div class="text-right">
        <p class="text-sm font-semibold text-gray-800">{{ greeting }}</p>
        <p class="text-[11px] text-emerald-600">• Shift Aktif · Outlet Pusat</p>
      </div>

      <!-- Avatar kotak inisial pengguna dengan warna brand -->
      <div
        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#C2410C] text-xs font-bold text-white shadow-sm"
      >
        {{ avatarInitials }}
      </div>
    </div>
  </header>
</template>

