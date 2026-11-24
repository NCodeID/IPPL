<script setup lang="ts">
import { ref, computed, watch, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Plus,
  Search,
  X,
  RotateCcw,
  Eye,
  Pencil,
  Trash2,
  Info,
  AlertTriangle,
  Armchair,
  CheckCircle,
  Users,
  CalendarClock,
  UtensilsCrossed,
  Check,
  SquarePen,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe status meja yang diizinkan ────────────────────────────────────────
type TableStatus = 'Tersedia' | 'Digunakan' | 'Reservasi'

// ─── Representasi satu entitas meja dine-in ─────────────────────────────────
// `code` digunakan sebagai kode unik untuk identifikasi visual (contoh: MJ-001)
// `capacity` menyimpan jumlah kursi pada meja
// `area` menyimpan lokasi atau zona meja di restoran
// `notes` menyimpan catatan opsional tentang meja (posisi, kondisi, dll)
interface TableItem {
  id: number
  code: string
  name: string
  capacity: number
  area: string
  status: TableStatus
  notes: string
}

// ─── Data form untuk membuat atau mengedit meja ─────────────────────────────
interface TableForm {
  name: string
  capacity: number
  area: string
  status: TableStatus
  notes: string
}

// ═══════════════════════════════════════════════════════════════════════════════
// ROUTING
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Router untuk navigasi ke halaman detail ────────────────────────────────
const router = useRouter()

// ═══════════════════════════════════════════════════════════════════════════════
// IKON HELPER — Ikon meja yang digunakan secara konsisten
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Ikon default untuk meja (di-markRaw agar tidak reaktif) ────────────────
const tableIcon: Component = markRaw(UtensilsCrossed)

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar seluruh meja (sumber data utama) ────────────────────────────────
// Data dummy awal lengkap dengan `code`, `capacity`, `area`, `status`, dan `notes`.
const tables = ref<TableItem[]>([
  { id: 1, code: 'MJ-001', name: 'Meja 01', capacity: 4, area: 'Area Indoor Utama', status: 'Tersedia', notes: 'Dekat pintu masuk utama' },
  { id: 2, code: 'MJ-002', name: 'Meja 02', capacity: 2, area: 'Area Jendela', status: 'Digunakan', notes: 'Dekat jendela depan' },
  { id: 3, code: 'MJ-003', name: 'Meja 03', capacity: 4, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' },
  { id: 4, code: 'MJ-004', name: 'Meja 04', capacity: 6, area: 'Area VIP / Keluarga', status: 'Digunakan', notes: 'Dekat stopkontak dan jendela utama' },
  { id: 5, code: 'MJ-005', name: 'Meja 05', capacity: 4, area: 'Area Teras / Outdoor', status: 'Tersedia', notes: 'Area merokok outdoor' },
  { id: 6, code: 'MJ-006', name: 'Meja 06', capacity: 2, area: 'Area Jendela', status: 'Reservasi', notes: 'Reservasi malam' },
  { id: 7, code: 'MJ-007', name: 'Meja 07', capacity: 4, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' },
  { id: 8, code: 'MJ-008', name: 'Meja 08', capacity: 2, area: 'Area Jendela', status: 'Digunakan', notes: '' },
  { id: 9, code: 'MJ-009', name: 'Meja 09', capacity: 6, area: 'Area VIP / Keluarga', status: 'Tersedia', notes: 'Ruangan tertutup' },
  { id: 10, code: 'MJ-010', name: 'Meja 10', capacity: 4, area: 'Area Teras / Outdoor', status: 'Tersedia', notes: '' },
  { id: 11, code: 'MJ-011', name: 'Meja 11', capacity: 2, area: 'Area Indoor Utama', status: 'Digunakan', notes: '' },
  { id: 12, code: 'MJ-012', name: 'Meja 12', capacity: 4, area: 'Area Jendela', status: 'Tersedia', notes: '' },
  { id: 13, code: 'MJ-013', name: 'Meja 13', capacity: 4, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' },
  { id: 14, code: 'MJ-014', name: 'Meja 14', capacity: 6, area: 'Area VIP / Keluarga', status: 'Reservasi', notes: '' },
  { id: 15, code: 'MJ-015', name: 'Meja 15', capacity: 2, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' },
  { id: 16, code: 'MJ-016', name: 'Meja 16', capacity: 4, area: 'Area Teras / Outdoor', status: 'Tersedia', notes: '' },
  { id: 17, code: 'MJ-017', name: 'Meja 17', capacity: 4, area: 'Area Indoor Utama', status: 'Digunakan', notes: '' },
  { id: 18, code: 'MJ-018', name: 'Meja 18', capacity: 2, area: 'Area Jendela', status: 'Tersedia', notes: '' },
  { id: 19, code: 'MJ-019', name: 'Meja 19', capacity: 4, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' },
  { id: 20, code: 'MJ-020', name: 'Meja 20', capacity: 6, area: 'Area VIP / Keluarga', status: 'Tersedia', notes: '' },
  { id: 21, code: 'MJ-021', name: 'Meja 21', capacity: 2, area: 'Area Jendela', status: 'Digunakan', notes: '' },
  { id: 22, code: 'MJ-022', name: 'Meja 22', capacity: 4, area: 'Area Teras / Outdoor', status: 'Tersedia', notes: '' },
  { id: 23, code: 'MJ-023', name: 'Meja 23', capacity: 4, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' },
  { id: 24, code: 'MJ-024', name: 'Meja 24', capacity: 2, area: 'Area Jendela', status: 'Reservasi', notes: '' },
  { id: 25, code: 'MJ-025', name: 'Meja 25', capacity: 4, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' },
  { id: 26, code: 'MJ-026', name: 'Meja 26', capacity: 6, area: 'Area VIP / Keluarga', status: 'Reservasi', notes: '' },
  { id: 27, code: 'MJ-027', name: 'Meja 27', capacity: 4, area: 'Area Teras / Outdoor', status: 'Tersedia', notes: '' },
  { id: 28, code: 'MJ-028', name: 'Meja 28', capacity: 2, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' },
])

// ─── Kata kunci pencarian untuk memfilter tabel ─────────────────────────────
const searchQuery = ref('')

// ─── Filter status yang sedang aktif ('' = semua status) ────────────────────
const statusFilter = ref<'' | TableStatus>('')

// ─── Halaman aktif saat ini pada pagination ─────────────────────────────────
const currentPage = ref(1)

// ─── Jumlah baris per halaman pada tabel ────────────────────────────────────
const itemsPerPage = ref(6)

// ─── Visibilitas masing-masing modal (true = tampil) ────────────────────────
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

// ─── Meja yang sedang dipilih untuk aksi edit / hapus ───────────────────────
const selectedTable = ref<TableItem | null>(null)

// ─── Data form yang digunakan di modal tambah & edit ────────────────────────
const formData = ref<TableForm>({
  name: '',
  capacity: 4,
  area: 'Area Indoor Utama',
  status: 'Tersedia',
  notes: '',
})

// ═══════════════════════════════════════════════════════════════════════════════
// COMPUTED — Statistik Kartu Ringkasan
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Total semua meja yang terdaftar ────────────────────────────────────────
const totalTables = computed<number>(() => tables.value.length)

// ─── Jumlah meja dengan status "Tersedia" ───────────────────────────────────
const availableTables = computed<number>(() =>
  tables.value.filter((t) => t.status === 'Tersedia').length,
)

// ─── Jumlah meja dengan status "Digunakan" ──────────────────────────────────
const usedTables = computed<number>(() =>
  tables.value.filter((t) => t.status === 'Digunakan').length,
)

// ─── Jumlah meja dengan status "Reservasi" ──────────────────────────────────
const reservedTables = computed<number>(() =>
  tables.value.filter((t) => t.status === 'Reservasi').length,
)

// ═══════════════════════════════════════════════════════════════════════════════
// COMPUTED — Filter, Pencarian & Pagination
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Daftar meja setelah difilter berdasarkan pencarian dan status ──────────
const filteredTables = computed<TableItem[]>(() => {
  let result = tables.value

  // Filter berdasarkan status jika dipilih
  if (statusFilter.value) {
    result = result.filter((t) => t.status === statusFilter.value)
  }

  // Filter berdasarkan kata kunci pencarian (nama atau area)
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    result = result.filter(
      (t) => t.name.toLowerCase().includes(q) || t.area.toLowerCase().includes(q),
    )
  }

  return result
})

// ─── Jumlah total halaman berdasarkan hasil filter ──────────────────────────
const totalPages = computed<number>(() =>
  Math.max(1, Math.ceil(filteredTables.value.length / itemsPerPage.value)),
)

// ─── Indeks awal baris yang ditampilkan di halaman aktif ────────────────────
const startIndex = computed<number>(() =>
  (currentPage.value - 1) * itemsPerPage.value,
)

// ─── Indeks akhir baris yang ditampilkan di halaman aktif ───────────────────
const endIndex = computed<number>(() =>
  Math.min(startIndex.value + itemsPerPage.value, filteredTables.value.length),
)

// ─── Potongan data meja yang tampil di halaman aktif (paginated) ────────────
const paginatedTables = computed<TableItem[]>(() =>
  filteredTables.value.slice(startIndex.value, endIndex.value),
)

// ─── Daftar nomor halaman yang terlihat (sliding window) ────────────────────
// Menghasilkan array berisi angka halaman dan string '...' (elipsis).
// Aturan sliding window:
//   - Jika currentPage <= 3       → [1, 2, 3, '...', totalPages]
//   - Jika currentPage >= total-2  → [1, '...', total-2, total-1, total]
//   - Di tengah                    → [1, '...', cur-1, cur, cur+1, '...', total]
// Jika totalPages <= 5, tampilkan semua halaman tanpa elipsis.
const visiblePages = computed<(number | string)[]>(() => {
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

// ─── Cek apakah meja terpilih boleh dihapus ────────────────────────────────
// Meja hanya boleh dihapus jika statusnya "Tersedia"
const canDelete = computed<boolean>(() =>
  selectedTable.value !== null && selectedTable.value.status === 'Tersedia',
)

// ─── Label status untuk modal hapus (format panjang) ────────────────────────
function getStatusLabel(status: TableStatus): string {
  switch (status) {
    case 'Tersedia': return 'Tersedia (Siap Dine-In)'
    case 'Digunakan': return 'Sedang Digunakan (Dine-In)'
    case 'Reservasi': return 'Reservasi Terkonfirmasi'
    default: return status
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// WATCHER — Reset halaman saat pencarian atau filter status berubah
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Mengembalikan nomor halaman pagination ke halaman 1 setiap kali query pencarian
// atau opsi status meja diubah, mencegah tabel kosong akibat terjebak di halaman sebelumnya.
watch([searchQuery, statusFilter], () => {
  currentPage.value = 1
})

// ─── Mereset semua filter pencarian dan status meja ke kondisi awal ───────────
function resetFilters(): void {
  searchQuery.value = ''
  statusFilter.value = ''
}

// ─── Berpindah ke nomor halaman tertentu ────────────────────────────────────
function goToPage(page: number | string): void {
  const pageNum = typeof page === 'string' ? parseInt(page, 10) : page
  if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages.value) {
    currentPage.value = pageNum
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI CRUD
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Mengambil daftar meja dari API ─────────────────────────────────────────
// [API-READY] Akan memanggil GET /api/admin/tables dan mengisi
// `tables` dengan response data dari server.
async function fetchTables(): Promise<void> {
  // [API-READY] const { data } = await api.get('/admin/tables')
  // tables.value = data
}

// ─── Membuat kode meja baru secara otomatis ─────────────────────────────────
// Mengambil angka tertinggi dari kode yang ada, lalu menambahkan 1.
function generateCode(): string {
  const maxNum = tables.value.reduce((max, t) => {
    const num = parseInt(t.code.replace('MJ-', ''), 10)
    return isNaN(num) ? max : Math.max(max, num)
  }, 0)
  return `MJ-${String(maxNum + 1).padStart(3, '0')}`
}

// ─── Membuat meja baru dan menambahkannya ke daftar ─────────────────────────
// [API-READY] Akan memanggil POST /api/admin/tables dengan formData,
// lalu menambahkan response ke array tables.
async function createTable(): Promise<void> {
  if (!formData.value.name.trim()) return

  // [API-READY] const { data } = await api.post('/admin/tables', formData.value)
  // tables.value.push(data)

  const newId =
    tables.value.length > 0
      ? Math.max(...tables.value.map((t) => t.id)) + 1
      : 1

  tables.value.push({
    id: newId,
    code: generateCode(),
    name: formData.value.name.trim(),
    capacity: formData.value.capacity,
    area: formData.value.area,
    status: formData.value.status,
    notes: formData.value.notes.trim(),
  })

  closeAllModals()
}

// ─── Memperbarui data meja yang sudah ada ───────────────────────────────────
// [API-READY] Akan memanggil PUT /api/admin/tables/{id} dengan formData,
// lalu memperbarui item di array tables.
async function updateTable(): Promise<void> {
  if (!selectedTable.value || !formData.value.name.trim()) return

  // [API-READY] const { data } = await api.put(`/admin/tables/${selectedTable.value.id}`, formData.value)

  const index = tables.value.findIndex((t) => t.id === selectedTable.value!.id)
  if (index !== -1) {
    tables.value[index] = {
      id: selectedTable.value.id,
      code: selectedTable.value.code,
      name: formData.value.name.trim(),
      capacity: formData.value.capacity,
      area: formData.value.area,
      status: formData.value.status,
      notes: formData.value.notes.trim(),
    }
  }

  closeAllModals()
}

// ─── Menghapus meja dari daftar berdasarkan ID yang dipilih ─────────────────
// [API-READY] Akan memanggil DELETE /api/admin/tables/{id},
// lalu menghapus item dari array tables.
// Hanya bisa dijalankan jika status meja === 'Tersedia'.
async function deleteTable(): Promise<void> {
  if (!selectedTable.value || !canDelete.value) return

  // [API-READY] await api.delete(`/admin/tables/${selectedTable.value.id}`)

  tables.value = tables.value.filter((t) => t.id !== selectedTable.value!.id)

  // Jika halaman aktif menjadi kosong setelah hapus, mundur 1 halaman
  if (currentPage.value > totalPages.value) {
    currentPage.value = Math.max(1, totalPages.value)
  }

  closeAllModals()
}

// ═══════════════════════════════════════════════════════════════════════════════
// MODAL HANDLERS — Fungsi buka/tutup modal
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Membuka modal tambah meja baru dengan form kosong ──────────────────────
function openCreateModal(): void {
  formData.value = { name: '', capacity: 4, area: 'Area Indoor Utama', status: 'Tersedia', notes: '' }
  showCreateModal.value = true
}

// ─── Membuka modal edit dengan data meja yang dipilih ───────────────────────
function openEditModal(table: TableItem): void {
  selectedTable.value = table
  formData.value = {
    name: table.name,
    capacity: table.capacity,
    area: table.area,
    status: table.status,
    notes: table.notes,
  }
  showEditModal.value = true
}

// ─── Navigasi ke halaman detail meja ────────────────────────────────────────
// Mengarahkan ke rute `/admin/tables/:id` alih-alih membuka modal
function goToDetail(table: TableItem): void {
  router.push(`/admin/tables/${table.id}`)
}

// ─── Membuka modal konfirmasi hapus meja ────────────────────────────────────
function openDeleteModal(table: TableItem): void {
  selectedTable.value = table
  showDeleteModal.value = true
}

// ─── Menutup semua modal dan mereset state terpilih ─────────────────────────
function closeAllModals(): void {
  showCreateModal.value = false
  showEditModal.value = false
  showDeleteModal.value = false
  selectedTable.value = null
}

// ═══════════════════════════════════════════════════════════════════════════════
// HELPER — Kelas warna berdasarkan status meja
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Mengembalikan kelas Tailwind CSS badge berdasarkan status meja ──────────
function getStatusClasses(status: TableStatus): string {
  switch (status) {
    case 'Tersedia':
      return 'bg-green-50 text-green-700'
    case 'Digunakan':
      return 'bg-orange-50 text-orange-700'
    case 'Reservasi':
      return 'bg-blue-50 text-blue-700'
    default:
      return 'bg-gray-50 text-gray-700'
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memuat data meja saat komponen pertama kali dimuat ─────────────────────
onMounted(() => {
  fetchTables()
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Page Header ═══════════ -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Manajemen Meja</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola nomor, area, dan status meja dine-in restoran.</p>
      </div>
      <button
        type="button"
        class="flex shrink-0 items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#B45309]"
        @click="openCreateModal"
      >
        <Plus class="h-4 w-4" />
        Tambah Meja
      </button>
    </div>

    <!-- ═══════════ Grid 4 Kartu Ringkasan Status ═══════════ -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      <!-- Kartu Total Meja -->
      <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-white px-5 py-4">
        <div>
          <p class="text-sm font-medium text-gray-500">Total Meja</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ totalTables }}</p>
          <p class="mt-0.5 text-xs text-gray-400">Meja terdaftar</p>
        </div>
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-50">
          <component :is="tableIcon" class="h-6 w-6 text-[#C2410C]" />
        </div>
      </div>

      <!-- Kartu Meja Tersedia -->
      <div class="flex items-center justify-between rounded-xl border border-green-100 bg-white px-5 py-4">
        <div>
          <p class="text-sm font-medium text-gray-500">Meja Tersedia</p>
          <p class="mt-1 text-3xl font-bold text-green-600">{{ availableTables }}</p>
          <p class="mt-0.5 text-xs text-green-500">Siap digunakan dine-in</p>
        </div>
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-green-50">
          <CheckCircle class="h-6 w-6 text-green-500" />
        </div>
      </div>

      <!-- Kartu Meja Digunakan -->
      <div class="flex items-center justify-between rounded-xl border border-orange-100 bg-white px-5 py-4">
        <div>
          <p class="text-sm font-medium text-gray-500">Meja Digunakan</p>
          <p class="mt-1 text-3xl font-bold text-orange-600">{{ usedTables }}</p>
          <p class="mt-0.5 text-xs text-orange-500">Sedang terisi pelanggan</p>
        </div>
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-50">
          <Users class="h-6 w-6 text-orange-500" />
        </div>
      </div>

      <!-- Kartu Meja Reservasi -->
      <div class="flex items-center justify-between rounded-xl border border-blue-100 bg-white px-5 py-4">
        <div>
          <p class="text-sm font-medium text-gray-500">Meja Reservasi</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ reservedTables }}</p>
          <p class="mt-0.5 text-xs text-blue-500">Dipesan untuk shift ini</p>
        </div>
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50">
          <CalendarClock class="h-6 w-6 text-blue-500" />
        </div>
      </div>
    </div>

    <!-- ═══════════ Bar Filter & Pencarian ═══════════ -->
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <!-- Input Pencarian (Sisi Kiri / flex-1) -->
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nomor atau area meja..."
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

        <!-- Dropdown Status (Sisi Kanan) -->
        <select
          v-model="statusFilter"
          class="h-10 w-full sm:w-48 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-700 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30 cursor-pointer"
        >
          <option value="">Semua Status</option>
          <option value="Tersedia">Tersedia</option>
          <option value="Digunakan">Digunakan</option>
          <option value="Reservasi">Reservasi</option>
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

    <!-- ═══════════ Table Card ═══════════ -->
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white">
      <!-- Tabel -->
      <div class="overflow-x-auto">
        <table class="w-full min-w-[650px]">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nomor Meja</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Kapasitas</th>
              <th class="w-32 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Status</th>
              <th class="w-32 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="paginatedTables.length === 0">
              <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-400">
                Tidak ada meja yang ditemukan.
              </td>
            </tr>
            <tr
              v-for="table in paginatedTables"
              :key="table.id"
              class="border-b border-gray-50 transition-colors hover:bg-gray-50/50"
            >
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-50">
                    <component :is="tableIcon" class="h-4 w-4 text-[#C2410C]" />
                  </div>
                  <span class="text-sm font-semibold text-gray-800">{{ table.name }}</span>
                </div>
              </td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <Armchair class="h-4 w-4 text-gray-400" />
                  {{ table.capacity }} Kursi
                </div>
              </td>
              <td class="px-5 py-4 text-center">
                <span
                  :class="[
                    'inline-block rounded-full px-3 py-0.5 text-xs font-medium',
                    getStatusClasses(table.status),
                  ]"
                >{{ table.status }}</span>
              </td>
              <td class="px-5 py-4">
                <div class="flex items-center justify-center gap-1">
                  <button type="button" title="Detail" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600" @click="goToDetail(table)"><Eye class="h-4 w-4" /></button>
                  <button type="button" title="Edit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600" @click="openEditModal(table)"><Pencil class="h-4 w-4" /></button>
                  <button type="button" title="Hapus" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" @click="openDeleteModal(table)"><Trash2 class="h-4 w-4" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row">
        <p class="text-sm text-gray-500">
          Menampilkan {{ filteredTables.length > 0 ? startIndex + 1 : 0 }} -
          {{ endIndex }} dari {{ filteredTables.length }} meja
        </p>
        <div class="flex items-center gap-1">
          <!-- Tombol Sebelumnya -->
          <button
            type="button"
            :disabled="currentPage <= 1"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage - 1)"
          >
            <ChevronLeft class="h-3.5 w-3.5" />
            Sebelumnya
          </button>

          <!-- Nomor Halaman (sliding window) -->
          <template v-for="(page, idx) in visiblePages" :key="'p-' + idx">
            <!-- Elipsis -->
            <span v-if="page === '...'" class="px-1.5 text-xs text-gray-400">…</span>

            <!-- Tombol Nomor Halaman -->
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
            Berikutnya
            <ChevronRight class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ Catatan Kaki ═══════════ -->
    <div class="mt-5 flex items-center gap-2 text-xs text-gray-400">
      <Info class="h-3.5 w-3.5 shrink-0" />
      <span>* Menampilkan ringkasan data meja dine-in dan status sinkronisasi sistem POS restoran.</span>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Tambah Meja Baru (Desain Figma Gambar 2)                       -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeAllModals"></div>
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl" @click.stop>
          <!-- Header dengan ikon brand dan judul -->
          <div class="flex shrink-0 items-start justify-between border-b border-gray-100 p-6">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                <component :is="tableIcon" class="h-5 w-5 text-[#C2410C]" />
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900">Tambah Meja Baru</h3>
                <p class="mt-0.5 text-sm text-gray-500">Tambahkan data nomor meja dan kapasitas dine-in restoran</p>
              </div>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" @click="closeAllModals">
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Form Body (scrollable) -->
          <form id="createTableForm" @submit.prevent="createTable" class="flex-1 space-y-4 overflow-y-auto p-6">
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Nomor Meja <span class="text-red-500">*</span></label>
              <input
                v-model="formData.name"
                type="text"
                required
                placeholder="Contoh: Meja 07"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
              <p class="mt-1 text-xs text-gray-400">Nomor meja harus unik dan belum pernah digunakan di restoran.</p>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Kapasitas Kursi <span class="text-red-500">*</span></label>
              <input
                v-model.number="formData.capacity"
                type="number"
                min="1"
                max="20"
                required
                placeholder="Contoh: 4"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
              <p class="mt-1 text-xs text-gray-400">Menentukan jumlah kapasitas tamu maksimal pada meja ini.</p>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Status Awal</label>
              <select
                v-model="formData.status"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              >
                <option value="Tersedia">Tersedia (Siap Digunakan Dine-In)</option>
                <option value="Digunakan">Digunakan</option>
                <option value="Reservasi">Reservasi</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Keterangan / Catatan Meja (Opsional)</label>
              <textarea
                v-model="formData.notes"
                rows="2"
                placeholder="Tuliskan catatan khusus meja seperti dekat stopkontak, area merokok, dll..."
                class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              ></textarea>
            </div>
          </form>

          <!-- Footer Buttons (fixed) -->
          <div class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button
              type="button"
              class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
              @click="closeAllModals"
            >
              Batal
            </button>
            <button
              type="submit"
              form="createTableForm"
              class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
            >
              <Check class="h-4 w-4" />
              Simpan Meja
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Edit Data Meja (Desain Figma Gambar 3)                         -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showEditModal && selectedTable" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeAllModals"></div>
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl" @click.stop>
          <!-- Header dengan ikon pensil dan judul + badge nomor meja -->
          <div class="flex shrink-0 items-start justify-between border-b border-gray-100 p-6">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                <SquarePen class="h-5 w-5 text-[#C2410C]" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-lg font-bold text-gray-900">Edit Data Meja</h3>
                  <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-[#C2410C]">{{ selectedTable.name }}</span>
                </div>
                <p class="mt-0.5 text-sm text-gray-500">Perbarui informasi nomor meja, kapasitas, dan status meja restoran</p>
              </div>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" @click="closeAllModals">
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Form Body (pre-filled, scrollable) -->
          <form id="editTableForm" @submit.prevent="updateTable" class="flex-1 space-y-4 overflow-y-auto p-6">
            <div>
              <div class="mb-1.5 flex items-center justify-between">
                <label class="text-sm font-semibold text-gray-700">Nomor Meja <span class="text-red-500">*</span></label>
                <span class="text-xs font-medium text-blue-600">Kode / Nomor Meja Aktif</span>
              </div>
              <input
                v-model="formData.name"
                type="text"
                required
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
              <p class="mt-1 text-xs text-gray-400">Nomor meja harus unik dan terdaftar di denah restoran.</p>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Kapasitas Kursi <span class="text-red-500">*</span></label>
              <input
                v-model.number="formData.capacity"
                type="number"
                min="1"
                max="20"
                required
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              />
              <p class="mt-1 text-xs text-gray-400">Menentukan jumlah kapasitas tamu maksimal pada meja ini.</p>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Status Meja</label>
              <select
                v-model="formData.status"
                class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              >
                <option value="Tersedia">Tersedia (Siap Digunakan Dine-In)</option>
                <option value="Digunakan">Digunakan</option>
                <option value="Reservasi">Reservasi</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Keterangan / Catatan Meja (Opsional)</label>
              <textarea
                v-model="formData.notes"
                rows="2"
                placeholder="Tuliskan catatan khusus meja..."
                class="w-full resize-none rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
              ></textarea>
            </div>
          </form>

          <!-- Footer Buttons (fixed) -->
          <div class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button
              type="button"
              class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
              @click="closeAllModals"
            >
              Batal
            </button>
            <button
              type="submit"
              form="editTableForm"
              class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
            >
              <Check class="h-4 w-4" />
              Simpan Perubahan
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Konfirmasi Hapus Meja (Desain Figma Gambar 1)                  -->
    <!-- Validasi cerdas: tombol hapus dinonaktifkan jika status !== Tersedia   -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showDeleteModal && selectedTable" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeAllModals"></div>
        <div class="relative z-10 max-h-[90vh] w-full max-w-md flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl" @click.stop>
          <!-- Tombol tutup pojok kanan atas -->
          <button type="button" class="absolute right-4 top-4 text-gray-400 hover:text-gray-600" @click="closeAllModals">
            <X class="h-5 w-5" />
          </button>

          <div class="px-6 pb-6 pt-6">
            <!-- Ikon tempat sampah merah muda di tengah atas -->
            <div class="mb-4 flex justify-center">
              <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50">
                <Trash2 class="h-7 w-7 text-red-500" />
              </div>
            </div>

            <!-- Judul dan subjudul -->
            <h3 class="text-center text-lg font-bold text-gray-900">Hapus Meja?</h3>
            <p class="mt-1 text-center text-sm text-gray-500">
              Apakah Anda yakin ingin menghapus
              <span class="font-semibold text-gray-800">{{ selectedTable.name }}</span>
              dari daftar tata letak meja restoran?
            </p>

            <!-- Kartu ringkasan detail meja -->
            <div class="mt-5 rounded-lg border border-gray-200 px-4 py-3">
              <div class="flex items-center justify-between border-b border-gray-100 py-2">
                <span class="text-sm text-gray-500">Nomor Meja</span>
                <span class="text-sm font-bold text-gray-800">{{ selectedTable.name }}</span>
              </div>
              <div class="flex items-center justify-between border-b border-gray-100 py-2">
                <span class="text-sm text-gray-500">Kapasitas Tamu</span>
                <span class="text-sm font-bold text-gray-800">{{ selectedTable.capacity }} Tamu (Kursi)</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-500">Status Meja Saat Ini</span>
                <span
                  :class="[
                    'inline-block rounded-full px-2.5 py-0.5 text-xs font-medium',
                    getStatusClasses(selectedTable.status),
                  ]"
                >{{ getStatusLabel(selectedTable.status) }}</span>
              </div>
            </div>

            <!-- Peringatan jika meja sedang digunakan atau direservasi -->
            <div
              v-if="!canDelete"
              class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3"
            >
              <div class="flex items-start gap-2.5">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                <div>
                  <p class="text-sm font-semibold text-amber-800">
                    Proteksi Validasi Meja Aktif:
                  </p>
                  <p class="mt-0.5 text-sm leading-relaxed text-amber-700">
                    Meja yang sedang memiliki pesanan/transaksi aktif (Dine-In) atau memiliki reservasi terkonfirmasi tidak dapat dihapus. Pastikan transaksi pada meja ini telah selesai (Closed Bill) atau dialihkan ke meja lain terlebih dahulu.
                  </p>
                </div>
              </div>
            </div>

            <!-- Tombol aksi -->
            <div class="mt-5 flex items-center justify-center gap-3">
              <button
                type="button"
                class="rounded-lg border border-gray-300 px-8 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                @click="closeAllModals"
              >
                Batal
              </button>
              <button
                type="button"
                :disabled="!canDelete"
                :class="[
                  'flex items-center justify-center gap-2 rounded-lg border px-6 py-2.5 text-sm font-semibold transition-colors',
                  canDelete
                    ? 'border-red-600 bg-red-600 text-white hover:bg-red-700'
                    : 'cursor-not-allowed border-red-200 bg-white text-red-300',
                ]"
                @click="deleteTable"
              >
                <Trash2 class="h-4 w-4" />
                Hapus Meja
              </button>
            </div>

            <!-- Teks bantuan di bawah tombol -->
            <p class="mt-3 text-center text-xs italic text-gray-400">
              (Tindakan ini permanen dan akan menghapus meja dari denah terminal kasir POS.)
            </p>
          </div>
        </div>
      </div>
    </Teleport>
  </DashboardLayout>
</template>
