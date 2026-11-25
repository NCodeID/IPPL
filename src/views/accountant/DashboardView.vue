<script setup lang="ts">
import { ref, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  FileText,
  ClipboardCheck,
  DollarSign,
  Star,
  ArrowRight,
  Info,
  Calendar,
  TrendingUp,
  TrendingDown,
  CheckCircle2,
  Clock,
  ShoppingBag,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe data untuk kartu statistik ringkasan di bagian atas ────────────────
interface StatCard {
  title: string
  badgeLabel: string
  badgeColor: string // Kelas Tailwind untuk warna badge
  value: string
  subtitle: string
  linkText: string
  linkPath: string
  icon: Component
  iconBg: string
  iconColor: string
}

// ─── Tipe data untuk baris tabel Purchase Request menunggu persetujuan ───────
interface PurchaseRequest {
  nomorPengajuan: string
  tanggalPengajuan: string
  pemohonNama: string
  pemohonInisial: string
  pemohonBg: string
  jumlahItem: string
  estimasiTotal: string
  status: string
}

// ─── Tipe data untuk baris tabel Settlement yang perlu diselesaikan ──────────
interface SettlementItem {
  nomorPengajuan: string
  deskripsi: string
  estimasiPembelian: string
  totalAktual: string
  selisih: string
  selisihType: 'over' | 'under' // over = lebih besar dari estimasi, under = lebih kecil
  status: string
}

// ─── Tipe data untuk produk terlaris ─────────────────────────────────────────
interface TopProduct {
  rank: number
  nama: string
  jumlahTerjual: string
  omzet: string
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tanggal dashboard (simulasi hari ini) ───────────────────────────────────
const dashboardDate = ref<string>('24 Okt 2024')

// ─── 4 Kartu Statistik Atas ─────────────────────────────────────────────────
// Berisi ringkasan PR menunggu persetujuan, PR menunggu settlement,
// penjualan bersih, dan produk terlaris.
// `markRaw` digunakan agar komponen ikon tidak dijadikan objek reaktif Vue.
const statCards = ref<StatCard[]>([
  {
    title: 'Purchase Request Menunggu Persetujuan',
    badgeLabel: 'Perlu Tindakan',
    badgeColor: 'bg-amber-50 text-amber-700',
    value: '3 berkas',
    subtitle: 'Pengajuan perlu ditinjau',
    linkText: 'Lihat Pengajuan',
    linkPath: '/accountant/approvals',
    icon: markRaw(FileText),
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    title: 'Purchase Request Menunggu Settlement',
    badgeLabel: 'Realisasi',
    badgeColor: 'bg-blue-50 text-blue-700',
    value: '2 faktur',
    subtitle: 'Menunggu penyelesaian selisih',
    linkText: 'Lihat Settlement',
    linkPath: '/accountant/settlements',
    icon: markRaw(ClipboardCheck),
    iconBg: 'bg-blue-50',
    iconColor: 'text-blue-600',
  },
  {
    title: 'Penjualan Bersih',
    badgeLabel: 'Omzet Bersih',
    badgeColor: 'bg-green-50 text-green-700',
    value: 'Rp 14.850.000',
    subtitle: 'Hari ini (24 Okt 2024)',
    linkText: 'Lihat Laporan',
    linkPath: '/accountant/sales-report',
    icon: markRaw(DollarSign),
    iconBg: 'bg-green-50',
    iconColor: 'text-green-600',
  },
  {
    title: 'Produk Terlaris',
    badgeLabel: 'Top Qty',
    badgeColor: 'bg-orange-50 text-[#C2410C]',
    value: 'Es Kopi Susu Aren',
    subtitle: '52 gelas terjual hari ini',
    linkText: 'Lihat Produk',
    linkPath: '/accountant/best-selling',
    icon: markRaw(Star),
    iconBg: 'bg-orange-50',
    iconColor: 'text-[#C2410C]',
  },
])

// ─── Data tabel Purchase Request Menunggu Persetujuan ───────────────────────
// 3 baris dummy sesuai desain Figma. Akan diganti data API nantinya.
const purchaseRequests = ref<PurchaseRequest[]>([
  {
    nomorPengajuan: 'PR-2024-10-001',
    tanggalPengajuan: '24 Okt 2024, 08:30 WIB',
    pemohonNama: 'Gudang - Doni Kurniawan',
    pemohonInisial: 'DK',
    pemohonBg: 'bg-blue-100 text-blue-700',
    jumlahItem: '5 Item Bahan',
    estimasiTotal: 'Rp 2.450.000',
    status: 'Menunggu Persetujuan',
  },
  {
    nomorPengajuan: 'PR-2024-10-002',
    tanggalPengajuan: '24 Okt 2024, 09:15 WIB',
    pemohonNama: 'Gudang - Doni Kurniawan',
    pemohonInisial: 'DK',
    pemohonBg: 'bg-blue-100 text-blue-700',
    jumlahItem: '3 Item Bahan',
    estimasiTotal: 'Rp 1.120.000',
    status: 'Menunggu Persetujuan',
  },
  {
    nomorPengajuan: 'PR-2024-10-003',
    tanggalPengajuan: '23 Okt 2024, 16:40 WIB',
    pemohonNama: 'Dapur - Siti Salimah',
    pemohonInisial: 'SS',
    pemohonBg: 'bg-purple-100 text-purple-700',
    jumlahItem: '8 Item Bahan',
    estimasiTotal: 'Rp 3.890.000',
    status: 'Menunggu Persetujuan',
  },
])

// ─── Data tabel Settlement yang Perlu Diselesaikan ──────────────────────────
// 2 baris dummy sesuai desain Figma. Akan diganti data API nantinya.
const settlementItems = ref<SettlementItem[]>([
  {
    nomorPengajuan: 'PR-2024-10-004',
    deskripsi: 'Belanja Bahan Segar Pasar',
    estimasiPembelian: 'Rp 1.500.000',
    totalAktual: 'Rp 1.620.000',
    selisih: '+Rp 120.000',
    selisihType: 'over',
    status: 'Menunggu Settlement (pending_settlement)',
  },
  {
    nomorPengajuan: 'PR-2024-10-005',
    deskripsi: 'Kebutuhan Minyak & Bumbu Kemasan',
    estimasiPembelian: 'Rp 2.200.000',
    totalAktual: 'Rp 2.150.000',
    selisih: '-Rp 50.000',
    selisihType: 'under',
    status: 'Menunggu Settlement (pending_settlement)',
  },
])

// ─── Data ringkasan penjualan ───────────────────────────────────────────────
const totalPenjualanBersih = ref<string>('Rp 14.850.000')
const totalTransaksiLunas = ref<string>('86 Pesanan')
const rataRataTransaksi = ref<string>('Rp 172.674')

// ─── Data top 3 produk terlaris hari ini ────────────────────────────────────
const topProducts = ref<TopProduct[]>([
  {
    rank: 1,
    nama: 'Es Kopi Susu Aren',
    jumlahTerjual: '52 Gelas Terjual',
    omzet: 'Rp 1.144.000',
  },
  {
    rank: 2,
    nama: 'Ayam Bakar Madu Spesial',
    jumlahTerjual: '48 Porsi Terjual',
    omzet: 'Rp 1.824.000',
  },
  {
    rank: 3,
    nama: 'Nasi Goreng Kampung',
    jumlahTerjual: '36 Porsi Terjual',
    omzet: 'Rp 1.008.000',
  },
])

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Mengambil data dashboard akuntan dari API backend ──────────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint GET /api/accountant/dashboard dan mengisi seluruh state di atas
// dengan data dari server.
async function fetchAccountantDashboard(): Promise<void> {
  // [API-READY] Contoh pemanggilan API:
  // const { data } = await api.get('/accountant/dashboard')
  // statCards.value = data.statCards
  // purchaseRequests.value = data.purchaseRequests
  // settlementItems.value = data.settlements
  // topProducts.value = data.topProducts
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memanggil fetch data saat komponen pertama kali dimuat ─────────────────
onMounted(() => {
  fetchAccountantDashboard()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Header Halaman ═══════════ -->
    <!-- Judul, badge "Data Contoh", subjudul, tanggal, dan badge sinkronisasi -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold text-gray-900">Dashboard Akuntan</h1>
        </div>
        <p class="mt-1 text-sm text-gray-500">
          Pantau pengajuan pembelian, penyelesaian settlement, dan laporan keuangan restoran secara
          terpadu.
        </p>
      </div>

      <!-- Tanggal dan badge sinkronisasi -->
      <div class="flex items-center gap-3">
        <div
          class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"
        >
          <Calendar class="h-4 w-4 text-gray-400" />
          {{ dashboardDate }}
        </div>
        <div class="flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-2 text-xs font-medium text-green-700">
          <span class="h-2 w-2 rounded-full bg-green-500"></span>
          Sinkronisasi Real-time
        </div>
      </div>
    </div>

    <!-- ═══════════ 4 Kartu Statistik Atas ═══════════ -->
    <!-- Grid 4 kolom responsif: 1 (mobile) → 2 (tablet) → 4 (desktop) -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="stat in statCards"
        :key="stat.title"
        class="flex flex-col justify-between rounded-xl border border-gray-100 bg-white p-5"
      >
        <!-- Baris atas: Ikon dan badge label -->
        <div class="mb-3 flex items-start justify-between">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl"
            :class="stat.iconBg"
          >
            <component :is="stat.icon" class="h-5 w-5" :class="stat.iconColor" />
          </div>
          <span
            :class="['rounded-full px-2.5 py-0.5 text-[10px] font-semibold', stat.badgeColor]"
          >
            {{ stat.badgeLabel }}
          </span>
        </div>

        <!-- Judul kartu -->
        <p class="text-xs font-medium text-gray-500">{{ stat.title }}</p>

        <!-- Nilai utama -->
        <p class="mt-1 text-xl font-bold text-gray-900">{{ stat.value }}</p>

        <!-- Subjudul -->
        <p class="mt-0.5 text-xs text-gray-400">{{ stat.subtitle }}</p>

        <!-- Tautan navigasi oranye di bawah -->
        <RouterLink
          :to="stat.linkPath"
          class="mt-3 flex items-center gap-1 text-xs font-semibold text-[#C2410C] transition-colors hover:text-[#B45309]"
        >
          {{ stat.linkText }}
          <ArrowRight class="h-3.5 w-3.5" />
        </RouterLink>
      </div>
    </div>

    <!-- ═══════════ Tabel 1: Purchase Request Menunggu Persetujuan ═══════════ -->
    <div class="mb-6 rounded-xl border border-gray-100 bg-white">
      <!-- Header kartu tabel -->
      <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <!-- Ikon dokumen -->
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50">
            <FileText class="h-5 w-5 text-amber-600" />
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-base font-bold text-gray-900">
                Purchase Request Menunggu Persetujuan
              </h2>
              <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-[#C2410C]">
                {{ purchaseRequests.length }} Pengajuan
              </span>
            </div>
            <p class="mt-0.5 text-xs text-gray-500">
              Tinjau kesesuaian anggaran pengajuan sebelum disetujui ke tahap pembelian
            </p>
          </div>
        </div>
        <!-- Tombol buka semua -->
        <RouterLink
          to="/accountant/approvals"
          class="flex shrink-0 items-center gap-1.5 text-sm font-semibold text-[#C2410C] transition-colors hover:text-[#B45309]"
        >
          Buka Semua Pengajuan
          <ArrowRight class="h-4 w-4" />
        </RouterLink>
      </div>

      <!-- Tabel data -->
      <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Nomor Pengajuan
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Tanggal Pengajuan
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Pemohon
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Jumlah Item
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Estimasi Total
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Status
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(pr, idx) in purchaseRequests"
              :key="pr.nomorPengajuan"
              :class="idx < purchaseRequests.length - 1 ? 'border-b border-gray-50' : ''"
            >
              <td class="px-5 py-4 text-sm font-semibold text-gray-800">
                {{ pr.nomorPengajuan }}
              </td>
              <td class="px-5 py-4 text-sm text-gray-500">
                {{ pr.tanggalPengajuan }}
              </td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                  <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                    :class="pr.pemohonBg"
                  >
                    {{ pr.pemohonInisial }}
                  </span>
                  <span class="text-sm text-gray-700">{{ pr.pemohonNama }}</span>
                </div>
              </td>
              <td class="px-5 py-4 text-sm text-gray-600">
                {{ pr.jumlahItem }}
              </td>
              <td class="px-5 py-4 text-right text-sm font-medium text-gray-800">
                {{ pr.estimasiTotal }}
              </td>
              <td class="px-5 py-4 text-right">
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                  <Clock class="h-3 w-3" />
                  {{ pr.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════ Tabel 2: Settlement yang Perlu Diselesaikan ═══════════ -->
    <div class="mb-6 rounded-xl border border-gray-100 bg-white">
      <!-- Header kartu tabel -->
      <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
            <ClipboardCheck class="h-5 w-5 text-blue-600" />
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-base font-bold text-gray-900">
                Settlement yang Perlu Diselesaikan
              </h2>
              <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                {{ settlementItems.length }} Membutuhkan Tindakan
              </span>
            </div>
            <p class="mt-0.5 text-xs text-gray-500">
              Rekonsiliasi nota belanja aktual gudang dengan estimasi anggaran yang telah disetujui
            </p>
          </div>
        </div>
        <RouterLink
          to="/accountant/settlements"
          class="flex shrink-0 items-center gap-1.5 text-sm font-semibold text-[#C2410C] transition-colors hover:text-[#B45309]"
        >
          Buka Settlement
          <ArrowRight class="h-4 w-4" />
        </RouterLink>
      </div>

      <!-- Tabel data settlement -->
      <div class="overflow-x-auto">
        <table class="w-full min-w-[750px]">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                Nomor Pengajuan
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Estimasi Pembelian
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Total Aktual
              </th>
              <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">
                Selisih
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">
                Status
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in settlementItems"
              :key="item.nomorPengajuan"
              :class="idx < settlementItems.length - 1 ? 'border-b border-gray-50' : ''"
            >
              <td class="px-5 py-4">
                <p class="text-sm font-semibold text-gray-800">{{ item.nomorPengajuan }}</p>
                <p class="text-xs text-gray-400">{{ item.deskripsi }}</p>
              </td>
              <td class="px-5 py-4 text-right text-sm text-gray-600">
                {{ item.estimasiPembelian }}
              </td>
              <td class="px-5 py-4 text-right text-sm font-medium text-gray-800">
                {{ item.totalAktual }}
              </td>
              <td class="px-5 py-4 text-center">
                <span
                  :class="[
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    item.selisihType === 'over'
                      ? 'bg-red-50 text-red-600'
                      : 'bg-green-50 text-green-600',
                  ]"
                >
                  <TrendingUp v-if="item.selisihType === 'over'" class="h-3 w-3" />
                  <TrendingDown v-else class="h-3 w-3" />
                  {{ item.selisih }}
                </span>
              </td>
              <td class="px-5 py-4 text-right">
                <span class="inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                  {{ item.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════ Bagian Bawah: Ringkasan Penjualan & Top 3 Produk ═══════════ -->
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- ─── Kartu Kiri: Ringkasan Penjualan ─── -->
      <div class="rounded-xl border border-gray-100 bg-white">
        <!-- Header kartu -->
        <div class="flex items-center justify-between border-b border-gray-100 p-5">
          <div class="flex items-center gap-2">
            <DollarSign class="h-5 w-5 text-green-600" />
            <h2 class="text-base font-bold text-gray-900">Ringkasan Penjualan</h2>
          </div>
          <span class="text-xs text-gray-400">Hari Ini - {{ dashboardDate }} ▾</span>
        </div>

        <div class="p-5">
          <!-- Kotak highlight penjualan bersih -->
          <div class="rounded-xl border border-orange-100 bg-orange-50/50 p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
              Total Penjualan Bersih
            </p>
            <p class="mt-1 text-3xl font-bold text-[#C2410C]">{{ totalPenjualanBersih }}</p>
            <p class="mt-1 text-xs text-gray-400">
              Dihitung dari seluruh pesanan yang telah dibayar / lunas pada rentang tanggal
              terpilih (Hari Ini - {{ dashboardDate }})
            </p>
            <!-- Badge Status Lunas -->
            <div class="mt-2 flex items-center gap-1 text-xs text-green-600">
              <CheckCircle2 class="h-3.5 w-3.5" />
              <span class="font-medium">Status Lunas</span>
            </div>
          </div>

          <!-- Grid statistik kecil -->
          <div class="mt-4 grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-gray-400">Total Transaksi Lunas</p>
              <p class="mt-0.5 text-lg font-bold text-gray-900">{{ totalTransaksiLunas }}</p>
              <p class="text-xs text-gray-400">Semua meja selesai bayar</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Rata-rata Transaksi (AOV)</p>
              <p class="mt-0.5 text-lg font-bold text-gray-900">{{ rataRataTransaksi }}</p>
              <p class="text-xs text-gray-400">Per transaksi lunas</p>
            </div>
          </div>

          <!-- Info rentang laporan -->
          <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400">
            <div class="flex items-center gap-1">
              <Clock class="h-3 w-3" />
              <span>Rentang Laporan Aktif:</span>
            </div>
            <span class="font-medium text-gray-600">24 Okt 2024 (00:00 - 23:59 WIB)</span>
            <span class="font-medium text-gray-600">POS Kasir Aktif</span>
          </div>

          <!-- Tombol buka laporan penjualan -->
          <RouterLink
            to="/accountant/sales-report"
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-[#C2410C] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#B45309]"
          >
            Buka Laporan Penjualan
            <ArrowRight class="h-4 w-4" />
          </RouterLink>
        </div>
      </div>

      <!-- ─── Kartu Kanan: Top 3 Produk Terlaris Hari Ini ─── -->
      <div class="rounded-xl border border-gray-100 bg-white">
        <!-- Header kartu -->
        <div class="flex items-center justify-between border-b border-gray-100 p-5">
          <div class="flex items-center gap-2">
            <ShoppingBag class="h-5 w-5 text-[#C2410C]" />
            <h2 class="text-base font-bold text-gray-900">Top 3 Produk Terlaris Hari Ini</h2>
          </div>
          <span class="text-xs text-gray-400">
            Peringkat: Jumlah<br />Terjual
          </span>
        </div>

        <div class="p-5">
          <!-- Daftar produk terlaris dengan ranking -->
          <div class="space-y-3">
            <div
              v-for="product in topProducts"
              :key="product.rank"
              class="flex items-center gap-3 rounded-xl border border-gray-100 p-4"
            >
              <!-- Badge ranking dengan warna berbeda per posisi -->
              <div
                :class="[
                  'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold',
                  product.rank === 1
                    ? 'bg-[#C2410C] text-white'
                    : product.rank === 2
                      ? 'bg-orange-100 text-[#C2410C]'
                      : 'bg-orange-50 text-[#C2410C]',
                ]"
              >
                {{ product.rank }}
              </div>

              <!-- Nama dan jumlah terjual -->
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-800">{{ product.nama }}</p>
                <p class="text-xs text-gray-400">{{ product.jumlahTerjual }}</p>
              </div>

              <!-- Omzet -->
              <div class="text-right">
                <p class="text-sm font-bold text-gray-900">{{ product.omzet }}</p>
                <p class="text-xs text-gray-400">Omzet pendukung</p>
              </div>
            </div>
          </div>

          <!-- Keterangan -->
          <p class="mt-4 text-xs text-gray-400">
            Urutan peringkat dihitung berdasarkan akumulasi kuantitas produk terjual (order items)
            dari pesanan lunas.
          </p>

          <!-- Tombol lihat semua produk -->
          <RouterLink
            to="/accountant/best-selling"
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50"
          >
            Lihat Semua Produk Terlaris
            <ArrowRight class="h-4 w-4" />
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- ═══════════ Catatan Kaki ═══════════ -->
    <!-- Disclaimer bahwa data yang ditampilkan adalah simulasi -->
    <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
      <Info class="h-3.5 w-3.5 shrink-0" />
      <span>
        * Menampilkan simulasi data contoh pengajuan, settlement, dan penjualan untuk role Akuntan
        sesuai ketentuan SKPL POS Restaurant.
      </span>
    </div>
  </DashboardLayout>
</template>
