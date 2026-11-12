<script setup lang="ts">
import { computed, markRaw } from 'vue'
import type { Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import {
  UtensilsCrossed,
  LayoutGrid,
  Tag,
  Armchair,
  Package,
  ScrollText,
  UserCog,
  ClipboardCheck,
  Wallet,
  BarChart3,
  TrendingUp,
  LogOut,
} from 'lucide-vue-next'

// ─── Tipe data untuk setiap item menu navigasi ──────────────────────────────
interface MenuItem {
  label: string
  path: string
  icon: Component
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// ─── Label subtitle portal yang tampil di bawah judul brand ─────────────────
// Berubah otomatis sesuai role: "PORTAL ADMIN" atau "PORTAL AKUNTAN"
const portalLabel = computed<string>(() =>
  authStore.userRole === 'admin' ? 'PORTAL ADMIN' : 'PORTAL AKUNTAN',
)

// ─── Inisial avatar profil pengguna di bagian bawah sidebar ─────────────────
// "AD" untuk admin, "AK" untuk accountant
const avatarInitials = computed<string>(() =>
  authStore.userRole === 'admin' ? 'AD' : 'AK',
)

// ─── Nama tampilan pengguna dengan fallback default per role ────────────────
const displayName = computed<string>(() =>
  authStore.user?.name ?? (authStore.userRole === 'admin' ? 'Admin Restoran' : 'Ratna Sari'),
)

// ─── Email pengguna dari store, dengan fallback default per role ────────────
const displayEmail = computed<string>(() =>
  authStore.user?.email ??
  (authStore.userRole === 'admin' ? 'admin@restaurant.com' : 'akuntan@restaurant.com'),
)

// ─── Daftar menu navigasi dinamis berdasarkan role pengguna ─────────────────
// Menggunakan `markRaw` pada ikon agar Vue tidak membuat komponen menjadi
// objek reaktif (mencegah warning di konsol)
const menuItems = computed<MenuItem[]>(() => {
  if (authStore.userRole === 'admin') {
    return [
      { label: 'Dashboard', path: '/admin/dashboard', icon: markRaw(LayoutGrid) },
      { label: 'Manajemen Kategori', path: '/admin/categories', icon: markRaw(Tag) },
      { label: 'Manajemen Meja', path: '/admin/tables', icon: markRaw(Armchair) },
      { label: 'Manajemen Produk', path: '/admin/products', icon: markRaw(Package) },
      { label: 'Manajemen Resep', path: '/admin/recipes', icon: markRaw(ScrollText) },
      { label: 'Manajemen Pengguna', path: '/admin/users', icon: markRaw(UserCog) },
    ]
  }

  // Menu untuk role accountant
  return [
    { label: 'Dashboard', path: '/accountant/dashboard', icon: markRaw(LayoutGrid) },
    { label: 'Approval Purchase Request', path: '/accountant/approvals', icon: markRaw(ClipboardCheck) },
    { label: 'Settlement', path: '/accountant/settlements', icon: markRaw(Wallet) },
    { label: 'Laporan Penjualan', path: '/accountant/sales-report', icon: markRaw(BarChart3) },
    { label: 'Produk Terlaris', path: '/accountant/best-selling', icon: markRaw(TrendingUp) },
  ]
})

// ─── Mengecek apakah suatu path menu sedang aktif (cocok dengan route saat ini)
function isActive(path: string): boolean {
  return route.path === path
}

// ─── Aksi logout: hapus sesi dari store lalu redirect ke halaman login ──────
function handleLogout(): void {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <aside class="flex h-screen w-64 shrink-0 flex-col border-r border-gray-200 bg-white">
    <!-- ===== Brand Section ===== -->
    <!-- Logo, judul aplikasi, dan label portal di bagian atas sidebar -->
    <div class="flex items-center gap-3 px-5 py-5">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#C2410C] shadow-sm">
        <UtensilsCrossed class="h-5 w-5 text-white" />
      </div>
      <div>
        <h1 class="text-sm font-bold leading-tight text-gray-900">POS Restaurant</h1>
        <span class="text-[10px] font-bold tracking-wider text-[#C2410C]">{{ portalLabel }}</span>
      </div>
    </div>

    <!-- ===== Navigation ===== -->
    <!-- Daftar menu navigasi dengan scroll jika item terlalu banyak -->
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 pt-2">
      <RouterLink
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        :class="[
          'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
          isActive(item.path)
            ? 'bg-[#C2410C] text-white shadow-sm'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
        ]"
      >
        <component
          :is="item.icon"
          class="h-[18px] w-[18px] shrink-0"
          :class="isActive(item.path) ? 'text-white' : 'text-gray-400'"
        />
        <span>{{ item.label }}</span>
      </RouterLink>
    </nav>

    <!-- ===== Profile Footer ===== -->
    <!-- Info profil pengguna dan tombol keluar di bagian paling bawah sidebar -->
    <div class="border-t border-gray-200 px-4 py-4">
      <div class="flex items-center gap-3">
        <!-- Avatar inisial dengan warna brand -->
        <div
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#C2410C] text-xs font-bold text-white"
        >
          {{ avatarInitials }}
        </div>

        <!-- Nama dan email pengguna -->
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-gray-800">{{ displayName }}</p>
          <p class="truncate text-xs text-gray-500">{{ displayEmail }}</p>
        </div>

        <!-- Tombol keluar (logout) -->
        <button
          type="button"
          class="flex shrink-0 items-center gap-1 text-xs text-gray-400 transition-colors hover:text-red-600"
          title="Keluar"
          @click="handleLogout"
        >
          <LogOut class="h-4 w-4" />
          <span class="hidden lg:inline">Keluar</span>
        </button>
      </div>
    </div>
  </aside>
</template>

