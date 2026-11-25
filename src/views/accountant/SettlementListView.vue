<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Search,
  X,
  RotateCcw,
  FileText,
  CheckCircle2,
  AlertTriangle,
  Wallet,
  Eye,
  Download,
  ChevronLeft,
  ChevronRight,
  Info,
  RefreshCw,
  ClipboardCheck,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe data untuk setiap item bahan baku dalam rincian settlement ────────
interface SettlementItem {
  namaBahan: string
  deskripsi: string
  qtyRealisasi: string
  hargaSatuan: string
  subtotalAktual: string
  subtotalNum: number
}

// ─── Tipe data untuk lampiran file nota/kuitansi ────────────────────────────
interface Attachment {
  namaFile: string
  ukuran: string
  keterangan: string
  iconBg: string
}

// ─── Tipe data untuk setiap baris settlement ────────────────────────────────
interface SettlementRow {
  id: string
  nomorPR: string
  nomorSettlement: string
  petugasBelanja: string
  unitPetugas: string
  tanggalBelanja: string
  jamBelanja: string
  estimasiPembelian: string
  estimasiNum: number
  totalAktual: string
  totalAktualNum: number
  selisih: string
  selisihNum: number
  selisihType: 'lebih' | 'sesuai' | 'kurang'
  status: 'Menunggu Settlement' | 'Settlement Selesai'
  // Data tambahan untuk modal detail (settlement selesai)
  verifikator: string
  tanggalDisahkan: string
  metodePengeluaran: string
  metodeDeskripsi: string
  statusRekonsiliasi: string
  items: SettlementItem[]
  attachments: Attachment[]
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar semua settlement (data dummy) ───────────────────────────────────
// Akan diganti dengan data dari API backend Laravel nantinya.
const allSettlements = ref<SettlementRow[]>([
  {
    id: '1',
    nomorPR: 'PR-2024-10-004',
    nomorSettlement: 'STL-2024-10-008',
    petugasBelanja: 'Doni Kurniawan',
    unitPetugas: 'Gudang Utama',
    tanggalBelanja: '24 Okt 2024',
    jamBelanja: '10:15 WIB',
    estimasiPembelian: 'Rp 950.000',
    estimasiNum: 950_000,
    totalAktual: 'Rp 945.000',
    totalAktualNum: 945_000,
    selisih: '+Rp 5.000',
    selisihNum: 5_000,
    selisihType: 'lebih',
    status: 'Menunggu Settlement',
    verifikator: '',
    tanggalDisahkan: '',
    metodePengeluaran: '',
    metodeDeskripsi: '',
    statusRekonsiliasi: '',
    items: [
      { namaBahan: 'Daging Sapi Sirloin Lokal', deskripsi: 'Spesifikasi Grade A', qtyRealisasi: '5 Kg', hargaSatuan: 'Rp 125.000', subtotalAktual: 'Rp 625.000', subtotalNum: 625_000 },
      { namaBahan: 'Bawang Bombay Segar', deskripsi: 'Kemasan Karung 8 Kg', qtyRealisasi: '8 Kg', hargaSatuan: 'Rp 25.000', subtotalAktual: 'Rp 200.000', subtotalNum: 200_000 },
      { namaBahan: 'Minyak Goreng Sawit Premium', deskripsi: 'Jerigen 6 Liter', qtyRealisasi: '6 Liter', hargaSatuan: 'Rp 20.000', subtotalAktual: 'Rp 120.000', subtotalNum: 120_000 },
    ],
    attachments: [
      { namaFile: 'Kuitansi_PasarInduk_Bahan_24Okt.pdf', ukuran: '1.4 MB', keterangan: 'Stempel Sah', iconBg: 'bg-red-50' },
      { namaFile: 'Struk_NotaTokoKemas_24Okt.jpg', ukuran: '850 KB', keterangan: 'Nota Kasir Asli', iconBg: 'bg-orange-50' },
    ],
  },
  {
    id: '2',
    nomorPR: 'PR-2024-10-003',
    nomorSettlement: 'STL-2024-10-007',
    petugasBelanja: 'Hendra',
    unitPetugas: 'Unit Gudang Dapur',
    tanggalBelanja: '23 Okt 2024',
    jamBelanja: '14:30 WIB',
    estimasiPembelian: 'Rp 2.450.000',
    estimasiNum: 2_450_000,
    totalAktual: 'Rp 2.450.000',
    totalAktualNum: 2_450_000,
    selisih: 'Rp 0',
    selisihNum: 0,
    selisihType: 'sesuai',
    status: 'Menunggu Settlement',
    verifikator: '',
    tanggalDisahkan: '',
    metodePengeluaran: '',
    metodeDeskripsi: '',
    statusRekonsiliasi: '',
    items: [
      { namaBahan: 'Kopi Arabika House Blend', deskripsi: 'Grade 1 Medium Dark Roast', qtyRealisasi: '10 Kg', hargaSatuan: 'Rp 120.000', subtotalAktual: 'Rp 1.200.000', subtotalNum: 1_200_000 },
      { namaBahan: 'Fresh Milk Full Cream', deskripsi: 'Pasteurisasi 1 Liter', qtyRealisasi: '24 Liter', hargaSatuan: 'Rp 22.000', subtotalAktual: 'Rp 528.000', subtotalNum: 528_000 },
      { namaBahan: 'Gula Aren Cair Premium', deskripsi: 'Jerigen Food Grade 5L', qtyRealisasi: '10 Liter', hargaSatuan: 'Rp 35.000', subtotalAktual: 'Rp 350.000', subtotalNum: 350_000 },
      { namaBahan: 'Paper Cup 12oz + Lid', deskripsi: 'Logo POS Restaurant', qtyRealisasi: '500 Pcs', hargaSatuan: 'Rp 600', subtotalAktual: 'Rp 300.000', subtotalNum: 300_000 },
      { namaBahan: 'Sedotan Ramah Lingkungan', deskripsi: 'Oxo-biodegradable isi 100', qtyRealisasi: '10 Pack', hargaSatuan: 'Rp 7.200', subtotalAktual: 'Rp 72.000', subtotalNum: 72_000 },
    ],
    attachments: [
      { namaFile: 'Nota_Supplier_Kopi_23Okt.pdf', ukuran: '1.8 MB', keterangan: 'Faktur Resmi', iconBg: 'bg-red-50' },
      { namaFile: 'Struk_TokoKemasan_23Okt.jpg', ukuran: '720 KB', keterangan: 'Nota Kasir Asli', iconBg: 'bg-orange-50' },
    ],
  },
  {
    id: '3',
    nomorPR: 'PR-2024-10-001',
    nomorSettlement: 'STL-2024-10-006',
    petugasBelanja: 'Doni Kurniawan',
    unitPetugas: 'Gudang Utama',
    tanggalBelanja: '22 Okt 2024',
    jamBelanja: '11:45 WIB',
    estimasiPembelian: 'Rp 1.120.000',
    estimasiNum: 1_120_000,
    totalAktual: 'Rp 1.150.000',
    totalAktualNum: 1_150_000,
    selisih: '-Rp 30.000',
    selisihNum: -30_000,
    selisihType: 'kurang',
    status: 'Menunggu Settlement',
    verifikator: '',
    tanggalDisahkan: '',
    metodePengeluaran: '',
    metodeDeskripsi: '',
    statusRekonsiliasi: '',
    items: [
      { namaBahan: 'Sabun Cuci Piring Industri', deskripsi: 'Jerigen 5 Liter Food Grade', qtyRealisasi: '4 Jerigen', hargaSatuan: 'Rp 90.000', subtotalAktual: 'Rp 360.000', subtotalNum: 360_000 },
      { namaBahan: 'Lap Microfiber Anti-Bakteri', deskripsi: 'Pack isi 10 lembar', qtyRealisasi: '10 Pack', hargaSatuan: 'Rp 48.000', subtotalAktual: 'Rp 480.000', subtotalNum: 480_000 },
      { namaBahan: 'Hand Sanitizer Refill 1L', deskripsi: 'Alkohol 70% Food Safe', qtyRealisasi: '6 Botol', hargaSatuan: 'Rp 51.667', subtotalAktual: 'Rp 310.000', subtotalNum: 310_000 },
    ],
    attachments: [
      { namaFile: 'Struk_Toko_Kimia_22Okt.pdf', ukuran: '950 KB', keterangan: 'Nota Kasir Asli', iconBg: 'bg-red-50' },
    ],
  },
  {
    id: '4',
    nomorPR: 'PR-2024-09-028',
    nomorSettlement: 'STL-2024-10-005',
    petugasBelanja: 'Doni Kurniawan',
    unitPetugas: 'Gudang Utama',
    tanggalBelanja: '20 Okt 2024',
    jamBelanja: '09:30 WIB',
    estimasiPembelian: 'Rp 3.890.000',
    estimasiNum: 3_890_000,
    totalAktual: 'Rp 3.890.000',
    totalAktualNum: 3_890_000,
    selisih: 'Rp 0',
    selisihNum: 0,
    selisihType: 'sesuai',
    status: 'Settlement Selesai',
    verifikator: 'Ratna Sari',
    tanggalDisahkan: '20 Okt 2024, 14:15 WIB',
    metodePengeluaran: 'Kas Kecil',
    metodeDeskripsi: 'Petty Cash Restoran',
    statusRekonsiliasi: 'Telah Rekonsiliasi',
    items: [
      { namaBahan: 'Beras Pandan Wangi Super', deskripsi: 'Stok Pokok Dapur Utama', qtyRealisasi: '10 Karung @ 25 Kg', hargaSatuan: 'Rp 175.000', subtotalAktual: 'Rp 1.750.000', subtotalNum: 1_750_000 },
      { namaBahan: 'Daging Ayam Karkas Segar', deskripsi: 'Potong dingin higienis', qtyRealisasi: '30 Kg @ Rp 38.000', hargaSatuan: 'Rp 38.000', subtotalAktual: 'Rp 1.140.000', subtotalNum: 1_140_000 },
      { namaBahan: 'Minyak Goreng Sawit Resto', deskripsi: 'Kemasan jerigen kuliner', qtyRealisasi: '25 Jerigen @ Rp 40.000', hargaSatuan: 'Rp 40.000', subtotalAktual: 'Rp 1.000.000', subtotalNum: 1_000_000 },
    ],
    attachments: [
      { namaFile: 'Kuitansi_Pasar_Induk_20Okt.pdf', ukuran: '2.1 MB', keterangan: 'Cap Basah Distributor', iconBg: 'bg-red-50' },
      { namaFile: 'Nota_Pemasok_Daging_Prima.jpg', ukuran: '1.4 MB', keterangan: 'Struk Kasir Asli', iconBg: 'bg-orange-50' },
    ],
  },
  {
    id: '5',
    nomorPR: 'PR-2024-09-025',
    nomorSettlement: 'STL-2024-10-004',
    petugasBelanja: 'Hendra',
    unitPetugas: 'Unit Gudang Dapur',
    tanggalBelanja: '18 Okt 2024',
    jamBelanja: '13:00 WIB',
    estimasiPembelian: 'Rp 420.000',
    estimasiNum: 420_000,
    totalAktual: 'Rp 420.000',
    totalAktualNum: 420_000,
    selisih: 'Rp 0',
    selisihNum: 0,
    selisihType: 'sesuai',
    status: 'Settlement Selesai',
    verifikator: 'Ratna Sari',
    tanggalDisahkan: '18 Okt 2024, 16:00 WIB',
    metodePengeluaran: 'Kas Kecil',
    metodeDeskripsi: 'Petty Cash Restoran',
    statusRekonsiliasi: 'Telah Rekonsiliasi',
    items: [
      { namaBahan: 'Bumbu Rempah Halus', deskripsi: 'Kemasan Vakum Higienis', qtyRealisasi: '5 Pack', hargaSatuan: 'Rp 20.000', subtotalAktual: 'Rp 100.000', subtotalNum: 100_000 },
      { namaBahan: 'Tepung Terigu Protein Tinggi', deskripsi: 'Karung 25 Kg', qtyRealisasi: '1 Karung', hargaSatuan: 'Rp 185.000', subtotalAktual: 'Rp 185.000', subtotalNum: 185_000 },
      { namaBahan: 'Garam Halus Beryodium', deskripsi: 'Kemasan 500g', qtyRealisasi: '10 Pack @ Rp 5.500', hargaSatuan: 'Rp 5.500', subtotalAktual: 'Rp 55.000', subtotalNum: 55_000 },
      { namaBahan: 'Santan Kelapa Murni', deskripsi: 'Kemasan 1 Liter', qtyRealisasi: '8 Liter @ Rp 10.000', hargaSatuan: 'Rp 10.000', subtotalAktual: 'Rp 80.000', subtotalNum: 80_000 },
    ],
    attachments: [
      { namaFile: 'Nota_PasarTradisional_18Okt.pdf', ukuran: '1.1 MB', keterangan: 'Nota Kasir Asli', iconBg: 'bg-red-50' },
    ],
  },
])

// ─── State pencarian & filter ───────────────────────────────────────────────
const searchQuery = ref<string>('')
const selectedStatus = ref<string>('Semua Status')

// ─── State pagination ───────────────────────────────────────────────────────
const currentPage = ref<number>(1)
const itemsPerPage = ref<number>(5)

// ─── State modal Proses Settlement (untuk status Menunggu) ──────────────────
const showProcessModal = ref<boolean>(false)
const processTarget = ref<SettlementRow | null>(null)

// ─── State modal Detail Settlement (untuk status Selesai) ───────────────────
const showDetailModal = ref<boolean>(false)
const detailTarget = ref<SettlementRow | null>(null)

// ═══════════════════════════════════════════════════════════════════════════════
// COMPUTED (Kalkulasi Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Jumlah berkas menunggu settlement ──────────────────────────────────────
const statMenunggu = computed<number>(
  () => allSettlements.value.filter((s) => s.status === 'Menunggu Settlement').length,
)

// ─── Jumlah berkas ada selisih belanja ──────────────────────────────────────
const statAdaSelisih = computed<number>(
  () => allSettlements.value.filter((s) => s.selisihType !== 'sesuai').length,
)

// ─── Data yang sudah difilter berdasarkan pencarian dan status ──────────────
const filteredSettlements = computed<SettlementRow[]>(() => {
  let result = allSettlements.value

  // Filter berdasarkan dropdown status
  if (selectedStatus.value === 'Menunggu Settlement') {
    result = result.filter((s) => s.status === 'Menunggu Settlement')
  } else if (selectedStatus.value === 'Settlement Selesai') {
    result = result.filter((s) => s.status === 'Settlement Selesai')
  }

  // Filter berdasarkan kata kunci pencarian (case-insensitive, trim spasi)
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    result = result.filter(
      (s) =>
        s.nomorPR.toLowerCase().includes(q) ||
        s.nomorSettlement.toLowerCase().includes(q) ||
        s.petugasBelanja.toLowerCase().includes(q),
    )
  }

  return result
})

// ─── Data yang ditampilkan pada halaman saat ini (pagination) ───────────────
const paginatedSettlements = computed<SettlementRow[]>(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredSettlements.value.slice(start, start + itemsPerPage.value)
})

// ─── Total halaman berdasarkan jumlah data yang telah difilter ──────────────
const totalPages = computed<number>(() =>
  Math.max(1, Math.ceil(filteredSettlements.value.length / itemsPerPage.value)),
)

// ─── Sliding window nomor halaman untuk pagination dinamis ──────────────────
const visiblePages = computed<(number | string)[]>(() => {
  const total = totalPages.value
  const current = currentPage.value
  const pages: (number | string)[] = []
  if (total <= 5) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    if (current < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

// ─── Hitung total realisasi belanja dari item rincian settlement (modal proses) ─
const processTotalRealisasi = computed<string>(() => {
  if (!processTarget.value) return 'Rp 0'
  const total = processTarget.value.items.reduce((sum, item) => sum + item.subtotalNum, 0)
  return `Rp ${total.toLocaleString('id-ID')}`
})

// ─── Hitung total realisasi belanja dari item rincian settlement (modal detail) ─
const detailTotalRealisasi = computed<string>(() => {
  if (!detailTarget.value) return 'Rp 0'
  const total = detailTarget.value.items.reduce((sum, item) => sum + item.subtotalNum, 0)
  return `Rp ${total.toLocaleString('id-ID')}`
})

// ─── Label selisih anggaran beserta keterangan ──────────────────────────────
function selisihLabel(row: SettlementRow): string {
  if (row.selisihType === 'lebih') return `${row.selisih} (Lebih)`
  if (row.selisihType === 'kurang') return `${row.selisih} (Kurang)`
  return 'Rp 0 (Sesuai)'
}

// ─── Kelas CSS badge selisih ────────────────────────────────────────────────
function selisihBadgeClass(type: SettlementRow['selisihType']): string {
  if (type === 'lebih') return 'bg-blue-50 text-blue-700'
  if (type === 'kurang') return 'bg-red-50 text-red-600'
  return 'bg-gray-100 text-gray-600'
}

// ═══════════════════════════════════════════════════════════════════════════════
// WATCHER
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Reset halaman ke 1 setiap kali pencarian atau filter status berubah ────
// Mencegah tabel tampak kosong karena terjebak di halaman yang tidak valid
watch([searchQuery, selectedStatus], () => {
  currentPage.value = 1
})

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Navigasi ke halaman tertentu pada pagination ───────────────────────────
function goToPage(page: number | string): void {
  if (typeof page === 'string') return
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

// ─── Mereset semua filter pencarian dan dropdown ke kondisi awal ────────────
function resetFilters(): void {
  searchQuery.value = ''
  selectedStatus.value = 'Semua Status'
}

// ─── Membuka modal Proses Settlement untuk berkas berstatus Menunggu ────────
function openProcessModal(row: SettlementRow): void {
  processTarget.value = row
  showProcessModal.value = true
}

// ─── Menutup modal Proses Settlement ────────────────────────────────────────
function closeProcessModal(): void {
  showProcessModal.value = false
  processTarget.value = null
}

// ─── Membuka modal Detail Settlement untuk berkas berstatus Selesai ─────────
function openDetailModal(row: SettlementRow): void {
  detailTarget.value = row
  showDetailModal.value = true
}

// ─── Menutup modal Detail Settlement ────────────────────────────────────────
function closeDetailModal(): void {
  showDetailModal.value = false
  detailTarget.value = null
}

// ─── Mengesahkan / menyelesaikan settlement ─────────────────────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint POST /api/accountant/settlements/{id}/approve
function approveSettlement(id: string): void {
  // [API-READY] Contoh pemanggilan API:
  // await api.post(`/accountant/settlements/${id}/approve`)

  const row = allSettlements.value.find((s) => s.id === id)
  if (row) {
    row.status = 'Settlement Selesai'
    row.verifikator = 'Ratna Sari'
    row.tanggalDisahkan = '25 Nov 2026, 21:00 WIB'
    row.metodePengeluaran = 'Kas Kecil'
    row.metodeDeskripsi = 'Petty Cash Restoran'
    row.statusRekonsiliasi = 'Telah Rekonsiliasi'
  }
  closeProcessModal()
}

// ─── Meminta revisi nota settlement ─────────────────────────────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint POST /api/accountant/settlements/{id}/revision
function requestRevisionSettlement(id: string, _reason: string): void {
  // [API-READY] Contoh pemanggilan API:
  // await api.post(`/accountant/settlements/${id}/revision`, { reason })

  void id
  alert('Permintaan revisi nota telah dikirim ke petugas gudang.')
  closeProcessModal()
}

// ─── Simulasi preview lampiran file ─────────────────────────────────────────
function previewAttachment(filename: string): void {
  alert(`[Simulasi] Membuka preview file: ${filename}`)
}

// ─── Simulasi download lampiran file ────────────────────────────────────────
function downloadAttachment(filename: string): void {
  alert(`[Simulasi] Mengunduh file: ${filename}`)
}

// ─── Mengambil data settlement dari API backend ─────────────────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint GET /api/accountant/settlements dan mengisi allSettlements.
async function fetchSettlements(): Promise<void> {
  // [API-READY] Contoh pemanggilan API:
  // const { data } = await api.get('/accountant/settlements')
  // allSettlements.value = data.settlements
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memuat data saat komponen pertama kali dipasang (mounted) ──────────────
onMounted(() => {
  fetchSettlements()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Header Halaman ═══════════ -->
    <div class="mb-6 rounded-xl border border-gray-100 bg-white p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">Settlement Pengadaan</h1>
            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">FR-015</span>
            <span class="flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-600">
              <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
              Data Aktif
            </span>
          </div>
          <p class="mt-1.5 max-w-2xl text-sm text-gray-500">
            Verifikasi nota dan kuitansi realisasi belanja bahan baku dari petugas gudang terhadap
            purchase request yang telah disetujui.
          </p>
        </div>

        <!-- Indikator sinkronisasi -->
        <div class="flex items-center gap-2">
          <div class="flex items-center gap-2 rounded-full bg-green-50 px-4 py-2 text-xs font-medium text-green-700">
            <span class="h-2 w-2 rounded-full bg-green-500"></span>
            Sinkronisasi Otomatis • 09:15 WIB
          </div>
          <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50"
            title="Refresh data"
          >
            <RefreshCw class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ 4 Kartu Ringkasan Status ═══════════ -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      <!-- Kartu 1: Menunggu Settlement -->
      <div class="rounded-xl border border-gray-100 bg-white p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-500">Menunggu Settlement</p>
              <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-600">Perlu Proses</span>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ statMenunggu }}</p>
            <p class="text-sm text-gray-500">Berkas</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50">
            <FileText class="h-5 w-5 text-[#C2410C]" />
          </div>
        </div>
      </div>

      <!-- Kartu 2: Settlement Selesai -->
      <div class="rounded-xl border border-gray-100 bg-white p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-500">Settlement Selesai</p>
              <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-600">Bulan Ini</span>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900">16</p>
            <p class="text-sm text-gray-500">Berkas</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
            <CheckCircle2 class="h-5 w-5 text-blue-600" />
          </div>
        </div>
      </div>

      <!-- Kartu 3: Ada Selisih Belanja -->
      <div class="rounded-xl border border-gray-100 bg-white p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-500">Ada Selisih Belanja</p>
              <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-600">Perlu Cek</span>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ statAdaSelisih }}</p>
            <p class="text-sm text-gray-500">Berkas</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50">
            <AlertTriangle class="h-5 w-5 text-orange-500" />
          </div>
        </div>
      </div>

      <!-- Kartu 4: Total Realisasi Belanja -->
      <div class="rounded-xl border border-gray-100 bg-white p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-500">Total Realisasi Belanja</p>
              <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600">Bulan Berjalan</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-[#C2410C]">Rp 18.320.000</p>
            <p class="text-sm text-gray-500">Akumulasi 23 transaksi PR</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50">
            <Wallet class="h-5 w-5 text-[#C2410C]" />
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════ Bar Filter & Pencarian ═══════════ -->
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <!-- Input pencarian -->
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nomor settlement, nomor PR, atau nama petugas gudang..."
            class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-9 text-sm text-gray-800 transition focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-slate-400 hover:text-slate-600"
            @click="searchQuery = ''"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Dropdown filter status -->
        <select
          v-model="selectedStatus"
          class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 transition focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30 sm:w-52"
        >
          <option>Semua Status</option>
          <option>Menunggu Settlement</option>
          <option>Settlement Selesai</option>
        </select>

        <!-- Tombol Reset Filter -->
        <button
          type="button"
          class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
          @click="resetFilters"
        >
          <RotateCcw class="h-3.5 w-3.5" />
          Reset Filter
        </button>
      </div>
    </div>

    <!-- ═══════════ Tabel Daftar Settlement ═══════════ -->
    <div class="rounded-xl border border-gray-100 bg-white">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[1000px]">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="w-12 px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">No.</th>
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nomor PR / Settlement</th>
              <th class="px-3.5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Petugas Gudang</th>
              <th class="px-3.5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Estimasi Pembelian</th>
              <th class="px-3.5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Total Aktual</th>
              <th class="px-3.5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Selisih</th>
              <th class="px-3.5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Status</th>
              <th class="px-3.5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in paginatedSettlements"
              :key="row.id"
              :class="idx < paginatedSettlements.length - 1 ? 'border-b border-gray-50' : ''"
            >
              <!-- NO -->
              <td class="px-3.5 py-4 text-sm text-gray-500">
                {{ (currentPage - 1) * itemsPerPage + idx + 1 }}
              </td>

              <!-- NOMOR PR / SETTLEMENT -->
              <td class="px-3.5 py-4">
                <p class="text-sm font-bold text-gray-900">{{ row.nomorPR }}</p>
                <p class="text-xs text-gray-400">{{ row.nomorSettlement }}</p>
              </td>

              <!-- PETUGAS GUDANG -->
              <td class="px-3.5 py-4">
                <p class="text-sm font-semibold text-gray-800">{{ row.petugasBelanja }}</p>
                <p class="text-xs text-gray-400">{{ row.unitPetugas }}</p>
              </td>

              <!-- ESTIMASI PEMBELIAN -->
              <td class="px-3.5 py-4 text-right text-sm text-gray-600">{{ row.estimasiPembelian }}</td>

              <!-- TOTAL AKTUAL -->
              <td class="px-3.5 py-4 text-right text-sm font-semibold text-gray-800">{{ row.totalAktual }}</td>

              <!-- SELISIH -->
              <td class="px-3.5 py-4 text-center">
                <span
                  :class="[
                    'inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    selisihBadgeClass(row.selisihType),
                  ]"
                >
                  {{ selisihLabel(row) }}
                </span>
              </td>

              <!-- STATUS -->
              <td class="px-3.5 py-4 text-center">
                <span
                  :class="[
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium',
                    row.status === 'Menunggu Settlement'
                      ? 'bg-amber-50 text-amber-700'
                      : 'bg-green-50 text-green-700',
                  ]"
                >
                  <span
                    :class="[
                      'h-1.5 w-1.5 rounded-full',
                      row.status === 'Menunggu Settlement' ? 'bg-amber-500' : 'bg-green-500',
                    ]"
                  ></span>
                  {{ row.status }}
                </span>
              </td>

              <!-- AKSI -->
              <td class="px-3.5 py-4 text-center">
                <button
                  v-if="row.status === 'Menunggu Settlement'"
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg bg-[#C2410C] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#B45309]"
                  @click="openProcessModal(row)"
                >
                  Proses Settlement
                </button>
                <button
                  v-else
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50"
                  @click="openDetailModal(row)"
                >
                  Detail
                </button>
              </td>
            </tr>

            <!-- Baris kosong -->
            <tr v-if="paginatedSettlements.length === 0">
              <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">
                Tidak ada berkas settlement yang sesuai dengan filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer tabel: Info jumlah & pagination -->
      <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 sm:flex-row">
        <p class="text-xs text-gray-500">
          Menampilkan
          <span class="font-semibold text-gray-800">
            {{ filteredSettlements.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }} -
            {{ Math.min(currentPage * itemsPerPage, filteredSettlements.length) }}
          </span>
          dari
          <span class="font-semibold text-gray-800">{{ filteredSettlements.length }}</span>
          berkas settlement
        </p>

        <!-- Pagination -->
        <div class="flex items-center gap-1.5">
          <button
            type="button"
            :disabled="currentPage <= 1"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage - 1)"
          >
            <ChevronLeft class="h-3.5 w-3.5" /> Sebelumnya
          </button>
          <template v-for="(page, pIdx) in visiblePages" :key="'p-' + pIdx">
            <span v-if="page === '...'" class="px-1.5 text-xs text-gray-400">…</span>
            <button
              v-else
              type="button"
              :class="[
                'inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-semibold transition',
                page === currentPage
                  ? 'bg-[#C2410C] text-white shadow-sm'
                  : 'text-gray-600 hover:bg-gray-100',
              ]"
              @click="goToPage(page as number)"
            >
              {{ page }}
            </button>
          </template>
          <button
            type="button"
            :disabled="currentPage >= totalPages"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage + 1)"
          >
            Selanjutnya <ChevronRight class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ Info Box: Alur Verifikasi Settlement ═══════════ -->
    <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/50 p-5">
      <div class="flex items-start gap-3">
        <Info class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" />
        <div class="text-sm text-gray-700">
          <p class="font-bold">Alur Verifikasi Settlement (SKPL FR-015 POS Restaurant)</p>
          <p class="mt-1">
            Akuntan membandingkan estimasi pembelian pada Purchase Request dengan biaya aktual pada
            nota belanja dan menyelesaikan selisih anggaran sesuai alur SKPL FR-015.
          </p>
        </div>
      </div>
    </div>

    <!-- ═══════════ MODAL: Proses Settlement Pengadaan ═══════════ -->
    <div
      v-if="showProcessModal && processTarget"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeProcessModal"
    >
      <div class="flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl" style="max-height: 90vh">
        <!-- Header Modal -->
        <div class="flex items-start justify-between border-b border-gray-100 p-5">
          <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
              <FileText class="h-5 w-5 text-[#C2410C]" />
            </div>
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-lg font-bold text-gray-900">Proses Settlement Pengadaan</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">FR-015</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                  • Menunggu Settlement
                </span>
              </div>
              <p class="mt-0.5 text-sm text-gray-500">
                Verifikasi kesesuaian nota/kuitansi belanja bahan baku riil terhadap estimasi Purchase Request (FR-015).
              </p>
            </div>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
            @click="closeProcessModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Body Modal (scrollable) -->
        <div class="flex-1 overflow-y-auto p-5">
          <!-- Kartu ringkasan 4 kolom -->
          <div class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-4">
            <div>
              <p class="text-[10px] font-medium uppercase text-gray-400">Nomor PR</p>
              <p class="mt-0.5 text-sm font-bold text-gray-800">{{ processTarget.nomorPR }}</p>
            </div>
            <div>
              <p class="text-[10px] font-medium uppercase text-gray-400">Nomor Settlement</p>
              <p class="mt-0.5 text-sm font-bold text-gray-800">{{ processTarget.nomorSettlement }}</p>
            </div>
            <div>
              <p class="text-[10px] font-medium uppercase text-gray-400">Petugas Belanja</p>
              <p class="mt-0.5 text-sm font-semibold text-gray-800">{{ processTarget.petugasBelanja }}</p>
              <p class="text-xs text-gray-400">{{ processTarget.unitPetugas }}</p>
            </div>
            <div>
              <p class="text-[10px] font-medium uppercase text-gray-400">Tanggal Belanja</p>
              <p class="mt-0.5 text-sm font-semibold text-gray-800">{{ processTarget.tanggalBelanja }}</p>
              <p class="text-xs text-gray-400">{{ processTarget.jamBelanja }}</p>
            </div>
          </div>

          <!-- 3 Kartu rekonsiliasi keuangan -->
          <div class="mt-4 grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-gray-200 bg-white p-3.5">
              <p class="text-[10px] font-medium uppercase text-gray-400">Estimasi PR</p>
              <p class="mt-1 text-lg font-bold text-gray-900">{{ processTarget.estimasiPembelian }}</p>
              <p class="text-[10px] text-gray-400">Alokasi disetujui</p>
            </div>
            <div class="rounded-xl border border-orange-200 bg-orange-50/50 p-3.5">
              <p class="text-[10px] font-medium uppercase text-[#C2410C]">Total Aktual Nota</p>
              <p class="mt-1 text-lg font-bold text-[#C2410C]">{{ processTarget.totalAktual }}</p>
              <p class="text-[10px] text-gray-400">Realisasi fisik</p>
            </div>
            <div
              class="rounded-xl border p-3.5"
              :class="
                processTarget.selisihType === 'sesuai'
                  ? 'border-gray-200 bg-white'
                  : processTarget.selisihType === 'lebih'
                    ? 'border-blue-200 bg-blue-50/50'
                    : 'border-red-200 bg-red-50/50'
              "
            >
              <p class="text-[10px] font-medium uppercase text-gray-400">Selisih Anggaran</p>
              <p class="mt-1 text-lg font-bold text-gray-900">{{ selisihLabel(processTarget) }}</p>
              <p
                class="text-[10px]"
                :class="
                  processTarget.selisihType === 'lebih'
                    ? 'text-blue-600'
                    : processTarget.selisihType === 'kurang'
                      ? 'text-red-500'
                      : 'text-gray-400'
                "
              >
                {{
                  processTarget.selisihType === 'lebih'
                    ? `Sisa Anggaran ${processTarget.selisih}`
                    : processTarget.selisihType === 'kurang'
                      ? `Melebihi Anggaran ${processTarget.selisih}`
                      : '• Kas Tepat / Sesuai Budget'
                }}
              </p>
            </div>
          </div>

          <!-- Bukti Nota & Kuitansi Fisik Gudang -->
          <div class="mt-5">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-bold text-gray-800">Bukti Nota &amp; Kuitansi Fisik Gudang</h4>
              <span class="flex items-center gap-1 text-xs text-green-600">
                <CheckCircle2 class="h-3 w-3" />
                Cap &amp; Stempel Toko Terverifikasi
              </span>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div
                v-for="(att, aIdx) in processTarget.attachments"
                :key="aIdx"
                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3"
              >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" :class="att.iconBg">
                  <FileText class="h-5 w-5 text-[#C2410C]" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-xs font-semibold text-gray-800">{{ att.namaFile }}</p>
                  <p class="text-[10px] text-gray-400">{{ att.ukuran }} • {{ att.keterangan }}</p>
                </div>
                <button
                  type="button"
                  class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50"
                  @click="previewAttachment(att.namaFile)"
                >
                  Lihat
                </button>
              </div>
            </div>
          </div>

          <!-- Tabel rincian belanja aktual -->
          <div class="mt-5">
            <h4 class="text-sm font-bold text-gray-800">Rincian Belanja Bahan Baku Aktual vs PR</h4>
            <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200">
              <table class="w-full min-w-[500px] text-sm">
                <thead>
                  <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">Nama Bahan Baku</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase text-gray-500">QTY Realisasi</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500">Harga Satuan</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500">Subtotal Aktual</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, iIdx) in processTarget.items"
                    :key="iIdx"
                    :class="iIdx < processTarget.items.length - 1 ? 'border-b border-gray-100' : ''"
                  >
                    <td class="px-4 py-3">
                      <p class="font-medium text-gray-800">{{ item.namaBahan }}</p>
                      <p class="text-xs text-gray-400">{{ item.deskripsi }}</p>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ item.qtyRealisasi }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ item.hargaSatuan }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ item.subtotalAktual }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="border-t border-gray-200 bg-gray-50">
                    <td colspan="3" class="px-4 py-3 text-sm text-gray-500">Total Realisasi Belanja Aktual</td>
                    <td class="px-4 py-3 text-right text-lg font-bold text-[#C2410C]">{{ processTotalRealisasi }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- Footer Modal -->
        <div class="flex items-center justify-between border-t border-gray-100 p-5">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="closeProcessModal"
          >
            Tutup / Batal
          </button>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
              @click="requestRevisionSettlement(processTarget.id, 'Nota perlu diperbaiki')"
            >
              Minta Revisi Nota
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-[#C2410C] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#B45309]"
              @click="approveSettlement(processTarget.id)"
            >
              <CheckCircle2 class="h-4 w-4" />
              Selesaikan &amp; Sahkan Settlement
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════ MODAL: Detail Settlement Pengadaan ═══════════ -->
    <div
      v-if="showDetailModal && detailTarget"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeDetailModal"
    >
      <div class="flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl" style="max-height: 90vh">
        <!-- Header Modal -->
        <div class="flex items-start justify-between border-b border-gray-100 p-5">
          <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50">
              <CheckCircle2 class="h-5 w-5 text-green-600" />
            </div>
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">FR-015</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                  • Settlement Selesai
                </span>
              </div>
              <h3 class="mt-1 text-lg font-bold text-gray-900">Detail Settlement Pengadaan</h3>
              <p class="mt-0.5 text-sm text-gray-500">
                Informasi lengkap berkas pengadaan bahan baku yang telah diverifikasi dan disahkan oleh Akuntan.
              </p>
            </div>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
            @click="closeDetailModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Body Modal (scrollable) -->
        <div class="flex-1 overflow-y-auto p-5">
          <!-- Kartu ringkasan 2 baris x 4 kolom -->
          <div class="rounded-xl border border-gray-200 p-4">
            <div class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4">
              <div>
                <p class="text-[10px] font-medium uppercase text-gray-400">Nomor PR</p>
                <p class="mt-0.5 text-sm font-bold text-gray-800">{{ detailTarget.nomorPR }}</p>
              </div>
              <div>
                <p class="text-[10px] font-medium uppercase text-gray-400">Nomor Settlement</p>
                <p class="mt-0.5 text-sm font-bold text-[#C2410C]">{{ detailTarget.nomorSettlement }}</p>
              </div>
              <div>
                <p class="text-[10px] font-medium uppercase text-gray-400">Petugas Belanja</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800">{{ detailTarget.petugasBelanja }}</p>
                <p class="text-xs text-gray-400">{{ detailTarget.unitPetugas }}</p>
              </div>
              <div>
                <p class="text-[10px] font-medium uppercase text-gray-400">Tanggal Belanja</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800">{{ detailTarget.tanggalBelanja }}</p>
                <p class="text-xs text-gray-400">{{ detailTarget.jamBelanja }}</p>
              </div>
              <div>
                <p class="text-[10px] font-medium uppercase text-gray-400">Diverifikasi Oleh</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800">{{ detailTarget.verifikator }}</p>
                <p class="text-xs text-gray-400">Portal Akuntan</p>
              </div>
              <div>
                <p class="text-[10px] font-medium uppercase text-gray-400">Tanggal Disahkan</p>
                <p class="mt-0.5 text-sm text-gray-700">{{ detailTarget.tanggalDisahkan }}</p>
              </div>
              <div>
                <p class="text-[10px] font-medium uppercase text-gray-400">Metode Pengeluaran</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800">{{ detailTarget.metodePengeluaran }}</p>
                <p class="text-xs text-gray-400">{{ detailTarget.metodeDeskripsi }}</p>
              </div>
              <div>
                <p class="text-[10px] font-medium uppercase text-gray-400">Status Rekonsiliasi</p>
                <p class="mt-0.5 flex items-center gap-1 text-sm font-semibold text-green-600">
                  <span class="h-2 w-2 rounded-full bg-green-500"></span>
                  {{ detailTarget.statusRekonsiliasi }}
                </p>
                <p class="text-xs text-gray-400">Kas Seimbang</p>
              </div>
            </div>
          </div>

          <!-- Ringkasan Rekonsiliasi Keuangan -->
          <div class="mt-5">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">Ringkasan Rekonsiliasi Keuangan</h4>
              <span class="text-[10px] text-gray-400">Mata Uang: IDR (Rupiah)</span>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-3">
              <div class="rounded-xl border border-gray-200 bg-white p-3.5">
                <div class="flex items-center justify-between">
                  <p class="text-[10px] font-medium uppercase text-gray-400">Estimasi PR</p>
                  <Wallet class="h-4 w-4 text-gray-300" />
                </div>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ detailTarget.estimasiPembelian }}</p>
                <p class="text-[10px] text-gray-400">Alokasi anggaran disetujui</p>
              </div>
              <div class="rounded-xl border border-orange-200 bg-orange-50/50 p-3.5">
                <div class="flex items-center justify-between">
                  <p class="text-[10px] font-medium uppercase text-[#C2410C]">Total Realisasi Nota</p>
                  <ClipboardCheck class="h-4 w-4 text-orange-300" />
                </div>
                <p class="mt-1 text-lg font-bold text-[#C2410C]">{{ detailTarget.totalAktual }}</p>
                <p class="text-[10px] text-gray-400">Total fisik struk/kuitansi</p>
              </div>
              <div
                class="rounded-xl border p-3.5"
                :class="
                  detailTarget.selisihType === 'lebih'
                    ? 'border-blue-200 bg-blue-50/50'
                    : detailTarget.selisihType === 'kurang'
                      ? 'border-red-200 bg-red-50/50'
                      : 'border-green-200 bg-green-50/50'
                "
              >
                <div class="flex items-center justify-between">
                  <p
                    class="text-[10px] font-medium uppercase"
                    :class="
                      detailTarget.selisihType === 'lebih'
                        ? 'text-blue-600'
                        : detailTarget.selisihType === 'kurang'
                          ? 'text-red-600'
                          : 'text-green-600'
                    "
                  >
                    Selisih Anggaran
                  </p>
                  <CheckCircle2
                    class="h-4 w-4"
                    :class="
                      detailTarget.selisihType === 'lebih'
                        ? 'text-blue-300'
                        : detailTarget.selisihType === 'kurang'
                          ? 'text-red-300'
                          : 'text-green-300'
                    "
                  />
                </div>
                <p
                  class="mt-1 text-lg font-bold"
                  :class="
                    detailTarget.selisihType === 'lebih'
                      ? 'text-blue-700'
                      : detailTarget.selisihType === 'kurang'
                        ? 'text-red-600'
                        : 'text-green-600'
                  "
                >
                  {{ selisihLabel(detailTarget) }}
                </p>
                <p class="text-[10px] text-gray-400">
                  {{
                    detailTarget.selisihType === 'lebih'
                      ? `• Sisa Anggaran ${detailTarget.selisih}`
                      : detailTarget.selisihType === 'kurang'
                        ? `• Melebihi Anggaran ${detailTarget.selisih}`
                        : '• Kas Tepat / Sesuai Budget'
                  }}
                </p>
              </div>
            </div>
          </div>

          <!-- Tabel rincian realisasi bahan baku -->
          <div class="mt-5 rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <ClipboardCheck class="h-4 w-4 text-gray-500" />
                <h4 class="text-sm font-bold text-gray-800">Rincian Realisasi Bahan Baku</h4>
              </div>
              <span class="text-xs text-gray-400">{{ detailTarget.items.length }} Macam Bahan Terbeli</span>
            </div>
            <table class="mt-3 w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="py-2 pr-2 text-left text-xs font-semibold uppercase text-gray-400">No</th>
                  <th class="px-2 py-2 text-left text-xs font-semibold uppercase text-gray-400">Nama Bahan Baku</th>
                  <th class="px-2 py-2 text-left text-xs font-semibold uppercase text-gray-400">Kuantitas &amp; Satuan</th>
                  <th class="py-2 pl-2 text-right text-xs font-semibold uppercase text-gray-400">Subtotal Realisasi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, iIdx) in detailTarget.items"
                  :key="iIdx"
                  :class="iIdx < detailTarget.items.length - 1 ? 'border-b border-gray-100' : ''"
                >
                  <td class="py-3 pr-2 text-gray-500">{{ iIdx + 1 }}</td>
                  <td class="px-2 py-3">
                    <p class="font-medium text-gray-800">{{ item.namaBahan }}</p>
                    <p class="text-xs text-gray-400">{{ item.deskripsi }}</p>
                  </td>
                  <td class="px-2 py-3 text-gray-600">{{ item.qtyRealisasi }}</td>
                  <td class="py-3 pl-2 text-right font-semibold text-gray-800">{{ item.subtotalAktual }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="border-t border-gray-200">
                  <td colspan="3" class="py-3 text-right text-sm text-gray-500">Total Realisasi Belanja (Valid):</td>
                  <td class="py-3 text-right text-lg font-bold text-[#C2410C]">{{ detailTotalRealisasi }}</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Bukti Nota & Kuitansi Terverifikasi -->
          <div class="mt-5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <ClipboardCheck class="h-4 w-4 text-gray-500" />
                <h4 class="text-sm font-bold text-gray-800">Bukti Nota &amp; Kuitansi Terverifikasi</h4>
              </div>
              <span class="flex items-center gap-1 text-xs text-green-600">
                <CheckCircle2 class="h-3 w-3" />
                Sah &amp; Sesuai Audit
              </span>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div
                v-for="(att, aIdx) in detailTarget.attachments"
                :key="aIdx"
                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3"
              >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" :class="att.iconBg">
                  <FileText class="h-5 w-5 text-[#C2410C]" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-xs font-semibold text-gray-800">{{ att.namaFile }}</p>
                  <p class="text-[10px] text-gray-400">{{ att.ukuran }} • {{ att.keterangan }}</p>
                </div>
                <div class="flex items-center gap-1">
                  <button
                    type="button"
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    title="Preview"
                    @click="previewAttachment(att.namaFile)"
                  >
                    <Eye class="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    title="Download"
                    @click="downloadAttachment(att.namaFile)"
                  >
                    <Download class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Modal (hanya tombol Tutup) -->
        <div class="border-t border-gray-100 p-5">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="closeDetailModal"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
