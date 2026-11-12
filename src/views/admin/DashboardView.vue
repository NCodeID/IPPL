<script setup lang="ts">
import { ref, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  ClipboardList,
  Tag,
  Armchair,
  Users,
  ScrollText,
  UserCog,
  ArrowRight,
  Info,
  Soup,
  CookingPot,
  Coffee,
  Beef,
  GlassWater,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe data untuk setiap kartu statistik ringkasan ───────────────────────
// Menampilkan metrik utama (total produk, kategori, meja, pengguna)
interface StatCard {
  title: string
  value: number
  subtitle: string
  icon: Component
}

// ─── Tipe data untuk kartu pintasan akses cepat ─────────────────────────────
// Menampilkan shortcut navigasi ke halaman manajemen
interface QuickAccessItem {
  title: string
  subtitle: string
  icon: Component
  path: string
}

// ─── Tipe data untuk setiap baris produk di tabel "Produk Terbaru" ──────────
// Berisi informasi produk beserta konfigurasi warna ikon
interface RecentProduct {
  name: string
  category: string
  price: number
  status: 'Tersedia' | 'Habis'
  icon: Component
  iconBg: string
  iconColor: string
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Kartu statistik ringkasan dashboard ────────────────────────────────────
// Berisi 4 metrik utama yang ditampilkan di bagian atas halaman.
// `markRaw` digunakan pada ikon agar Vue tidak menjadikannya objek reaktif.
const statCards = ref<StatCard[]>([
  {
    title: 'Total Produk',
    value: 84,
    subtitle: 'Aktif di katalog',
    icon: markRaw(ClipboardList),
  },
  {
    title: 'Total Kategori',
    value: 12,
    subtitle: 'Kategori aktif',
    icon: markRaw(Tag),
  },
  {
    title: 'Total Meja',
    value: 28,
    subtitle: 'Meja makan dine-in',
    icon: markRaw(Armchair),
  },
  {
    title: 'Total Pengguna',
    value: 6,
    subtitle: 'Admin, akuntan, Kasir, runner, gudang, dan dapur',
    icon: markRaw(Users),
  },
])

// ─── Kartu pintasan akses cepat ─────────────────────────────────────────────
// Berisi 5 shortcut navigasi ke halaman CRUD manajemen.
// Setiap item memiliki ikon, judul, deskripsi, dan path tujuan.
const quickAccessItems = ref<QuickAccessItem[]>([
  {
    title: 'Kelola Produk',
    subtitle: 'Katalog harga & stok',
    icon: markRaw(ClipboardList),
    path: '/admin/products',
  },
  {
    title: 'Kelola Kategori',
    subtitle: 'Grup menu makanan',
    icon: markRaw(Tag),
    path: '/admin/categories',
  },
  {
    title: 'Kelola Meja',
    subtitle: 'Layout meja & status',
    icon: markRaw(Armchair),
    path: '/admin/tables',
  },
  {
    title: 'Kelola Resep',
    subtitle: 'Bahan baku & porsi',
    icon: markRaw(ScrollText),
    path: '/admin/recipes',
  },
  {
    title: 'Kelola Pengguna',
    subtitle: 'Akses staf & kasir',
    icon: markRaw(UserCog),
    path: '/admin/users',
  },
])

// ─── Daftar 5 produk terbaru untuk tabel ────────────────────────────────────
// Data dummy yang akan diganti dengan data dari API Laravel nantinya.
// Setiap produk memiliki ikon berwarna unik untuk identifikasi visual.
const recentProducts = ref<RecentProduct[]>([
  {
    name: 'Ayam Bakar Madu Spesial',
    category: 'Makanan Utama',
    price: 38_000,
    status: 'Tersedia',
    icon: markRaw(Soup),
    iconBg: 'bg-red-50',
    iconColor: 'text-red-400',
  },
  {
    name: 'Nasi Goreng Kampung',
    category: 'Makanan Utama',
    price: 28_000,
    status: 'Tersedia',
    icon: markRaw(CookingPot),
    iconBg: 'bg-orange-50',
    iconColor: 'text-orange-400',
  },
  {
    name: 'Es Kopi Susu Aren',
    category: 'Minuman',
    price: 22_000,
    status: 'Tersedia',
    icon: markRaw(Coffee),
    iconBg: 'bg-teal-50',
    iconColor: 'text-teal-400',
  },
  {
    name: 'Sate Ayam Madura',
    category: 'Makanan Utama',
    price: 35_000,
    status: 'Tersedia',
    icon: markRaw(Beef),
    iconBg: 'bg-purple-50',
    iconColor: 'text-purple-400',
  },
  {
    name: 'Jus Alpukat Kocok',
    category: 'Minuman',
    price: 20_000,
    status: 'Habis',
    icon: markRaw(GlassWater),
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-400',
  },
])

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI HELPER
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Format angka menjadi format mata uang Rupiah Indonesia ─────────────────
// Contoh: 38000 → "Rp 38.000"
function formatPrice(value: number): string {
  return `Rp ${value.toLocaleString('id-ID')}`
}

// ─── Fungsi untuk mengambil data dashboard dari API backend ─────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint seperti GET /api/admin/dashboard lalu mengisi statCards,
// quickAccessItems, dan recentProducts dengan data dari server.
async function fetchDashboardData(): Promise<void> {
  // [API-READY] Contoh pemanggilan API:
  // const { data } = await api.get('/admin/dashboard')
  // statCards.value = data.stats.map(s => ({ ...s, icon: iconMap[s.iconKey] }))
  // recentProducts.value = data.recentProducts
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memanggil fetch data saat komponen pertama kali dimuat ─────────────────
// Menginisialisasi dashboard dengan data terbaru dari server
onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Page Header ═══════════ -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard Admin</h1>
      <p class="mt-1 text-sm text-gray-500">Ringkasan pengelolaan master data POS Restaurant.</p>
    </div>

    <!-- ═══════════ Stat Cards ═══════════ -->
    <!-- Grid 4 kartu statistik responsif: 1 kolom (mobile) → 2 → 4 (desktop) -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="stat in statCards"
        :key="stat.title"
        class="flex items-start justify-between rounded-xl border border-gray-100 bg-white p-5"
      >
        <!-- Kiri: Judul, angka besar, dan keterangan -->
        <div>
          <p class="text-xs font-medium text-gray-500">{{ stat.title }}</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stat.value }}</p>
          <p class="mt-1 text-xs font-medium text-[#C2410C]">{{ stat.subtitle }}</p>
        </div>

        <!-- Kanan: Ikon dalam kotak krem/oranye lembut -->
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50">
          <component :is="stat.icon" class="h-5 w-5 text-[#C2410C]" />
        </div>
      </div>
    </div>

    <!-- ═══════════ Akses Cepat ═══════════ -->
    <div class="mt-8">
      <h2 class="text-lg font-bold text-gray-900">Akses Cepat</h2>
      <p class="mt-0.5 text-sm text-gray-500">Pintasan langsung ke menu manajemen utama restoran.</p>

      <!-- Grid 5 kartu shortcut: 2 kolom (mobile) → 3 → 5 (desktop) -->
      <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
        <RouterLink
          v-for="item in quickAccessItems"
          :key="item.path"
          :to="item.path"
          class="group relative rounded-xl border border-gray-100 bg-white p-4 transition-all hover:border-gray-200 hover:shadow-sm"
        >
          <!-- Ikon dalam kotak krem/oranye di kiri atas -->
          <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50">
            <component :is="item.icon" class="h-5 w-5 text-[#C2410C]" />
          </div>

          <!-- Panah kecil di pojok kanan atas -->
          <ArrowRight
            class="absolute right-3 top-4 h-4 w-4 text-gray-300 transition-colors group-hover:text-[#C2410C]"
          />

          <!-- Judul dan deskripsi singkat -->
          <p class="text-sm font-semibold text-gray-800">{{ item.title }}</p>
          <p class="mt-0.5 text-xs text-gray-400">{{ item.subtitle }}</p>
        </RouterLink>
      </div>
    </div>

    <!-- ═══════════ Produk Terbaru ═══════════ -->
    <div class="mt-8">
      <!-- Header bagian: Judul di kiri, tombol lihat semua di kanan -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 class="text-lg font-bold text-gray-900">Produk Terbaru</h2>
          <p class="mt-0.5 text-sm text-gray-500">
            Daftar produk yang baru diperbarui atau ditambahkan ke sistem.
          </p>
        </div>
        <RouterLink
          to="/admin/products"
          class="flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
        >
          Lihat Semua Produk
          <ArrowRight class="h-3.5 w-3.5" />
        </RouterLink>
      </div>

      <!-- Tabel produk dengan scroll horizontal pada layar kecil -->
      <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100 bg-white">
        <table class="w-full min-w-[600px]">
          <!-- Header kolom tabel -->
          <thead>
            <tr class="border-b border-gray-100">
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Nama Produk
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Kategori
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Harga
              </th>
              <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">
                Status
              </th>
            </tr>
          </thead>

          <!-- Baris data produk, dirender dengan v-for -->
          <tbody>
            <tr
              v-for="(product, index) in recentProducts"
              :key="product.name"
              :class="index < recentProducts.length - 1 ? 'border-b border-gray-50' : ''"
            >
              <!-- Kolom nama produk dengan ikon berwarna -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                    :class="[product.iconBg]"
                  >
                    <component :is="product.icon" class="h-4 w-4" :class="[product.iconColor]" />
                  </div>
                  <span class="text-sm font-semibold text-gray-800">{{ product.name }}</span>
                </div>
              </td>

              <!-- Kolom kategori -->
              <td class="px-5 py-4 text-sm text-gray-500">
                {{ product.category }}
              </td>

              <!-- Kolom harga dalam format Rupiah -->
              <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">
                {{ formatPrice(product.price) }}
              </td>

              <!-- Kolom status dengan badge berwarna -->
              <td class="px-5 py-4 text-center">
                <span
                  :class="[
                    'inline-block rounded-full px-3 py-0.5 text-xs font-medium',
                    product.status === 'Tersedia'
                      ? 'bg-green-50 text-green-700'
                      : 'bg-red-50 text-red-700',
                  ]"
                >
                  {{ product.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════ Catatan Kaki ═══════════ -->
    <div class="mt-5 flex items-center gap-2 text-xs text-gray-400">
      <Info class="h-3.5 w-3.5 shrink-0" />
      <span>* Menampilkan ringkasan data produk dan metrik simulasi sistem POS restoran.</span>
    </div>
  </DashboardLayout>
</template>
