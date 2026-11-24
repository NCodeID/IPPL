<script setup lang="ts">
// ============================================================================
// Halaman Manajemen Pengguna — Daftar, Tambah, Edit, Hapus akun staf
// ============================================================================

import { ref, computed, watch, onMounted } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  Plus,
  Search,
  X,
  RotateCcw,
  Eye,
  Pencil,
  Trash2,
  Check,
  Info,
  AlertTriangle,
  Mail,
  EyeOff,
  Shield,
  UserPlus,
  Users,
  ChefHat,
  Warehouse,
  Calculator,
  CalendarDays,
  ArrowLeft,
  Lock,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import { useRouter } from 'vue-router'

// ============================================================================
// Tipe Data
// ============================================================================

type UserRole = 'Admin' | 'Kasir' | 'Runner' | 'Dapur' | 'Gudang' | 'Akuntan'
type UserStatus = 'Aktif' | 'Nonaktif'

interface UserItem {
  id: number
  code: string
  name: string
  username: string
  email: string
  role: UserRole
  status: UserStatus
  createdAt: string
}

interface UserForm {
  name: string
  email: string
  role: UserRole | ''
  status: UserStatus
  password: string
}

// ============================================================================
// Router
// ============================================================================

const router = useRouter()

// ============================================================================
// Data Dummy Pengguna (12 pengguna)
// ============================================================================

const users = ref<UserItem[]>([
  {
    id: 1,
    code: 'USR-001',
    name: 'Ahmad Fauzi',
    username: '@ahmad.admin',
    email: 'ahmad@restaurant.com',
    role: 'Admin',
    status: 'Aktif',
    createdAt: '12 Januari 2024'
  },
  {
    id: 2,
    code: 'USR-002',
    name: 'Siti Nurhaliza',
    username: '@siti.kasir',
    email: 'siti@restaurant.com',
    role: 'Kasir',
    status: 'Aktif',
    createdAt: '15 Januari 2024'
  },
  {
    id: 3,
    code: 'USR-003',
    name: 'Budi Santoso',
    username: '@budi.runner',
    email: 'budi.runner@restaurant.com',
    role: 'Runner',
    status: 'Aktif',
    createdAt: '20 Januari 2024'
  },
  {
    id: 4,
    code: 'USR-004',
    name: 'Hendra Pratama',
    username: '@chef.hendra',
    email: 'hendra@restaurant.com',
    role: 'Dapur',
    status: 'Aktif',
    createdAt: '25 Januari 2024'
  },
  {
    id: 5,
    code: 'USR-005',
    name: 'Doni Kurniawan',
    username: '@doni.gudang',
    email: 'doni.gudang@restaurant.com',
    role: 'Gudang',
    status: 'Aktif',
    createdAt: '1 Februari 2024'
  },
  {
    id: 6,
    code: 'USR-006',
    name: 'Ratna Sari',
    username: '@ratna.akuntan',
    email: 'ratna.akuntan@restaurant.com',
    role: 'Akuntan',
    status: 'Aktif',
    createdAt: '5 Februari 2024'
  },
  {
    id: 7,
    code: 'USR-007',
    name: 'Dewi Lestari',
    username: '@dewi.kasir2',
    email: 'dewi@restaurant.com',
    role: 'Kasir',
    status: 'Aktif',
    createdAt: '10 Februari 2024'
  },
  {
    id: 8,
    code: 'USR-008',
    name: 'Andi Wijaya',
    username: '@andi.admin2',
    email: 'andi@restaurant.com',
    role: 'Admin',
    status: 'Nonaktif',
    createdAt: '15 Februari 2024'
  },
  {
    id: 9,
    code: 'USR-009',
    name: 'Putri Handayani',
    username: '@putri.kasir3',
    email: 'putri@restaurant.com',
    role: 'Kasir',
    status: 'Aktif',
    createdAt: '20 Februari 2024'
  },
  {
    id: 10,
    code: 'USR-010',
    name: 'Rizky Firmansyah',
    username: '@rizky.runner2',
    email: 'rizky@restaurant.com',
    role: 'Runner',
    status: 'Aktif',
    createdAt: '25 Februari 2024'
  },
  {
    id: 11,
    code: 'USR-011',
    name: 'Mega Puspita',
    username: '@mega.kasir4',
    email: 'mega@restaurant.com',
    role: 'Kasir',
    status: 'Nonaktif',
    createdAt: '1 Maret 2024'
  },
  {
    id: 12,
    code: 'USR-012',
    name: 'Fajar Nugroho',
    username: '@fajar.dapur2',
    email: 'fajar@restaurant.com',
    role: 'Dapur',
    status: 'Aktif',
    createdAt: '5 Maret 2024'
  }
])

// ============================================================================
// State Filter & Pencarian
// ============================================================================

const searchQuery = ref('')
const roleFilter = ref<UserRole | ''>('')
const statusFilter = ref<UserStatus | ''>('')

// ============================================================================
// Pagination — 6 item per halaman
// ============================================================================

const currentPage = ref(1)
const itemsPerPage = 6

// ============================================================================
// State Modal
// ============================================================================

const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

// Referensi pengguna yang sedang di-edit / di-hapus
const selectedUser = ref<UserItem | null>(null)

// Visibilitas kata sandi
const showAddPassword = ref(false)
const showEditPassword = ref(false)

// ============================================================================
// Formulir Tambah & Edit
// ============================================================================

const addForm = ref<UserForm>({
  name: '',
  email: '',
  role: '',
  status: 'Aktif',
  password: ''
})

const editForm = ref<UserForm>({
  name: '',
  email: '',
  role: '',
  status: 'Aktif',
  password: ''
})

// ============================================================================
// Fungsi Helper — Inisial Nama
// ============================================================================

/** Mengambil 2 huruf inisial dari nama depan dan nama belakang */
function getInitials(name: string): string {
  const parts = name.trim().split(/\s+/)
  const first = parts[0]
  const last = parts.length > 1 ? parts[parts.length - 1] : undefined
  const firstInitial = first ? first.charAt(0).toUpperCase() : ''
  const lastInitial = last ? last.charAt(0).toUpperCase() : ''
  return firstInitial + lastInitial
}

/** Mengembalikan kelas warna latar belakang inisial berdasarkan role */
function getInitialsBgColor(role: UserRole): string {
  const colorMap: Record<UserRole, string> = {
    Admin: 'bg-rose-100 text-rose-700',
    Kasir: 'bg-orange-100 text-orange-700',
    Runner: 'bg-blue-100 text-blue-700',
    Dapur: 'bg-amber-100 text-amber-700',
    Gudang: 'bg-green-100 text-green-700',
    Akuntan: 'bg-purple-100 text-purple-700'
  }
  return colorMap[role]
}

// ============================================================================
// Fungsi Helper — Badge Role
// ============================================================================

/** Mengembalikan kelas Tailwind untuk badge role */
function getRoleBadgeClasses(role: UserRole): { bg: string; text: string; iconBg: string } {
  const classMap: Record<UserRole, { bg: string; text: string; iconBg: string }> = {
    Admin: { bg: 'bg-rose-50', text: 'text-rose-700', iconBg: 'bg-rose-100' },
    Kasir: { bg: 'bg-orange-50', text: 'text-orange-700', iconBg: 'bg-orange-100' },
    Runner: { bg: 'bg-blue-50', text: 'text-blue-700', iconBg: 'bg-blue-100' },
    Dapur: { bg: 'bg-amber-50', text: 'text-amber-700', iconBg: 'bg-amber-100' },
    Gudang: { bg: 'bg-green-50', text: 'text-green-700', iconBg: 'bg-green-100' },
    Akuntan: { bg: 'bg-purple-50', text: 'text-purple-700', iconBg: 'bg-purple-100' }
  }
  return classMap[role]
}

// ============================================================================
// Fungsi Helper — Deskripsi Role
// ============================================================================

/** Mengembalikan deskripsi singkat untuk setiap role */
function getRoleDescription(role: UserRole): string {
  const descMap: Record<UserRole, string> = {
    Admin: 'Master data & pengaturan',
    Kasir: 'POS Kasir',
    Runner: 'Pengantaran pesanan',
    Dapur: 'Manajemen dapur',
    Gudang: 'Stok & inventaris',
    Akuntan: 'Laporan keuangan'
  }
  return descMap[role]
}

// ============================================================================
// Fungsi Helper — Ikon Role (dipakai di template via dynamic component)
// ============================================================================

/** Mengembalikan komponen ikon berdasarkan role */
function getRoleIcon(role: UserRole) {
  const iconMap: Record<UserRole, typeof Shield> = {
    Admin: Shield,
    Kasir: Calculator,
    Runner: Users,
    Dapur: ChefHat,
    Gudang: Warehouse,
    Akuntan: Calculator
  }
  return iconMap[role]
}

// ============================================================================
// Computed — Statistik Pengguna
// ============================================================================

/** Total seluruh pengguna */
const totalUsers = computed(() => users.value.length)

/** Jumlah pengguna per role */
const adminCount = computed(() => users.value.filter((u) => u.role === 'Admin').length)
const kasirCount = computed(() => users.value.filter((u) => u.role === 'Kasir').length)
const runnerCount = computed(() => users.value.filter((u) => u.role === 'Runner').length)
const dapurCount = computed(() => users.value.filter((u) => u.role === 'Dapur').length)
const gudangCount = computed(() => users.value.filter((u) => u.role === 'Gudang').length)
const akuntanCount = computed(() => users.value.filter((u) => u.role === 'Akuntan').length)

// ============================================================================
// Computed — Filter & Pencarian
// ============================================================================

/** Daftar pengguna yang sudah difilter berdasarkan pencarian, role, dan status */
const filteredUsers = computed(() => {
  return users.value.filter((user) => {
    // Filter pencarian berdasarkan nama, username, email, atau kode
    const query = searchQuery.value.toLowerCase()
    const matchesSearch =
      !query ||
      user.name.toLowerCase().includes(query) ||
      user.username.toLowerCase().includes(query) ||
      user.email.toLowerCase().includes(query) ||
      user.code.toLowerCase().includes(query)

    // Filter berdasarkan role
    const matchesRole = !roleFilter.value || user.role === roleFilter.value

    // Filter berdasarkan status
    const matchesStatus = !statusFilter.value || user.status === statusFilter.value

    return matchesSearch && matchesRole && matchesStatus
  })
})

// ============================================================================
// Computed — Pagination
// ============================================================================

/** Total halaman pagination */
const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage))

/** Data pengguna pada halaman saat ini */
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredUsers.value.slice(start, end)
})

/** Nomor urut awal pada halaman ini */
const startIndex = computed(() => {
  if (filteredUsers.value.length === 0) return 0
  return (currentPage.value - 1) * itemsPerPage + 1
})

/** Nomor urut akhir pada halaman ini */
const endIndex = computed(() => {
  return Math.min(currentPage.value * itemsPerPage, filteredUsers.value.length)
})

/** Daftar nomor halaman untuk sliding window pagination */
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

// ============================================================================
// Watcher — Reset halaman saat filter berubah
// ============================================================================

// Mengembalikan halaman pagination ke halaman 1 setiap kali filter berubah
watch([searchQuery, roleFilter, statusFilter], () => {
  currentPage.value = 1
})

// ============================================================================
// Fungsi Navigasi & Aksi
// ============================================================================

/** Navigasi ke halaman detail pengguna */
function viewUser(user: UserItem): void {
  router.push(`/admin/users/${user.id}`)
}

/** Menuju halaman tertentu pada pagination */
function goToPage(page: number | string): void {
  const pageNum = typeof page === 'string' ? parseInt(page, 10) : page
  if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages.value) {
    currentPage.value = pageNum
  }
}

/** Mereset semua filter pencarian ke kondisi awal */
function resetFilters(): void {
  searchQuery.value = ''
  roleFilter.value = ''
  statusFilter.value = ''
}

// ============================================================================
// Fungsi Modal Tambah
// ============================================================================

/** Membuka modal tambah pengguna dan mengosongkan formulir */
function openAddModal(): void {
  addForm.value = {
    name: '',
    email: '',
    role: '',
    status: 'Aktif',
    password: ''
  }
  showAddPassword.value = false
  showAddModal.value = true
}

/** Menutup modal tambah pengguna */
function closeAddModal(): void {
  showAddModal.value = false
}

// ============================================================================
// Fungsi Modal Edit
// ============================================================================

/** Membuka modal edit dan mengisi formulir dari data pengguna yang dipilih */
function openEditModal(user: UserItem): void {
  selectedUser.value = user
  editForm.value = {
    name: user.name,
    email: user.email,
    role: user.role,
    status: user.status,
    password: ''
  }
  showEditPassword.value = false
  showEditModal.value = true
}

/** Menutup modal edit pengguna */
function closeEditModal(): void {
  showEditModal.value = false
  selectedUser.value = null
}

// ============================================================================
// Fungsi Modal Hapus
// ============================================================================

/** Membuka modal konfirmasi hapus pengguna */
function openDeleteModal(user: UserItem): void {
  selectedUser.value = user
  showDeleteModal.value = true
}

/** Menutup modal hapus pengguna */
function closeDeleteModal(): void {
  showDeleteModal.value = false
  selectedUser.value = null
}

// ============================================================================
// Fungsi CRUD [API-READY]
// ============================================================================

/** [API-READY] Mengambil daftar pengguna dari server */
function fetchUsers(): void {
  // TODO: Ganti dengan pemanggilan API sesungguhnya
  // Saat ini menggunakan data dummy yang sudah didefinisikan di atas
  console.log('Mengambil data pengguna...')
}

/** [API-READY] Membuat pengguna baru */
function createUser(): void {
  // TODO: Ganti dengan pemanggilan API sesungguhnya
  // Validasi form sebelum mengirim
  if (!addForm.value.name || !addForm.value.email || !addForm.value.role || !addForm.value.password) {
    return
  }

  // Buat ID dan kode baru
  const maxId = users.value.reduce((max, u) => Math.max(max, u.id), 0)
  const newId = maxId + 1
  const newCode = `USR-${String(newId).padStart(3, '0')}`

  const newUser: UserItem = {
    id: newId,
    code: newCode,
    name: addForm.value.name,
    username: `@${addForm.value.name.toLowerCase().replace(/\s+/g, '.')}`,
    email: addForm.value.email,
    role: addForm.value.role as UserRole,
    status: addForm.value.status,
    createdAt: new Date().toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    })
  }

  users.value.push(newUser)
  closeAddModal()
}

/** [API-READY] Memperbarui data pengguna yang sudah ada */
function updateUser(): void {
  // TODO: Ganti dengan pemanggilan API sesungguhnya
  if (!selectedUser.value) return
  if (!editForm.value.name || !editForm.value.email || !editForm.value.role) return

  const index = users.value.findIndex((u) => u.id === selectedUser.value?.id)
  if (index === -1) return

  const existing = users.value[index]
  if (!existing) return

  existing.name = editForm.value.name
  existing.email = editForm.value.email
  existing.role = editForm.value.role as UserRole
  existing.status = editForm.value.status

  closeEditModal()
}

/** [API-READY] Menghapus pengguna dari daftar */
function deleteUser(): void {
  // TODO: Ganti dengan pemanggilan API sesungguhnya
  if (!selectedUser.value) return

  const targetId = selectedUser.value.id
  users.value = users.value.filter((u) => u.id !== targetId)

  // Jika halaman saat ini melebihi total halaman setelah penghapusan, kembali ke halaman terakhir
  if (currentPage.value > totalPages.value && totalPages.value > 0) {
    currentPage.value = totalPages.value
  }

  closeDeleteModal()
}

// ============================================================================
// Lifecycle — Memuat data saat komponen dimount
// ============================================================================

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-[#F8FAFC] px-4 py-6 sm:px-6 lg:px-8">
      <!-- ================================================================ -->
      <!-- Header Halaman                                                    -->
      <!-- ================================================================ -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Manajemen Pengguna</h1>
          <p class="mt-1 text-sm text-gray-500">
            Kelola akun staf dan hak akses peran sistem POS Restaurant sesuai SKPL.
          </p>
        </div>
        <button
          class="inline-flex items-center gap-2 rounded-xl bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309] active:scale-[0.98]"
          @click="openAddModal"
        >
          <Plus class="h-4 w-4" />
          Tambah Pengguna
        </button>
      </div>

      <!-- ================================================================ -->
      <!-- Kartu Statistik — 7 kolom                                         -->
      <!-- ================================================================ -->
      <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
        <!-- Total Pengguna -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100">
            <Users class="h-4.5 w-4.5 text-slate-600" />
          </div>
          <p class="text-xs font-medium text-slate-500">Total Pengguna</p>
          <p class="mt-0.5 text-xl font-bold text-gray-900">{{ totalUsers }}</p>
        </div>

        <!-- Admin -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50">
            <Shield class="h-4.5 w-4.5 text-rose-600" />
          </div>
          <p class="text-xs font-medium text-slate-500">Admin</p>
          <p class="mt-0.5 text-xl font-bold text-gray-900">{{ adminCount }}</p>
        </div>

        <!-- Kasir -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50">
            <Calculator class="h-4.5 w-4.5 text-orange-600" />
          </div>
          <p class="text-xs font-medium text-slate-500">Kasir</p>
          <p class="mt-0.5 text-xl font-bold text-gray-900">{{ kasirCount }}</p>
        </div>

        <!-- Runner -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50">
            <Users class="h-4.5 w-4.5 text-blue-600" />
          </div>
          <p class="text-xs font-medium text-slate-500">Runner</p>
          <p class="mt-0.5 text-xl font-bold text-gray-900">{{ runnerCount }}</p>
        </div>

        <!-- Dapur -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50">
            <ChefHat class="h-4.5 w-4.5 text-amber-600" />
          </div>
          <p class="text-xs font-medium text-slate-500">Dapur</p>
          <p class="mt-0.5 text-xl font-bold text-gray-900">{{ dapurCount }}</p>
        </div>

        <!-- Gudang -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-green-50">
            <Warehouse class="h-4.5 w-4.5 text-green-600" />
          </div>
          <p class="text-xs font-medium text-slate-500">Gudang</p>
          <p class="mt-0.5 text-xl font-bold text-gray-900">{{ gudangCount }}</p>
        </div>

        <!-- Akuntan -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50">
            <Calculator class="h-4.5 w-4.5 text-purple-600" />
          </div>
          <p class="text-xs font-medium text-slate-500">Akuntan</p>
          <p class="mt-0.5 text-xl font-bold text-gray-900">{{ akuntanCount }}</p>
        </div>
      </div>

      <!-- ================================================================ -->
      <!-- Bar Filter & Pencarian                                            -->
      <!-- ================================================================ -->
      <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
          <!-- Input Pencarian -->
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama, username, email, atau kode..."
              class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-9 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
            />
            <button
              v-if="searchQuery"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
              @click="searchQuery = ''"
            >
              <X class="h-4 w-4" />
            </button>
          </div>

          <!-- Dropdown Filter Role -->
          <select
            v-model="roleFilter"
            class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30 sm:w-48"
          >
            <option value="">Semua Role</option>
            <option value="Admin">Admin</option>
            <option value="Kasir">Kasir</option>
            <option value="Runner">Runner</option>
            <option value="Dapur">Dapur</option>
            <option value="Gudang">Gudang</option>
            <option value="Akuntan">Akuntan</option>
          </select>

          <!-- Dropdown Filter Status -->
          <select
            v-model="statusFilter"
            class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30 sm:w-48"
          >
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Nonaktif">Nonaktif</option>
          </select>

          <!-- Tombol Reset Filter -->
          <button
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-800"
            @click="resetFilters"
          >
            <RotateCcw class="h-4 w-4" />
            Reset Filter
          </button>
        </div>
      </div>

      <!-- ================================================================ -->
      <!-- Tabel Daftar Pengguna                                             -->
      <!-- ================================================================ -->
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50">
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  No.
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Nama Pengguna
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Email
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Role
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Status Akun
                </th>
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(user, index) in paginatedUsers"
                :key="user.id"
                class="transition hover:bg-slate-50/50"
              >
                <!-- Nomor Urut -->
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">
                  {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                </td>

                <!-- Nama Pengguna dengan Avatar Inisial -->
                <td class="whitespace-nowrap px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div
                      class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                      :class="getInitialsBgColor(user.role)"
                    >
                      {{ getInitials(user.name) }}
                    </div>
                    <div>
                      <p class="font-semibold text-gray-900">{{ user.name }}</p>
                      <p class="text-xs text-slate-400">{{ user.username }}</p>
                    </div>
                  </div>
                </td>

                <!-- Email -->
                <td class="whitespace-nowrap px-4 py-3">
                  <div class="flex items-center gap-2 text-sm text-slate-600">
                    <Mail class="h-3.5 w-3.5 text-slate-400" />
                    {{ user.email }}
                  </div>
                </td>

                <!-- Role Badge -->
                <td class="whitespace-nowrap px-4 py-3">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold"
                    :class="[getRoleBadgeClasses(user.role).bg, getRoleBadgeClasses(user.role).text]"
                  >
                    <span
                      class="flex h-4.5 w-4.5 items-center justify-center rounded"
                      :class="getRoleBadgeClasses(user.role).iconBg"
                    >
                      <component :is="getRoleIcon(user.role)" class="h-3 w-3" />
                    </span>
                    {{ user.role }}
                  </span>
                </td>

                <!-- Status Akun -->
                <td class="whitespace-nowrap px-4 py-3">
                  <span
                    v-if="user.status === 'Aktif'"
                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                  >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Aktif
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600"
                  >
                    <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                    Nonaktif
                  </span>
                </td>

                <!-- Tombol Aksi -->
                <td class="whitespace-nowrap px-4 py-3">
                  <div class="flex items-center gap-1">
                    <!-- Lihat Detail -->
                    <button
                      class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-blue-600"
                      title="Lihat Detail"
                      @click="viewUser(user)"
                    >
                      <Eye class="h-4 w-4" />
                    </button>

                    <!-- Edit Pengguna -->
                    <button
                      class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-amber-600"
                      title="Edit Pengguna"
                      @click="openEditModal(user)"
                    >
                      <Pencil class="h-4 w-4" />
                    </button>

                    <!-- Hapus Pengguna -->
                    <button
                      class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-red-600"
                      title="Hapus Pengguna"
                      @click="openDeleteModal(user)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Baris kosong jika tidak ada data -->
              <tr v-if="paginatedUsers.length === 0">
                <td colspan="6" class="px-4 py-12 text-center">
                  <div class="flex flex-col items-center gap-2">
                    <Info class="h-8 w-8 text-slate-300" />
                    <p class="font-medium text-slate-500">Tidak ada pengguna ditemukan</p>
                    <p class="text-sm text-slate-400">Coba ubah kata kunci pencarian atau filter Anda.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ================================================================ -->
        <!-- Footer Tabel — Pagination                                        -->
        <!-- ================================================================ -->
        <div
          v-if="filteredUsers.length > 0"
          class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row"
        >
          <!-- Info jumlah data yang ditampilkan -->
          <p class="text-sm text-slate-500">
            Menampilkan <span class="font-semibold text-gray-700">{{ startIndex }}</span> -
            <span class="font-semibold text-gray-700">{{ endIndex }}</span> dari
            <span class="font-semibold text-gray-700">{{ filteredUsers.length }}</span> pengguna
          </p>

          <!-- Navigasi Halaman -->
          <div class="flex items-center gap-1">
            <!-- Tombol Sebelumnya -->
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
              :disabled="currentPage <= 1"
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
              class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
              :disabled="currentPage >= totalPages"
              @click="goToPage(currentPage + 1)"
            >
              Berikutnya
              <ChevronRight class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>

        <!-- Catatan Kaki -->
        <div class="border-t border-slate-100 px-4 py-2">
          <p class="text-xs text-slate-400">
            <Info class="mr-1 inline h-3 w-3" />
            Data pengguna dikelola oleh sistem. Hubungi administrator untuk perubahan hak akses.
          </p>
        </div>
      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- Modal Tambah Pengguna Baru                                            -->
    <!-- ==================================================================== -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showAddModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          @click.self="closeAddModal"
        >
          <div
            class="max-h-[90vh] w-full max-w-lg flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
          >
            <!-- Header Modal -->
            <div class="flex shrink-0 items-start justify-between border-b border-slate-100 p-6 pb-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50">
                  <UserPlus class="h-5 w-5 text-[#C2410C]" />
                </div>
                <div>
                  <h2 class="text-lg font-bold text-gray-900">Tambah Pengguna Baru</h2>
                  <p class="text-sm text-slate-500">Isi data untuk membuat akun pengguna baru</p>
                </div>
              </div>
              <button
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                @click="closeAddModal"
              >
                <X class="h-5 w-5" />
              </button>
            </div>

            <!-- Body Modal (scrollable) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
              <!-- Nama Pengguna -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Nama Pengguna <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="addForm.name"
                  type="text"
                  placeholder="Masukkan nama lengkap"
                  class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                />
              </div>

              <!-- Email -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Email <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    v-model="addForm.email"
                    type="email"
                    placeholder="contoh@restaurant.com"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 pl-9 pr-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  />
                </div>
              </div>

              <!-- Grid 2 kolom: Role & Status -->
              <div class="grid grid-cols-2 gap-4">
                <!-- Role -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Role <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="addForm.role"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  >
                    <option value="" disabled>Pilih role</option>
                    <option value="Admin">Admin</option>
                    <option value="Kasir">Kasir</option>
                    <option value="Runner">Runner</option>
                    <option value="Dapur">Dapur</option>
                    <option value="Gudang">Gudang</option>
                    <option value="Akuntan">Akuntan</option>
                  </select>
                </div>

                <!-- Status Akun -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Status Akun <span class="text-red-500">*</span>
                  </label>
                  <div class="flex items-center gap-4 pt-2">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                      <input
                        v-model="addForm.status"
                        type="radio"
                        value="Aktif"
                        class="h-4 w-4 border-gray-300 text-[#C2410C] accent-[#C2410C]"
                      />
                      <span class="text-gray-700">Aktif</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                      <input
                        v-model="addForm.status"
                        type="radio"
                        value="Nonaktif"
                        class="h-4 w-4 border-gray-300 text-[#C2410C] accent-[#C2410C]"
                      />
                      <span class="text-gray-700">Nonaktif</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Kata Sandi -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Kata Sandi <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="addForm.password"
                    :type="showAddPassword ? 'text' : 'password'"
                    placeholder="Masukkan kata sandi"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 pr-10 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  />
                  <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                    @click="showAddPassword = !showAddPassword"
                  >
                    <EyeOff v-if="showAddPassword" class="h-4 w-4" />
                    <Eye v-else class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Footer Modal -->
            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 p-6 pt-4">
              <button
                class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                @click="closeAddModal"
              >
                Batal
              </button>
              <button
                class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309] active:scale-[0.98]"
                @click="createUser"
              >
                <Check class="h-4 w-4" />
                Simpan Pengguna
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ==================================================================== -->
    <!-- Modal Edit Pengguna                                                   -->
    <!-- ==================================================================== -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showEditModal && selectedUser"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          @click.self="closeEditModal"
        >
          <div
            class="max-h-[90vh] w-full max-w-lg flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
          >
            <!-- Header Modal -->
            <div class="flex shrink-0 items-start justify-between border-b border-slate-100 p-6 pb-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                  <Users class="h-5 w-5 text-blue-600" />
                </div>
                <div>
                  <h2 class="text-lg font-bold text-gray-900">Edit Pengguna</h2>
                  <p class="text-sm text-slate-500">Perbarui data pengguna {{ selectedUser.code }}</p>
                </div>
              </div>
              <button
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                @click="closeEditModal"
              >
                <X class="h-5 w-5" />
              </button>
            </div>

            <!-- Body Modal (scrollable) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
              <!-- Grid 2 kolom: Nama & Email -->
              <div class="grid grid-cols-2 gap-4">
                <!-- Nama Pengguna -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Nama Pengguna <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="editForm.name"
                    type="text"
                    placeholder="Masukkan nama lengkap"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  />
                </div>

                <!-- Email -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Email <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                      v-model="editForm.email"
                      type="email"
                      placeholder="contoh@restaurant.com"
                      class="w-full rounded-lg border border-gray-200 bg-slate-50 pl-9 pr-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                    />
                  </div>
                </div>
              </div>

              <!-- Grid 2 kolom: Role & Status -->
              <div class="grid grid-cols-2 gap-4">
                <!-- Role -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Role <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="editForm.role"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  >
                    <option value="" disabled>Pilih role</option>
                    <option value="Admin">Admin</option>
                    <option value="Kasir">Kasir</option>
                    <option value="Runner">Runner</option>
                    <option value="Dapur">Dapur</option>
                    <option value="Gudang">Gudang</option>
                    <option value="Akuntan">Akuntan</option>
                  </select>
                </div>

                <!-- Status Akun -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Status Akun <span class="text-red-500">*</span>
                  </label>
                  <div class="flex items-center gap-4 pt-2">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                      <input
                        v-model="editForm.status"
                        type="radio"
                        value="Aktif"
                        class="h-4 w-4 border-gray-300 text-[#C2410C] accent-[#C2410C]"
                      />
                      <span class="text-gray-700">Aktif</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                      <input
                        v-model="editForm.status"
                        type="radio"
                        value="Nonaktif"
                        class="h-4 w-4 border-gray-300 text-[#C2410C] accent-[#C2410C]"
                      />
                      <span class="text-gray-700">Nonaktif</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Kata Sandi Baru (Opsional) -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Kata Sandi Baru (Opsional)
                </label>
                <div class="relative">
                  <input
                    v-model="editForm.password"
                    :type="showEditPassword ? 'text' : 'password'"
                    placeholder="Masukkan kata sandi baru"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 pr-10 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  />
                  <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                    @click="showEditPassword = !showEditPassword"
                  >
                    <EyeOff v-if="showEditPassword" class="h-4 w-4" />
                    <Eye v-else class="h-4 w-4" />
                  </button>
                </div>
                <p class="mt-1.5 text-xs text-gray-400">
                  Kosongkan jika tidak ingin mengubah kata sandi
                </p>
              </div>
            </div>

            <!-- Footer Modal -->
            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 p-6 pt-4">
              <button
                class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                @click="closeEditModal"
              >
                Batal
              </button>
              <button
                class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309] active:scale-[0.98]"
                @click="updateUser"
              >
                <Check class="h-4 w-4" />
                Simpan Perubahan
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ==================================================================== -->
    <!-- Modal Hapus Akun Pengguna                                             -->
    <!-- ==================================================================== -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDeleteModal && selectedUser"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          @click.self="closeDeleteModal"
        >
          <div
            class="max-h-[90vh] w-full max-w-md flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
          >
            <!-- Header Modal -->
            <div class="flex shrink-0 items-start justify-between border-b border-slate-100 p-6 pb-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-50">
                  <Trash2 class="h-5 w-5 text-red-500" />
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-900">Hapus Akun Pengguna</h2>
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono font-semibold text-slate-600">
                      {{ selectedUser.code }}
                    </span>
                  </div>
                  <p class="text-sm text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
                </div>
              </div>
              <button
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                @click="closeDeleteModal"
              >
                <X class="h-5 w-5" />
              </button>
            </div>

            <!-- Body Modal -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
              <!-- Deskripsi -->
              <p class="text-sm text-slate-600">
                Apakah Anda yakin ingin menghapus akun pengguna berikut? Semua data terkait pengguna ini
                akan dihapus secara permanen dari sistem.
              </p>

              <!-- Kartu Ringkasan Pengguna -->
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold"
                    :class="getInitialsBgColor(selectedUser.role)"
                  >
                    {{ getInitials(selectedUser.name) }}
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <p class="font-semibold text-gray-900">{{ selectedUser.name }}</p>
                      <span
                        v-if="selectedUser.status === 'Aktif'"
                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700"
                      >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Aktif
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600"
                      >
                        <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                        Nonaktif
                      </span>
                    </div>
                    <p class="text-xs text-slate-400">{{ selectedUser.username }}</p>
                  </div>
                </div>

                <div class="mt-3 space-y-1.5 border-t border-slate-200 pt-3">
                  <!-- Email -->
                  <div class="flex items-center gap-2 text-sm text-slate-600">
                    <Mail class="h-3.5 w-3.5 text-slate-400" />
                    {{ selectedUser.email }}
                  </div>

                  <!-- Role Badge -->
                  <div class="flex items-center gap-2 text-sm">
                    <span
                      class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold"
                      :class="[
                        getRoleBadgeClasses(selectedUser.role).bg,
                        getRoleBadgeClasses(selectedUser.role).text
                      ]"
                    >
                      <component :is="getRoleIcon(selectedUser.role)" class="h-3 w-3" />
                      {{ selectedUser.role }}
                    </span>
                    <span class="text-xs text-slate-400">— {{ getRoleDescription(selectedUser.role) }}</span>
                  </div>
                </div>
              </div>

              <!-- Peringatan Keamanan -->
              <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                  <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100">
                    <Lock class="h-4 w-4 text-amber-700" />
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-amber-800">Proteksi Keamanan Akun</p>
                    <p class="mt-1 text-xs text-amber-700">
                      Menghapus akun ini akan mencabut semua hak akses dan sesi aktif pengguna ke sistem
                      POS Restaurant secara permanen.
                    </p>
                    <p class="mt-1.5 text-xs text-amber-600">
                      Disarankan untuk menonaktifkan akun terlebih dahulu jika pengguna hanya
                      bersifat sementara tidak aktif.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Modal -->
            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 p-6 pt-4">
              <button
                class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                @click="closeDeleteModal"
              >
                Batal
              </button>
              <button
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 active:scale-[0.98]"
                @click="deleteUser"
              >
                <Trash2 class="h-4 w-4" />
                Ya, Hapus Pengguna
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </DashboardLayout>
</template>

<style scoped>
/* Animasi transisi untuk modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
