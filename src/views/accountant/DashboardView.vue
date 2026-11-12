<script setup lang="ts">
import { ref, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  DollarSign,
  ClipboardCheck,
  Wallet,
  BarChart3,
  TrendingUp,
  ArrowRight,
  Info,
  ShoppingCart,
  CreditCard,
  Receipt,
  CircleDollarSign,
  ArrowUpRight,
  ArrowDownRight,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe data untuk kartu statistik ringkasan keuangan ─────────────────────
interface StatCard {
  title: string
  value: string
  subtitle: string
  icon: Component
  trend?: 'up' | 'down'
  trendValue?: string
}

// ─── Tipe data untuk kartu pintasan akses cepat ─────────────────────────────
interface QuickAccessItem {
  title: string
  subtitle: string
  icon: Component
  path: string
}

// ─── Tipe data untuk setiap baris transaksi pada tabel ──────────────────────
interface RecentTransaction {
  id: string
  description: string
  type: 'Penjualan' | 'Purchase Request' | 'Settlement'
  amount: number
  status: 'Selesai' | 'Pending' | 'Ditolak'
  date: string
  icon: Component
  iconBg: string
  iconColor: string
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Kartu statistik ringkasan keuangan ─────────────────────────────────────
// Berisi 4 metrik utama keuangan: pendapatan, PR pending, settlement, transaksi.
// `markRaw` digunakan pada ikon agar Vue tidak menjadikannya objek reaktif.
const statCards = ref<StatCard[]>([
  {
    title: 'Total Pendapatan',
    value: 'Rp 12.450.000',
    subtitle: 'Bulan ini',
    icon: markRaw(DollarSign),
    trend: 'up',
    trendValue: '+12.5%',
  },
  {
    title: 'PR Menunggu Approval',
    value: '8',
    subtitle: 'Purchase request',
    icon: markRaw(ClipboardCheck),
  },
  {
    title: 'Settlement Pending',
    value: '3',
    subtitle: 'Belum diselesaikan',
    icon: markRaw(Wallet),
  },
  {
    title: 'Total Transaksi',
    value: '156',
    subtitle: 'Transaksi bulan ini',
    icon: markRaw(Receipt),
    trend: 'up',
    trendValue: '+8.2%',
  },
])

// ─── Kartu pintasan akses cepat ke halaman akuntan ──────────────────────────
// Berisi 4 shortcut navigasi ke halaman utama akuntan.
const quickAccessItems = ref<QuickAccessItem[]>([
  {
    title: 'Approval PR',
    subtitle: 'Review purchase request',
    icon: markRaw(ClipboardCheck),
    path: '/accountant/approvals',
  },
  {
    title: 'Settlement',
    subtitle: 'Proses pembayaran',
    icon: markRaw(Wallet),
    path: '/accountant/settlements',
  },
  {
    title: 'Laporan Penjualan',
    subtitle: 'Rekap harian & bulanan',
    icon: markRaw(BarChart3),
    path: '/accountant/sales-report',
  },
  {
    title: 'Produk Terlaris',
    subtitle: 'Analisis penjualan',
    icon: markRaw(TrendingUp),
    path: '/accountant/best-selling',
  },
])

// ─── Daftar transaksi terbaru untuk tabel ───────────────────────────────────
// Data dummy 5 transaksi terakhir. Akan diganti data API Laravel nantinya.
const recentTransactions = ref<RecentTransaction[]>([
  {
    id: 'TRX-2024-001',
    description: 'Penjualan Meja 5 — 4 item',
    type: 'Penjualan',
    amount: 152_000,
    status: 'Selesai',
    date: '11 Nov 2024',
    icon: markRaw(ShoppingCart),
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    id: 'PR-2024-038',
    description: 'Pembelian Bahan — Daging Sapi 10kg',
    type: 'Purchase Request',
    amount: 1_500_000,
    status: 'Pending',
    date: '11 Nov 2024',
    icon: markRaw(ClipboardCheck),
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
  {
    id: 'STL-2024-012',
    description: 'Settlement Outlet Pusat — Shift Pagi',
    type: 'Settlement',
    amount: 3_250_000,
    status: 'Selesai',
    date: '10 Nov 2024',
    icon: markRaw(CreditCard),
    iconBg: 'bg-blue-50',
    iconColor: 'text-blue-500',
  },
  {
    id: 'TRX-2024-002',
    description: 'Penjualan Meja 12 — 6 item',
    type: 'Penjualan',
    amount: 248_000,
    status: 'Selesai',
    date: '10 Nov 2024',
    icon: markRaw(ShoppingCart),
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    id: 'PR-2024-037',
    description: 'Pembelian Bahan — Sayuran Segar',
    type: 'Purchase Request',
    amount: 450_000,
    status: 'Ditolak',
    date: '09 Nov 2024',
    icon: markRaw(ClipboardCheck),
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
])

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI HELPER
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Format angka menjadi format mata uang Rupiah Indonesia ─────────────────
// Contoh: 152000 → "Rp 152.000"
function formatPrice(value: number): string {
  return `Rp ${value.toLocaleString('id-ID')}`
}

// ─── Menghasilkan kelas CSS badge sesuai status transaksi ───────────────────
// Selesai → hijau, Pending → kuning/amber, Ditolak → merah
function statusClasses(status: RecentTransaction['status']): string {
  const map: Record<RecentTransaction['status'], string> = {
    Selesai: 'bg-green-50 text-green-700',
    Pending: 'bg-amber-50 text-amber-700',
    Ditolak: 'bg-red-50 text-red-700',
  }
  return map[status]
}

// ─── Fungsi untuk mengambil data dashboard dari API backend ─────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint seperti GET /api/accountant/dashboard lalu mengisi statCards
// dan recentTransactions dengan data dari server.
async function fetchDashboardData(): Promise<void> {
  // [API-READY] Contoh pemanggilan API:
  // const { data } = await api.get('/accountant/dashboard')
  // statCards.value = data.stats
  // recentTransactions.value = data.recentTransactions
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memanggil fetch data saat komponen pertama kali dimuat ─────────────────
onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Page Header ═══════════ -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard Akuntan</h1>
      <p class="mt-1 text-sm text-gray-500">Ringkasan data keuangan dan laporan POS Restaurant.</p>
    </div>

    <!-- ═══════════ Stat Cards ═══════════ -->
    <!-- Grid 4 kartu statistik responsif: 1 kolom (mobile) → 2 → 4 (desktop) -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="stat in statCards"
        :key="stat.title"
        class="flex items-start justify-between rounded-xl border border-gray-100 bg-white p-5"
      >
        <!-- Kiri: Judul, nilai utama, keterangan, dan tren (opsional) -->
        <div>
          <p class="text-xs font-medium text-gray-500">{{ stat.title }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ stat.value }}</p>
          <div class="mt-1 flex items-center gap-1.5">
            <span class="text-xs font-medium text-[#C2410C]">{{ stat.subtitle }}</span>
            <!-- Indikator tren naik/turun jika tersedia -->
            <span
              v-if="stat.trend"
              :class="[
                'flex items-center gap-0.5 text-[10px] font-semibold',
                stat.trend === 'up' ? 'text-emerald-600' : 'text-red-500',
              ]"
            >
              <ArrowUpRight v-if="stat.trend === 'up'" class="h-3 w-3" />
              <ArrowDownRight v-else class="h-3 w-3" />
              {{ stat.trendValue }}
            </span>
          </div>
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
      <p class="mt-0.5 text-sm text-gray-500">Pintasan langsung ke fitur utama akuntan.</p>

      <!-- Grid 4 kartu shortcut: 2 kolom (mobile) → 4 (desktop) -->
      <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <RouterLink
          v-for="item in quickAccessItems"
          :key="item.path"
          :to="item.path"
          class="group relative rounded-xl border border-gray-100 bg-white p-4 transition-all hover:border-gray-200 hover:shadow-sm"
        >
          <!-- Ikon dalam kotak krem di kiri atas -->
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

    <!-- ═══════════ Transaksi Terbaru ═══════════ -->
    <div class="mt-8">
      <!-- Header bagian: Judul di kiri, tombol lihat semua di kanan -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 class="text-lg font-bold text-gray-900">Transaksi Terbaru</h2>
          <p class="mt-0.5 text-sm text-gray-500">
            Aktivitas penjualan, purchase request, dan settlement terkini.
          </p>
        </div>
        <RouterLink
          to="/accountant/sales-report"
          class="flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
        >
          Lihat Laporan Lengkap
          <ArrowRight class="h-3.5 w-3.5" />
        </RouterLink>
      </div>

      <!-- Tabel transaksi dengan scroll horizontal pada layar kecil -->
      <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100 bg-white">
        <table class="w-full min-w-[700px]">
          <!-- Header kolom tabel -->
          <thead>
            <tr class="border-b border-gray-100">
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                ID Transaksi
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Deskripsi
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Tipe
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Jumlah
              </th>
              <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">
                Status
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Tanggal
              </th>
            </tr>
          </thead>

          <!-- Baris data transaksi, dirender dengan v-for -->
          <tbody>
            <tr
              v-for="(tx, index) in recentTransactions"
              :key="tx.id"
              :class="index < recentTransactions.length - 1 ? 'border-b border-gray-50' : ''"
            >
              <!-- Kolom ID transaksi dengan ikon berwarna -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                    :class="[tx.iconBg]"
                  >
                    <component :is="tx.icon" class="h-4 w-4" :class="[tx.iconColor]" />
                  </div>
                  <span class="text-sm font-semibold text-gray-800">{{ tx.id }}</span>
                </div>
              </td>

              <!-- Kolom deskripsi -->
              <td class="px-5 py-4 text-sm text-gray-600">{{ tx.description }}</td>

              <!-- Kolom tipe transaksi -->
              <td class="px-5 py-4 text-sm text-gray-500">{{ tx.type }}</td>

              <!-- Kolom jumlah dalam format Rupiah -->
              <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">
                {{ formatPrice(tx.amount) }}
              </td>

              <!-- Kolom status dengan badge berwarna -->
              <td class="px-5 py-4 text-center">
                <span
                  :class="[
                    'inline-block rounded-full px-3 py-0.5 text-xs font-medium',
                    statusClasses(tx.status),
                  ]"
                >
                  {{ tx.status }}
                </span>
              </td>

              <!-- Kolom tanggal -->
              <td class="px-5 py-4 text-right text-sm text-gray-400">{{ tx.date }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════ Catatan Kaki ═══════════ -->
    <div class="mt-5 flex items-center gap-2 text-xs text-gray-400">
      <Info class="h-3.5 w-3.5 shrink-0" />
      <span>* Menampilkan ringkasan data keuangan dan metrik simulasi sistem POS restoran.</span>
    </div>
  </DashboardLayout>
</template>
