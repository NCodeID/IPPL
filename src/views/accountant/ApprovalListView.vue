<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Search,
  X,
  RotateCcw,
  FileText,
  CheckCircle2,
  XCircle,
  Wallet,
  ClipboardCheck,
  Eye,
  Clock,
  ChevronLeft,
  ChevronRight,
  Info,
  RefreshCw,
  Printer,
  ArrowRight,
  Warehouse,
  UtensilsCrossed,
} from 'lucide-vue-next'

const router = useRouter()

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe data untuk setiap item bahan baku dalam rincian PR ────────────────
interface PRItem {
  namaBahan: string
  deskripsi: string
  jumlah: string
  hargaEstimasi: string
  subtotal: string
  subtotalNum: number
}

// ─── Tipe data untuk setiap baris pengajuan Purchase Request ────────────────
interface PurchaseRequestRow {
  id: string
  nomorPengajuan: string
  tanggal: string
  pemohonNama: string
  pemohonInisial: string
  pemohonBg: string
  unitNama: string
  unitIcon: 'gudang' | 'dapur'
  estimasiBiaya: string
  estimasiBiayaNum: number
  jumlahSKU: number
  status: 'Menunggu Persetujuan' | 'Disetujui' | 'Ditolak'
  statusSubteks: string
  tujuanKeterangan: string
  tanggalKeputusan: string
  verifikator: string
  catatanAkuntan: string
  items: PRItem[]
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar semua pengajuan Purchase Request (data dummy) ───────────────────
// Akan diganti dengan data dari API backend Laravel nantinya.
const allPurchaseRequests = ref<PurchaseRequestRow[]>([
  {
    id: '1',
    nomorPengajuan: 'PR-2024-10-001',
    tanggal: '24 Okt 2024 • 08:30 WIB',
    pemohonNama: 'Doni Kurniawan',
    pemohonInisial: 'DK',
    pemohonBg: 'bg-blue-100 text-blue-700',
    unitNama: 'Gudang Utama',
    unitIcon: 'gudang',
    estimasiBiaya: 'Rp 2.450.000',
    estimasiBiayaNum: 2_450_000,
    jumlahSKU: 5,
    status: 'Menunggu Persetujuan',
    statusSubteks: '',
    tujuanKeterangan: 'Kebutuhan restock bahan baku kopi, susu, dan bahan kemasan untuk periode akhir pekan.',
    tanggalKeputusan: '',
    verifikator: '',
    catatanAkuntan: '',
    items: [
      { namaBahan: 'Kopi Arabika House Blend', deskripsi: 'Grade 1 Medium Dark Roast', jumlah: '10 Kg', hargaEstimasi: 'Rp 120.000', subtotal: 'Rp 1.200.000', subtotalNum: 1_200_000 },
      { namaBahan: 'Fresh Milk Full Cream', deskripsi: 'Pasteurisasi 1 Liter / karton', jumlah: '24 Liter', hargaEstimasi: 'Rp 22.000', subtotal: 'Rp 528.000', subtotalNum: 528_000 },
      { namaBahan: 'Gula Aren Cair Premium', deskripsi: 'Jerigen Food Grade 5L x 2', jumlah: '10 Liter', hargaEstimasi: 'Rp 35.000', subtotal: 'Rp 350.000', subtotalNum: 350_000 },
      { namaBahan: 'Paper Cup 12oz + Lid', deskripsi: 'Logo POS Restaurant Cetak', jumlah: '500 Pcs', hargaEstimasi: 'Rp 600', subtotal: 'Rp 300.000', subtotalNum: 300_000 },
      { namaBahan: 'Sedotan Ramah Lingkungan', deskripsi: 'Oxo-biodegradable isi 100/pack', jumlah: '10 Pack', hargaEstimasi: 'Rp 7.200', subtotal: 'Rp 72.000', subtotalNum: 72_000 },
    ],
  },
  {
    id: '2',
    nomorPengajuan: 'PR-2024-10-002',
    tanggal: '24 Okt 2024 • 09:15 WIB',
    pemohonNama: 'Doni Kurniawan',
    pemohonInisial: 'DK',
    pemohonBg: 'bg-blue-100 text-blue-700',
    unitNama: 'Gudang Utama',
    unitIcon: 'gudang',
    estimasiBiaya: 'Rp 1.120.000',
    estimasiBiayaNum: 1_120_000,
    jumlahSKU: 3,
    status: 'Menunggu Persetujuan',
    statusSubteks: '',
    tujuanKeterangan: 'Pengadaan ulang bahan pembersih dan perlengkapan sanitasi dapur mingguan.',
    tanggalKeputusan: '',
    verifikator: '',
    catatanAkuntan: '',
    items: [
      { namaBahan: 'Sabun Cuci Piring Industri', deskripsi: 'Jerigen 5 Liter Food Grade', jumlah: '4 Jerigen', hargaEstimasi: 'Rp 85.000', subtotal: 'Rp 340.000', subtotalNum: 340_000 },
      { namaBahan: 'Lap Microfiber Anti-Bakteri', deskripsi: 'Pack isi 10 lembar', jumlah: '10 Pack', hargaEstimasi: 'Rp 45.000', subtotal: 'Rp 450.000', subtotalNum: 450_000 },
      { namaBahan: 'Hand Sanitizer Refill 1L', deskripsi: 'Alkohol 70% Food Safe', jumlah: '6 Botol', hargaEstimasi: 'Rp 55.000', subtotal: 'Rp 330.000', subtotalNum: 330_000 },
    ],
  },
  {
    id: '3',
    nomorPengajuan: 'PR-2024-10-003',
    tanggal: '23 Okt 2024 • 16:40 WIB',
    pemohonNama: 'Siti Salimah',
    pemohonInisial: 'SS',
    pemohonBg: 'bg-purple-100 text-purple-700',
    unitNama: 'Dapur Utama',
    unitIcon: 'dapur',
    estimasiBiaya: 'Rp 3.890.000',
    estimasiBiayaNum: 3_890_000,
    jumlahSKU: 8,
    status: 'Menunggu Persetujuan',
    statusSubteks: '',
    tujuanKeterangan: 'Stok bahan masakan utama untuk menu baru dan antisipasi lonjakan pengunjung akhir pekan.',
    tanggalKeputusan: '',
    verifikator: '',
    catatanAkuntan: '',
    items: [
      { namaBahan: 'Daging Sapi Has Dalam', deskripsi: 'Grade A Lokal Segar', jumlah: '15 Kg', hargaEstimasi: 'Rp 130.000', subtotal: 'Rp 1.950.000', subtotalNum: 1_950_000 },
      { namaBahan: 'Ayam Kampung Utuh', deskripsi: 'Bobot 1.2-1.5 Kg', jumlah: '20 Ekor', hargaEstimasi: 'Rp 55.000', subtotal: 'Rp 1.100.000', subtotalNum: 1_100_000 },
      { namaBahan: 'Bawang Merah Brebes', deskripsi: 'Grade Super Kering', jumlah: '10 Kg', hargaEstimasi: 'Rp 32.000', subtotal: 'Rp 320.000', subtotalNum: 320_000 },
      { namaBahan: 'Cabai Rawit Merah', deskripsi: 'Segar Pilihan', jumlah: '5 Kg', hargaEstimasi: 'Rp 60.000', subtotal: 'Rp 300.000', subtotalNum: 300_000 },
      { namaBahan: 'Santan Kelapa Murni', deskripsi: 'Kemasan 1 Liter', jumlah: '8 Liter', hargaEstimasi: 'Rp 18.000', subtotal: 'Rp 144.000', subtotalNum: 144_000 },
      { namaBahan: 'Minyak Goreng Premium', deskripsi: 'Jerigen 5 Liter', jumlah: '2 Jerigen', hargaEstimasi: 'Rp 75.000', subtotal: 'Rp 150.000', subtotalNum: 150_000 },
      { namaBahan: 'Tepung Terigu Protein Tinggi', deskripsi: 'Karung 25 Kg', jumlah: '1 Karung', hargaEstimasi: 'Rp 185.000', subtotal: 'Rp 185.000', subtotalNum: 185_000 },
      { namaBahan: 'Garam Halus Beryodium', deskripsi: 'Kemasan 500g', jumlah: '10 Pack', hargaEstimasi: 'Rp 5.500', subtotal: 'Rp 55.000', subtotalNum: 55_000 },
    ],
  },
  {
    id: '4',
    nomorPengajuan: 'PR-2024-10-004',
    tanggal: '22 Okt 2024 • 11:20 WIB',
    pemohonNama: 'Doni Kurniawan',
    pemohonInisial: 'DK',
    pemohonBg: 'bg-blue-100 text-blue-700',
    unitNama: 'Gudang Utama',
    unitIcon: 'gudang',
    estimasiBiaya: 'Rp 950.000',
    estimasiBiayaNum: 950_000,
    jumlahSKU: 4,
    status: 'Disetujui',
    statusSubteks: 'oleh Ratna Sari',
    tujuanKeterangan: 'Pengadaan darurat minyak goreng, beras pandan wangi, telur ayam, dan rempah untuk pasokan dapur tengah minggu.',
    tanggalKeputusan: '22 Okt 2024, 13:45 WIB',
    verifikator: 'Ratna Sari (Akuntan)',
    catatanAkuntan: 'Pengajuan disetujui sesuai kuota mingguan. Mohon simpan seluruh bukti struk pembelian riil untuk verifikasi pada modul Settlement (FR-015).',
    items: [
      { namaBahan: 'Minyak Goreng Resto 5L', deskripsi: 'Jerigen Segel Kemasan Pabrik', jumlah: '2 Jerigen', hargaEstimasi: 'Rp 85.000', subtotal: 'Rp 170.000', subtotalNum: 170_000 },
      { namaBahan: 'Beras Pandan Wangi', deskripsi: 'Karung 25 Kg Grade Super', jumlah: '25 Kg', hargaEstimasi: 'Rp 16.000', subtotal: 'Rp 400.000', subtotalNum: 400_000 },
      { namaBahan: 'Telur Ayam Boiler', deskripsi: 'Segar Peternakan Lokal Grade A', jumlah: '10 Kg', hargaEstimasi: 'Rp 28.000', subtotal: 'Rp 280.000', subtotalNum: 280_000 },
      { namaBahan: 'Bumbu Rempah Halus', deskripsi: 'Kemasan Vakum Higienis', jumlah: '5 Pack', hargaEstimasi: 'Rp 20.000', subtotal: 'Rp 100.000', subtotalNum: 100_000 },
    ],
  },
  {
    id: '5',
    nomorPengajuan: 'PR-2024-10-005',
    tanggal: '21 Okt 2024 • 14:05 WIB',
    pemohonNama: 'Siti Salimah',
    pemohonInisial: 'SS',
    pemohonBg: 'bg-purple-100 text-purple-700',
    unitNama: 'Dapur Utama',
    unitIcon: 'dapur',
    estimasiBiaya: 'Rp 420.000',
    estimasiBiayaNum: 420_000,
    jumlahSKU: 2,
    status: 'Ditolak',
    statusSubteks: 'Di luar pos bahan baku',
    tujuanKeterangan: 'Pembelian peralatan masak tambahan yang tidak termasuk dalam pos anggaran bahan baku.',
    tanggalKeputusan: '21 Okt 2024, 15:30 WIB',
    verifikator: 'Ratna Sari (Akuntan)',
    catatanAkuntan: 'Pengajuan ditolak karena item yang diminta bukan termasuk pos bahan baku operasional. Silakan ajukan melalui form pengadaan inventaris (FR-018).',
    items: [
      { namaBahan: 'Wajan Anti Lengket 30cm', deskripsi: 'Teflon Grade Komersial', jumlah: '2 Unit', hargaEstimasi: 'Rp 150.000', subtotal: 'Rp 300.000', subtotalNum: 300_000 },
      { namaBahan: 'Spatula Stainless Steel', deskripsi: 'Panjang 35cm Food Grade', jumlah: '4 Pcs', hargaEstimasi: 'Rp 30.000', subtotal: 'Rp 120.000', subtotalNum: 120_000 },
    ],
  },
])

// ─── State pencarian & filter ───────────────────────────────────────────────
const searchQuery = ref<string>('')
const activeStatusTab = ref<string>('Semua Status')
const selectedPemohon = ref<string>('Semua Pemohon')

// ─── State pagination ───────────────────────────────────────────────────────
const currentPage = ref<number>(1)
const itemsPerPage = ref<number>(5)
const totalDataCount = ref<number>(23) // Total data simulasi (dummy)

// ─── State modal Tinjau Purchase Request ────────────────────────────────────
const showReviewModal = ref<boolean>(false)
const reviewTarget = ref<PurchaseRequestRow | null>(null)
const reviewNote = ref<string>('')
const reviewNoteError = ref<string>('')

// ─── State modal Detail Purchase Request ────────────────────────────────────
const showDetailModal = ref<boolean>(false)
const detailTarget = ref<PurchaseRequestRow | null>(null)

// ═══════════════════════════════════════════════════════════════════════════════
// COMPUTED (Kalkulasi Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar tab status filter beserta jumlah data "Menunggu" ────────────────
const statusTabs = computed(() => {
  const menungguCount = allPurchaseRequests.value.filter(
    (pr) => pr.status === 'Menunggu Persetujuan',
  ).length
  return [
    { label: 'Semua Status', count: 0 },
    { label: 'Menunggu', count: menungguCount },
    { label: 'Disetujui', count: 0 },
    { label: 'Ditolak', count: 0 },
  ]
})

// ─── Daftar nama pemohon unik untuk dropdown filter ─────────────────────────
const pemohonOptions = computed<string[]>(() => {
  const names = allPurchaseRequests.value.map((pr) => pr.pemohonNama)
  return ['Semua Pemohon', ...new Set(names)]
})

// ─── Data yang sudah difilter berdasarkan pencarian, tab status, dan pemohon ─
const filteredRequests = computed<PurchaseRequestRow[]>(() => {
  let result = allPurchaseRequests.value

  // Filter berdasarkan tab status aktif
  if (activeStatusTab.value === 'Menunggu') {
    result = result.filter((pr) => pr.status === 'Menunggu Persetujuan')
  } else if (activeStatusTab.value === 'Disetujui') {
    result = result.filter((pr) => pr.status === 'Disetujui')
  } else if (activeStatusTab.value === 'Ditolak') {
    result = result.filter((pr) => pr.status === 'Ditolak')
  }

  // Filter berdasarkan dropdown pemohon
  if (selectedPemohon.value !== 'Semua Pemohon') {
    result = result.filter((pr) => pr.pemohonNama === selectedPemohon.value)
  }

  // Filter berdasarkan kata kunci pencarian (case-insensitive, trim spasi)
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    result = result.filter(
      (pr) =>
        pr.nomorPengajuan.toLowerCase().includes(q) ||
        pr.pemohonNama.toLowerCase().includes(q) ||
        pr.unitNama.toLowerCase().includes(q),
    )
  }

  return result
})

// ─── Data yang ditampilkan pada halaman saat ini (pagination) ───────────────
const paginatedRequests = computed<PurchaseRequestRow[]>(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredRequests.value.slice(start, start + itemsPerPage.value)
})

// ─── Total halaman berdasarkan jumlah data yang telah difilter ──────────────
const totalPages = computed<number>(() =>
  Math.max(1, Math.ceil(filteredRequests.value.length / itemsPerPage.value)),
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

// ─── Kartu statistik ringkasan yang dihitung dari data aktual ───────────────
const statMenunggu = computed<number>(
  () => allPurchaseRequests.value.filter((pr) => pr.status === 'Menunggu Persetujuan').length,
)
const statDisetujui = computed<number>(
  () => allPurchaseRequests.value.filter((pr) => pr.status === 'Disetujui').length,
)
const statDitolak = computed<number>(
  () => allPurchaseRequests.value.filter((pr) => pr.status === 'Ditolak').length,
)
const statEstimasiMenunggu = computed<string>(() => {
  const total = allPurchaseRequests.value
    .filter((pr) => pr.status === 'Menunggu Persetujuan')
    .reduce((sum, pr) => sum + pr.estimasiBiayaNum, 0)
  return `Rp ${total.toLocaleString('id-ID')}`
})

// ─── Hitung total estimasi biaya dari item rincian PR yang sedang ditinjau ──
const reviewTotalEstimasi = computed<string>(() => {
  if (!reviewTarget.value) return 'Rp 0'
  const total = reviewTarget.value.items.reduce((sum, item) => sum + item.subtotalNum, 0)
  return `Rp ${total.toLocaleString('id-ID')}`
})

// ─── Hitung total biaya dari item rincian PR yang sedang dilihat detailnya ──
const detailTotalBiaya = computed<string>(() => {
  if (!detailTarget.value) return 'Rp 0'
  const total = detailTarget.value.items.reduce((sum, item) => sum + item.subtotalNum, 0)
  return `Rp ${total.toLocaleString('id-ID')}`
})

// ═══════════════════════════════════════════════════════════════════════════════
// WATCHER
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Reset halaman ke 1 setiap kali pencarian, tab status, atau pemohon berubah ─
// Mencegah tabel tampak kosong karena terjebak di halaman yang sudah tidak valid
watch([searchQuery, activeStatusTab, selectedPemohon], () => {
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

// ─── Mereset semua filter pencarian, tab status, dan dropdown ke kondisi awal ─
function resetFilters(): void {
  searchQuery.value = ''
  activeStatusTab.value = 'Semua Status'
  selectedPemohon.value = 'Semua Pemohon'
}

// ─── Membuka modal Tinjau untuk PR berstatus "Menunggu Persetujuan" ─────────
function openReviewModal(pr: PurchaseRequestRow): void {
  reviewTarget.value = pr
  reviewNote.value = ''
  reviewNoteError.value = ''
  showReviewModal.value = true
}

// ─── Menutup modal Tinjau ───────────────────────────────────────────────────
function closeReviewModal(): void {
  showReviewModal.value = false
  reviewTarget.value = null
  reviewNote.value = ''
  reviewNoteError.value = ''
}

// ─── Membuka modal Detail untuk PR berstatus "Disetujui" atau "Ditolak" ─────
function openDetailModal(pr: PurchaseRequestRow): void {
  detailTarget.value = pr
  showDetailModal.value = true
}

// ─── Menutup modal Detail ───────────────────────────────────────────────────
function closeDetailModal(): void {
  showDetailModal.value = false
  detailTarget.value = null
}

// ─── Menyetujui pengajuan Purchase Request ──────────────────────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint POST /api/accountant/purchase-requests/{id}/approve
// dengan payload { catatan: reviewNote.value }
function approvePurchaseRequest(id: string, note: string): void {
  // [API-READY] Contoh pemanggilan API:
  // await api.post(`/accountant/purchase-requests/${id}/approve`, { catatan: note })

  // Update status pada data lokal secara reaktif
  const pr = allPurchaseRequests.value.find((p) => p.id === id)
  if (pr) {
    pr.status = 'Disetujui'
    pr.statusSubteks = 'oleh Ratna Sari'
    pr.catatanAkuntan = note || 'Pengajuan disetujui.'
    pr.tanggalKeputusan = '24 Okt 2024, 14:00 WIB'
    pr.verifikator = 'Ratna Sari (Akuntan)'
  }
  closeReviewModal()
}

// ─── Menolak pengajuan Purchase Request ─────────────────────────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint POST /api/accountant/purchase-requests/{id}/reject
// dengan payload { catatan: reviewNote.value }
function rejectPurchaseRequest(id: string, note: string): void {
  // Validasi: catatan wajib diisi jika menolak
  if (!note.trim()) {
    reviewNoteError.value = 'Alasan penolakan wajib diisi sebelum menolak pengajuan.'
    return
  }

  // [API-READY] Contoh pemanggilan API:
  // await api.post(`/accountant/purchase-requests/${id}/reject`, { catatan: note })

  // Update status pada data lokal secara reaktif
  const pr = allPurchaseRequests.value.find((p) => p.id === id)
  if (pr) {
    pr.status = 'Ditolak'
    pr.statusSubteks = note.length > 30 ? note.substring(0, 30) + '...' : note
    pr.catatanAkuntan = note
    pr.tanggalKeputusan = '24 Okt 2024, 14:00 WIB'
    pr.verifikator = 'Ratna Sari (Akuntan)'
  }
  closeReviewModal()
}

// ─── Menangani klik tombol Setujui pada modal Tinjau ────────────────────────
function handleApprove(): void {
  if (!reviewTarget.value) return
  reviewNoteError.value = ''
  approvePurchaseRequest(reviewTarget.value.id, reviewNote.value)
}

// ─── Menangani klik tombol Tolak pada modal Tinjau ──────────────────────────
function handleReject(): void {
  if (!reviewTarget.value) return
  rejectPurchaseRequest(reviewTarget.value.id, reviewNote.value)
}

// ─── Navigasi ke halaman settlement ─────────────────────────────────────────
function goToSettlement(): void {
  closeDetailModal()
  router.push('/accountant/settlements')
}

// ─── Mengambil data pengajuan PR dari API backend ───────────────────────────
// [API-READY] Saat backend Laravel sudah siap, fungsi ini akan memanggil
// endpoint GET /api/accountant/purchase-requests dan mengisi
// allPurchaseRequests dengan data dari server.
async function fetchPurchaseRequests(): Promise<void> {
  // [API-READY] Contoh pemanggilan API:
  // const { data } = await api.get('/accountant/purchase-requests')
  // allPurchaseRequests.value = data.purchaseRequests
  // totalDataCount.value = data.total
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memuat data saat komponen pertama kali dipasang (mounted) ──────────────
onMounted(() => {
  fetchPurchaseRequests()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Header Halaman ═══════════ -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="text-2xl font-bold text-gray-900">Approval Purchase Request</h1>
          <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">FR-013</span>
          <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-600">Data Aktif</span>
        </div>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Tinjau dan proses persetujuan pengajuan pembelian bahan baku dari unit gudang dan dapur
          sebelum otorisasi pengadaan resmi diterbitkan.
        </p>
      </div>

      <!-- Indikator sinkronisasi & waktu -->
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-1.5 text-xs text-green-600">
          <span class="h-2 w-2 rounded-full bg-green-500"></span>
          <span class="font-medium">Sinkronisasi Otomatis</span>
        </div>
        <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
          <Clock class="mr-1 inline h-3 w-3" />08:42 WIB
        </span>
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50"
          title="Refresh data"
        >
          <RefreshCw class="h-3.5 w-3.5" />
        </button>
      </div>
    </div>

    <!-- ═══════════ 4 Kartu Ringkasan Status ═══════════ -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      <!-- Kartu 1: Menunggu Persetujuan -->
      <div class="rounded-xl border border-gray-100 bg-white p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-500">Menunggu Persetujuan</p>
              <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Perlu Tindakan</span>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ statMenunggu }}</p>
            <p class="text-sm text-gray-500">Berkas PR</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50">
            <FileText class="h-5 w-5 text-[#C2410C]" />
          </div>
        </div>
        <div class="mt-3 h-1 w-full overflow-hidden rounded-full bg-gray-100">
          <div class="h-full rounded-full bg-[#C2410C]" :style="{ width: '40%' }"></div>
        </div>
      </div>

      <!-- Kartu 2: Disetujui Bulan Ini -->
      <div class="rounded-xl border border-gray-100 bg-white p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-500">Disetujui Bulan Ini</p>
              <span class="rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-semibold text-green-700">Telah Disetujui</span>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900">18</p>
            <p class="text-sm text-gray-500">Berkas PR</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50">
            <CheckCircle2 class="h-5 w-5 text-green-600" />
          </div>
        </div>
        <div class="mt-3 h-1 w-full overflow-hidden rounded-full bg-gray-100">
          <div class="h-full rounded-full bg-green-500" :style="{ width: '75%' }"></div>
        </div>
      </div>

      <!-- Kartu 3: Ditolak / Revisi -->
      <div class="rounded-xl border border-gray-100 bg-white p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-500">Ditolak / Revisi</p>
              <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-600">Tidak Lolos</span>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ statDitolak }}</p>
            <p class="text-sm text-gray-500">Berkas</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50">
            <XCircle class="h-5 w-5 text-red-500" />
          </div>
        </div>
        <div class="mt-3 h-1 w-full overflow-hidden rounded-full bg-gray-100">
          <div class="h-full rounded-full bg-red-400" :style="{ width: '15%' }"></div>
        </div>
      </div>

      <!-- Kartu 4: Estimasi Menunggu -->
      <div class="rounded-xl border border-gray-100 bg-white p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-500">Estimasi Menunggu</p>
              <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600">Anggaran Pending</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-[#C2410C]">{{ statEstimasiMenunggu }}</p>
            <p class="text-sm text-gray-500">{{ statMenunggu }} pengajuan aktif</p>
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
            placeholder="Cari nomor PR (cth: PR-2024...), pemohon, atau unit..."
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

        <!-- Tab pill filter status -->
        <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1">
          <button
            v-for="tab in statusTabs"
            :key="tab.label"
            type="button"
            :class="[
              'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition',
              activeStatusTab === tab.label
                ? 'bg-white text-gray-800 shadow-sm'
                : 'text-gray-500 hover:text-gray-700',
            ]"
            @click="activeStatusTab = tab.label"
          >
            {{ tab.label }}
            <span
              v-if="tab.count > 0"
              class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#C2410C] px-1.5 text-[10px] font-bold text-white"
            >
              {{ tab.count }}
            </span>
          </button>
        </div>

        <!-- Dropdown pemohon -->
        <select
          v-model="selectedPemohon"
          class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 transition focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30 sm:w-48"
        >
          <option v-for="opt in pemohonOptions" :key="opt" :value="opt">{{ opt }}</option>
        </select>

        <!-- Tombol Reset -->
        <button
          type="button"
          class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
          @click="resetFilters"
        >
          <RotateCcw class="h-3.5 w-3.5" />
          Reset
        </button>
      </div>
    </div>

    <!-- ═══════════ Tabel Daftar Pengajuan ═══════════ -->
    <div class="rounded-xl border border-gray-100 bg-white">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[900px]">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="w-14 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">No.</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nomor Pengajuan</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Pemohon &amp; Unit</th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Estimasi Biaya</th>
              <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Status</th>
              <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(pr, idx) in paginatedRequests"
              :key="pr.id"
              :class="idx < paginatedRequests.length - 1 ? 'border-b border-gray-50' : ''"
            >
              <!-- Kolom NO -->
              <td class="px-5 py-4 text-sm text-gray-500">
                {{ (currentPage - 1) * itemsPerPage + idx + 1 }}
              </td>

              <!-- Kolom NOMOR PENGAJUAN -->
              <td class="px-5 py-4">
                <p class="text-sm font-bold text-[#C2410C]">{{ pr.nomorPengajuan }}</p>
                <p class="text-xs text-gray-400">{{ pr.tanggal }}</p>
              </td>

              <!-- Kolom PEMOHON & UNIT -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-2.5">
                  <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                    :class="pr.pemohonBg"
                  >
                    {{ pr.pemohonInisial }}
                  </span>
                  <div>
                    <p class="text-sm font-semibold text-gray-800">{{ pr.pemohonNama }}</p>
                    <p class="flex items-center gap-1 text-xs text-gray-400">
                      <Warehouse v-if="pr.unitIcon === 'gudang'" class="h-3 w-3" />
                      <UtensilsCrossed v-else class="h-3 w-3" />
                      {{ pr.unitNama }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Kolom ESTIMASI BIAYA -->
              <td class="px-5 py-4 text-right">
                <p class="text-sm font-bold text-gray-800">{{ pr.estimasiBiaya }}</p>
                <p class="text-xs text-gray-400">{{ pr.jumlahSKU }} SKU</p>
              </td>

              <!-- Kolom STATUS -->
              <td class="px-5 py-4 text-center">
                <!-- Menunggu Persetujuan -->
                <template v-if="pr.status === 'Menunggu Persetujuan'">
                  <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                    <Clock class="h-3 w-3" />
                    Menunggu Persetujuan
                  </span>
                </template>
                <!-- Disetujui -->
                <template v-else-if="pr.status === 'Disetujui'">
                  <div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
                      <CheckCircle2 class="h-3 w-3" />
                      Disetujui
                    </span>
                    <p class="mt-0.5 text-[10px] text-gray-400">{{ pr.statusSubteks }}</p>
                  </div>
                </template>
                <!-- Ditolak -->
                <template v-else>
                  <div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-600">
                      <XCircle class="h-3 w-3" />
                      Ditolak
                    </span>
                    <p class="mt-0.5 text-[10px] text-gray-400">{{ pr.statusSubteks }}</p>
                  </div>
                </template>
              </td>

              <!-- Kolom AKSI -->
              <td class="px-5 py-4 text-center">
                <!-- Tombol Tinjau (untuk status Menunggu) -->
                <button
                  v-if="pr.status === 'Menunggu Persetujuan'"
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-[#C2410C] px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-[#B45309]"
                  @click="openReviewModal(pr)"
                >
                  <ClipboardCheck class="h-3.5 w-3.5" />
                  Tinjau
                </button>
                <!-- Tombol Detail (untuk status Disetujui / Ditolak) -->
                <button
                  v-else
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50"
                  @click="openDetailModal(pr)"
                >
                  <Eye class="h-3.5 w-3.5" />
                  Detail
                </button>
              </td>
            </tr>

            <!-- Baris kosong jika tidak ada data -->
            <tr v-if="paginatedRequests.length === 0">
              <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">
                Tidak ada pengajuan yang sesuai dengan filter.
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
            {{ (currentPage - 1) * itemsPerPage + 1 }} -
            {{ Math.min(currentPage * itemsPerPage, filteredRequests.length) }}
          </span>
          dari
          <span class="font-semibold text-gray-800">{{ totalDataCount }}</span>
          pengajuan
        </p>

        <!-- Pagination -->
        <div class="flex items-center gap-1.5">
          <!-- Tombol Sebelumnya -->
          <button
            type="button"
            :disabled="currentPage <= 1"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage - 1)"
          >
            <ChevronLeft class="h-3.5 w-3.5" /> Sebelumnya
          </button>

          <!-- Nomor halaman -->
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

          <!-- Tombol Berikutnya -->
          <button
            type="button"
            :disabled="currentPage >= totalPages"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage + 1)"
          >
            Berikutnya <ChevronRight class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ Info Box: Alur Otorisasi ═══════════ -->
    <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/50 p-5">
      <div class="flex items-start gap-3">
        <Info class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" />
        <div class="text-sm text-gray-700">
          <p class="font-bold">Alur Otorisasi Purchase Request (SKPL POS Restaurant)</p>
          <p class="mt-1">
            Catatan: Purchase Request yang disetujui oleh Akuntan akan diteruskan statusnya menjadi
            <span class="font-bold">siap dibelanjakan</span> oleh petugas gudang, dan kemudian
            diverifikasi kembali pada tahap
            <RouterLink to="/accountant/settlements" class="font-bold text-[#C2410C] hover:underline">
              Settlement (FR-015)
            </RouterLink>
            beserta bukti kuitansi riil pembelanjaan.
          </p>
        </div>
      </div>
    </div>

    <!-- ═══════════ MODAL: Tinjau Purchase Request ═══════════ -->
    <div
      v-if="showReviewModal && reviewTarget"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeReviewModal"
    >
      <div class="flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl" style="max-height: 90vh">
        <!-- Header Modal -->
        <div class="flex items-start justify-between border-b border-gray-100 p-5">
          <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50">
              <ClipboardCheck class="h-5 w-5 text-amber-600" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-bold text-gray-900">Tinjau Purchase Request</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">FR-013</span>
              </div>
              <p class="mt-0.5 text-sm text-gray-500">
                Verifikasi rincian kebutuhan bahan dan anggaran sebelum memberikan otorisasi belanja (FR-013)
              </p>
            </div>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
            @click="closeReviewModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Body Modal (scrollable) -->
        <div class="flex-1 overflow-y-auto p-5">
          <!-- Kartu ringkasan 2 kolom -->
          <div class="grid grid-cols-1 gap-4 rounded-xl bg-slate-50 p-4 sm:grid-cols-2">
            <div class="space-y-2">
              <div>
                <p class="text-xs text-gray-400">Nomor PR:</p>
                <div class="flex items-center gap-2">
                  <span class="text-sm font-bold text-gray-800">{{ reviewTarget.nomorPengajuan }}</span>
                  <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700">
                    • Menunggu Persetujuan
                  </span>
                </div>
              </div>
              <div>
                <p class="text-xs text-gray-400">Tanggal &amp; Waktu:</p>
                <p class="text-sm text-gray-700">{{ reviewTarget.tanggal }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Pemohon:</p>
                <p class="text-sm font-semibold text-gray-800">
                  {{ reviewTarget.pemohonNama }} (Kepala {{ reviewTarget.unitNama }})
                </p>
              </div>
            </div>
            <div class="space-y-2">
              <div>
                <p class="text-xs text-gray-400">Tujuan / Keterangan:</p>
                <p class="text-sm text-gray-700">{{ reviewTarget.tujuanKeterangan }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Total Estimasi Anggaran:</p>
                <p class="text-xl font-bold text-[#C2410C]">{{ reviewTotalEstimasi }}</p>
              </div>
            </div>
          </div>

          <!-- Tabel rincian item bahan baku -->
          <div class="mt-5">
            <div class="flex items-baseline gap-2">
              <h4 class="text-sm font-bold text-gray-800">Rincian Item Bahan Baku</h4>
              <span class="text-xs text-gray-400">({{ reviewTarget.items.length }} SKU Diajukan)</span>
            </div>
            <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200">
              <table class="w-full min-w-[550px] text-sm">
                <thead>
                  <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">Nama Bahan</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase text-gray-500">Jumlah (QTY)</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500">Harga Estimasi</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, iIdx) in reviewTarget.items"
                    :key="iIdx"
                    :class="iIdx < reviewTarget.items.length - 1 ? 'border-b border-gray-100' : ''"
                  >
                    <td class="px-4 py-3">
                      <p class="font-medium text-gray-800">{{ item.namaBahan }}</p>
                      <p class="text-xs text-gray-400">{{ item.deskripsi }}</p>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ item.jumlah }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ item.hargaEstimasi }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ item.subtotal }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="border-t border-gray-200 bg-gray-50">
                    <td class="px-4 py-3 text-sm font-bold text-gray-800">
                      Total Pengajuan: {{ reviewTarget.items.length }} Item Bahan
                    </td>
                    <td colspan="2" class="px-4 py-3 text-right text-sm text-gray-500">Total Estimasi Biaya:</td>
                    <td class="px-4 py-3 text-right text-lg font-bold text-[#C2410C]">{{ reviewTotalEstimasi }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Textarea Catatan Akuntan -->
          <div class="mt-5">
            <label class="text-sm font-semibold text-gray-800">
              Catatan Akuntan (Wajib diisi jika ditolak, opsional jika disetujui):
            </label>
            <textarea
              v-model="reviewNote"
              rows="3"
              class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 transition placeholder:text-gray-400 focus:border-[#C2410C] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#C2410C]/30"
              placeholder="Tuliskan catatan verifikasi atau alasan penolakan/revisi pengajuan..."
              @input="reviewNoteError = ''"
            ></textarea>
            <!-- Pesan error validasi -->
            <p v-if="reviewNoteError" class="mt-1 text-xs font-medium text-red-500">
              {{ reviewNoteError }}
            </p>
          </div>
        </div>

        <!-- Footer Modal -->
        <div class="flex items-center justify-between border-t border-gray-100 p-5">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="closeReviewModal"
          >
            Tutup / Batal
          </button>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
              @click="handleReject"
            >
              <X class="h-4 w-4" />
              Tolak Pengajuan
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-[#C2410C] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#B45309]"
              @click="handleApprove"
            >
              <CheckCircle2 class="h-4 w-4" />
              Setujui Pengajuan
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════ MODAL: Detail Purchase Request ═══════════ -->
    <div
      v-if="showDetailModal && detailTarget"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeDetailModal"
    >
      <div class="flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl" style="max-height: 90vh">
        <!-- Header Modal -->
        <div class="flex items-start justify-between border-b border-gray-100 p-5">
          <div class="flex items-start gap-3">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              :class="detailTarget.status === 'Disetujui' ? 'bg-green-50' : 'bg-red-50'"
            >
              <CheckCircle2 v-if="detailTarget.status === 'Disetujui'" class="h-5 w-5 text-green-600" />
              <XCircle v-else class="h-5 w-5 text-red-500" />
            </div>
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-lg font-bold text-gray-900">Detail Purchase Request</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">FR-013</span>
                <span
                  :class="[
                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                    detailTarget.status === 'Disetujui'
                      ? 'bg-green-50 text-green-700'
                      : 'bg-red-50 text-red-600',
                  ]"
                >
                  <CheckCircle2 v-if="detailTarget.status === 'Disetujui'" class="h-3 w-3" />
                  <XCircle v-else class="h-3 w-3" />
                  {{ detailTarget.status }}
                </span>
              </div>
              <p class="mt-0.5 text-sm text-gray-500">
                Informasi lengkap pengajuan pembelian bahan baku dan status otorisasi belanja.
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
          <!-- Kartu ringkasan 2 kolom -->
          <div class="grid grid-cols-1 gap-4 rounded-xl bg-slate-50 p-4 sm:grid-cols-2">
            <div class="space-y-2">
              <div>
                <p class="text-xs text-gray-400">Nomor PR:</p>
                <span class="text-sm font-bold text-gray-800">{{ detailTarget.nomorPengajuan }}</span>
              </div>
              <div>
                <p class="text-xs text-gray-400">Tanggal Pengajuan:</p>
                <p class="text-sm text-gray-700">{{ detailTarget.tanggal }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Pemohon &amp; Unit:</p>
                <p class="text-sm font-semibold text-gray-800">
                  {{ detailTarget.pemohonNama }} ({{ detailTarget.unitNama }})
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Diverifikasi Oleh:</p>
                <p class="text-sm text-gray-700">{{ detailTarget.verifikator }}</p>
              </div>
            </div>
            <div class="space-y-2">
              <div>
                <p class="text-xs text-gray-400">Keperluan / Keterangan:</p>
                <p class="text-sm text-gray-700">{{ detailTarget.tujuanKeterangan }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400">
                  {{ detailTarget.status === 'Disetujui' ? 'Tanggal Disetujui:' : 'Tanggal Ditolak:' }}
                </p>
                <p class="text-sm text-gray-700">{{ detailTarget.tanggalKeputusan }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Total Anggaran:</p>
                <p class="text-xl font-bold text-[#C2410C]">{{ detailTotalBiaya }}</p>
              </div>
            </div>
          </div>

          <!-- Tabel rincian item bahan baku -->
          <div class="mt-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <div class="flex items-baseline gap-2">
                <h4 class="text-sm font-bold text-gray-800">Rincian Item Bahan Baku</h4>
                <span class="text-xs text-gray-400">({{ detailTarget.items.length }} SKU {{ detailTarget.status === 'Disetujui' ? 'Disetujui' : 'Ditolak' }})</span>
              </div>
              <span class="flex items-center gap-1 text-xs text-green-600">
                <CheckCircle2 class="h-3 w-3" />
                Diverifikasi oleh {{ detailTarget.verifikator }}
              </span>
            </div>
            <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200">
              <table class="w-full min-w-[550px] text-sm">
                <thead>
                  <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">Nama Bahan / Deskripsi</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase text-gray-500">QTY</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500">Harga Estimasi</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, iIdx) in detailTarget.items"
                    :key="iIdx"
                    :class="iIdx < detailTarget.items.length - 1 ? 'border-b border-gray-100' : ''"
                  >
                    <td class="px-4 py-3">
                      <p class="font-medium text-gray-800">{{ item.namaBahan }}</p>
                      <p class="text-xs text-gray-400">{{ item.deskripsi }}</p>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ item.jumlah }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ item.hargaEstimasi }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ item.subtotal }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="border-t border-gray-200 bg-gray-50">
                    <td class="px-4 py-3 text-sm font-bold text-gray-800">
                      Total Pengajuan: {{ detailTarget.items.length }} Item Bahan
                    </td>
                    <td colspan="2" class="px-4 py-3 text-right text-sm text-gray-500">Total Biaya:</td>
                    <td class="px-4 py-3 text-right text-lg font-bold text-[#C2410C]">{{ detailTotalBiaya }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Kotak catatan akuntan -->
          <div
            class="mt-5 rounded-xl p-4"
            :class="detailTarget.status === 'Disetujui' ? 'bg-green-50 border border-green-100' : 'bg-red-50 border border-red-100'"
          >
            <div class="flex items-center gap-2 text-sm font-semibold" :class="detailTarget.status === 'Disetujui' ? 'text-green-700' : 'text-red-700'">
              <CheckCircle2 v-if="detailTarget.status === 'Disetujui'" class="h-4 w-4" />
              <XCircle v-else class="h-4 w-4" />
              Catatan Akuntan ({{ detailTarget.verifikator.split(' (')[0] ?? '' }})
              <span class="ml-auto text-xs font-normal text-gray-400">{{ detailTarget.tanggalKeputusan }}</span>
            </div>
            <p class="mt-2 text-sm text-gray-700">{{ detailTarget.catatanAkuntan }}</p>
          </div>

          <!-- Status Alur Berikutnya -->
          <div
            class="mt-4 flex items-center gap-2 rounded-xl border p-3 text-sm"
            :class="
              detailTarget.status === 'Disetujui'
                ? 'border-green-100 bg-green-50/50 text-green-700'
                : 'border-red-100 bg-red-50/50 text-red-600'
            "
          >
            <ArrowRight class="h-4 w-4 shrink-0" />
            <span v-if="detailTarget.status === 'Disetujui'">
              Status Alur: <strong>Siap Dibelanjakan / Menunggu Settlement</strong> oleh petugas gudang.
            </span>
            <span v-else>
              Status Alur: <strong>Ditolak</strong> — Pengajuan tidak dilanjutkan ke tahap pembelanjaan.
            </span>
          </div>
        </div>

        <!-- Footer Modal -->
        <div class="flex items-center justify-between border-t border-gray-100 p-5">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="closeDetailModal"
          >
            Tutup
          </button>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
              <Printer class="h-4 w-4" />
              Cetak Berkas PR
            </button>
            <button
              v-if="detailTarget.status === 'Disetujui'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-[#C2410C] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#B45309]"
              @click="goToSettlement"
            >
              <ClipboardCheck class="h-4 w-4" />
              Lihat Status Settlement
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
