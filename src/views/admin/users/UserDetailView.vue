<script setup lang="ts">
// ============================================================
// Halaman Detail Pengguna — Panel Admin POS Restaurant
// Menampilkan informasi lengkap pengguna dan modal edit
// ============================================================

import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  ArrowLeft,
  Pencil,
  Check,
  X,
  Mail,
  Shield,
  Users,
  ChefHat,
  Warehouse,
  Calculator,
  CalendarDays,
  EyeOff,
  Eye,
  Lock,
  Info,
  AlertTriangle,
  Trash2,
  Plus,
  Search,
} from 'lucide-vue-next'

// ------------------------------------------------------------
// Tipe data
// ------------------------------------------------------------

/** Peran pengguna dalam sistem POS */
type UserRole = 'Admin' | 'Kasir' | 'Runner' | 'Dapur' | 'Gudang' | 'Akuntan'

/** Status aktif/nonaktif pengguna */
type UserStatus = 'Aktif' | 'Nonaktif'

/** Detail lengkap pengguna */
interface UserDetail {
  id: number
  code: string
  name: string
  username: string
  email: string
  role: UserRole
  status: UserStatus
  createdAt: string
}

/** Formulir edit pengguna */
interface UserEditForm {
  name: string
  email: string
  role: UserRole | ''
  status: UserStatus
  password: string
}

// ------------------------------------------------------------
// Router
// ------------------------------------------------------------

const route = useRoute()
const router = useRouter()

/** Ambil ID dari parameter rute dengan guard untuk noUncheckedIndexedAccess */
const userId = computed(() => route.params['id'] ?? '')

// ------------------------------------------------------------
// Data pengguna (simulasi)
// ------------------------------------------------------------

/** Data detail pengguna yang sedang ditampilkan */
const user = ref<UserDetail>({
  id: 1,
  code: 'USR-001',
  name: 'Ahmad Fauzi',
  username: '@ahmad.admin',
  email: 'ahmad@restaurant.com',
  role: 'Admin',
  status: 'Aktif',
  createdAt: '12 Januari 2024',
})

// ------------------------------------------------------------
// State modal edit
// ------------------------------------------------------------

/** Visibilitas modal edit pengguna */
const showEditModal = ref(false)

/** Visibilitas kata sandi di input modal edit */
const showEditPassword = ref(false)

/** Formulir edit pengguna */
const editForm = ref<UserEditForm>({
  name: '',
  email: '',
  role: '',
  status: 'Aktif',
  password: '',
})

/** Daftar peran yang tersedia untuk dropdown */
const availableRoles: UserRole[] = ['Admin', 'Kasir', 'Runner', 'Dapur', 'Gudang', 'Akuntan']

// ------------------------------------------------------------
// Fungsi pembantu
// ------------------------------------------------------------

/**
 * Mengambil inisial 2 huruf dari nama pengguna
 * Contoh: "Ahmad Fauzi" → "AF"
 */
function getInitials(name: string): string {
  const parts = name.trim().split(/\s+/)
  const first = parts[0]?.[0] ?? ''
  const second = parts[1]?.[0] ?? ''
  return (first + second).toUpperCase()
}

/**
 * Mengembalikan kelas CSS badge berdasarkan peran pengguna
 * Setiap peran memiliki skema warna yang berbeda
 */
function getRoleBadgeClasses(role: UserRole): string {
  if (role === 'Admin') return 'bg-red-100 text-red-700 border border-red-200'
  if (role === 'Kasir') return 'bg-orange-100 text-orange-700 border border-orange-200'
  if (role === 'Runner') return 'bg-blue-100 text-blue-700 border border-blue-200'
  if (role === 'Dapur') return 'bg-amber-100 text-amber-700 border border-amber-200'
  if (role === 'Gudang') return 'bg-green-100 text-green-700 border border-green-200'
  if (role === 'Akuntan') return 'bg-purple-100 text-purple-700 border border-purple-200'
  return 'bg-gray-100 text-gray-700 border border-gray-200'
}

/**
 * Mengembalikan deskripsi singkat untuk setiap peran
 */
function getRoleDescription(role: UserRole): string {
  if (role === 'Admin') return 'Akses penuh ke seluruh fitur sistem POS'
  if (role === 'Kasir') return 'Mengelola transaksi penjualan dan pembayaran'
  if (role === 'Runner') return 'Mengantarkan pesanan ke meja pelanggan'
  if (role === 'Dapur') return 'Menerima dan memproses pesanan dapur'
  if (role === 'Gudang') return 'Mengelola stok bahan baku dan inventaris'
  if (role === 'Akuntan') return 'Mengelola laporan keuangan dan akuntansi'
  return 'Peran tidak diketahui'
}

/**
 * Mengembalikan komponen ikon yang sesuai untuk setiap peran
 * Menggunakan if/else biasa, BUKAN disimpan di data reaktif
 */
function getRoleIcon(role: UserRole) {
  if (role === 'Admin') return Shield
  if (role === 'Kasir') return Calculator
  if (role === 'Runner') return Users
  if (role === 'Dapur') return ChefHat
  if (role === 'Gudang') return Warehouse
  if (role === 'Akuntan') return Calculator
  return Users
}

// ------------------------------------------------------------
// Aksi modal
// ------------------------------------------------------------

/** Membuka modal edit dan mengisi formulir dari data pengguna saat ini */
function openEditModal(): void {
  editForm.value = {
    name: user.value.name,
    email: user.value.email,
    role: user.value.role,
    status: user.value.status,
    password: '',
  }
  showEditPassword.value = false
  showEditModal.value = true
}

/** Menutup modal edit pengguna */
function closeEditModal(): void {
  showEditModal.value = false
}

// ------------------------------------------------------------
// Fungsi CRUD (siap integrasi API)
// ------------------------------------------------------------

/**
 * Mengambil data detail pengguna dari server
 * [API-READY] GET /api/admin/users/:id
 */
async function fetchUserDetail(): Promise<void> {
  // TODO: Ganti dengan panggilan API sesungguhnya
  // const response = await api.get(`/admin/users/${userId.value}`)
  // user.value = response.data
  console.log(`[API-READY] Mengambil detail pengguna ID: ${userId.value}`)
}

/**
 * Memperbarui data pengguna ke server
 * [API-READY] PUT /api/admin/users/:id
 */
async function updateUser(): Promise<void> {
  // TODO: Ganti dengan panggilan API sesungguhnya
  // const payload = { ...editForm.value }
  // if (!payload.password) delete payload.password
  // await api.put(`/admin/users/${userId.value}`, payload)
  console.log(`[API-READY] Memperbarui pengguna ID: ${userId.value}`, editForm.value)

  // Simulasi pembaruan data lokal
  user.value.name = editForm.value.name
  user.value.email = editForm.value.email
  if (editForm.value.role !== '') {
    user.value.role = editForm.value.role
  }
  user.value.status = editForm.value.status

  closeEditModal()
}

/** Navigasi kembali ke halaman daftar pengguna */
function goBackToList(): void {
  router.push('/admin/users')
}
</script>

<template>
  <DashboardLayout>
    <!-- Kontainer utama halaman detail pengguna -->
    <div class="min-h-screen bg-[#F8FAFC] p-6 lg:p-8">
      <div class="mx-auto max-w-4xl">

        <!-- ======================================================== -->
        <!-- Header Halaman: Judul + Tombol Kembali & Edit            -->
        <!-- ======================================================== -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-gray-900">Detail Pengguna</h1>
              <!-- Badge kode identitas pengguna -->
              <span
                class="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                {{ user.code }}
              </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">Informasi lengkap akun staf dan hak akses sistem POS.</p>
          </div>
          <div class="flex items-center gap-3">
            <!-- Tombol kembali ke daftar pengguna -->
            <button
              type="button"
              class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              @click="goBackToList"
            >
              <ArrowLeft class="h-4 w-4" />
              Kembali
            </button>
            <!-- Tombol edit pengguna -->
            <button
              type="button"
              class="flex items-center gap-2 rounded-lg bg-[#C2410C] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#B45309]"
              @click="openEditModal"
            >
              <Pencil class="h-4 w-4" />
              Edit Pengguna
            </button>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- Kartu profil utama pengguna                              -->
        <!-- ======================================================== -->
        <div class="mb-6 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
          <div class="flex flex-col items-start gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
            <!-- Sisi kiri: Avatar + info dasar -->
            <div class="flex items-center gap-4">
              <!-- Avatar dengan inisial -->
              <div
                class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#C2410C] to-[#92400E] text-xl font-bold text-white shadow-md"
              >
                {{ getInitials(user.name) }}
              </div>

              <div>
                <!-- Nama pengguna dan badge ID -->
                <div class="flex flex-wrap items-center gap-2">
                  <h1 class="text-xl font-bold text-gray-900">{{ user.name }}</h1>
                  <span
                    class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500"
                  >
                    ID: {{ user.code }}
                  </span>
                </div>
                <!-- Email pengguna -->
                <p class="mt-0.5 text-sm text-gray-500">{{ user.email }}</p>
              </div>
            </div>

            <!-- Sisi kanan: Badge peran dan status -->
            <div class="flex flex-wrap items-center gap-2.5">
              <!-- Badge peran dengan ikon -->
              <span
                :class="getRoleBadgeClasses(user.role)"
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
              >
                <component :is="getRoleIcon(user.role)" :size="13" />
                {{ user.role }}
              </span>

              <!-- Badge status akun -->
              <span
                :class="
                  user.status === 'Aktif'
                    ? 'border-green-200 bg-green-50 text-green-700'
                    : 'border-red-200 bg-red-50 text-red-700'
                "
                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold"
              >
                <span
                  :class="user.status === 'Aktif' ? 'bg-green-500' : 'bg-red-500'"
                  class="inline-block h-1.5 w-1.5 rounded-full"
                ></span>
                {{ user.status }}
              </span>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- Bagian "Informasi Akun"                                  -->
        <!-- ======================================================== -->
        <div class="mb-6 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
          <!-- Header bagian informasi akun -->
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-6 pb-4">
            <h2 class="text-lg font-bold text-gray-900">Informasi Akun</h2>
            <span class="text-xs text-gray-400">Sistem POS &amp; Hak Akses Kasir</span>
          </div>

          <div class="p-6 pt-4">
            <!-- Grid 2×2 kotak informasi -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- Nama Pengguna -->
              <div class="rounded-xl bg-slate-50 p-4">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                  Nama Pengguna
                </p>
                <p class="text-sm font-semibold text-gray-800">{{ user.name }}</p>
              </div>

              <!-- Email -->
              <div class="rounded-xl bg-slate-50 p-4">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                  Email
                </p>
                <div class="flex items-center gap-2">
                  <Mail :size="14" class="text-gray-400" />
                  <p class="text-sm font-semibold text-gray-800">{{ user.email }}</p>
                </div>
              </div>

              <!-- Role -->
              <div class="rounded-xl bg-slate-50 p-4">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                  Role
                </p>
                <span
                  :class="getRoleBadgeClasses(user.role)"
                  class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                >
                  <component :is="getRoleIcon(user.role)" :size="13" />
                  {{ user.role }}
                </span>
              </div>

              <!-- Status Akun -->
              <div class="rounded-xl bg-slate-50 p-4">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                  Status Akun
                </p>
                <span
                  :class="
                    user.status === 'Aktif'
                      ? 'border-green-200 bg-green-50 text-green-700'
                      : 'border-red-200 bg-red-50 text-red-700'
                  "
                  class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold"
                >
                  <span
                    :class="user.status === 'Aktif' ? 'bg-green-500' : 'bg-red-500'"
                    class="inline-block h-1.5 w-1.5 rounded-full"
                  ></span>
                  {{ user.status }}
                </span>
              </div>
            </div>

            <!-- Kotak tanggal dibuat (lebar penuh) -->
            <div class="mt-4 rounded-xl bg-slate-50 p-4">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Tanggal Dibuat / Terdaftar
              </p>
              <div class="flex items-center gap-2">
                <CalendarDays :size="14" class="text-gray-400" />
                <p class="text-sm font-semibold text-gray-800">{{ user.createdAt }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- Kotak informasi audit log keamanan                       -->
        <!-- ======================================================== -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm">
          <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
            <!-- Sisi kiri: Ikon dan teks informasi -->
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-slate-200/70">
                <Shield :size="18" class="text-slate-600" />
              </div>
              <div>
                <p class="text-sm font-bold text-gray-800">Audit Log Keamanan SKPL:</p>
                <p class="mt-0.5 text-xs text-gray-500">
                  Setiap perubahan data pengguna dicatat dalam audit log keamanan sesuai persyaratan
                  SKPL untuk menjamin keamanan dan akuntabilitas sistem POS Restaurant.
                </p>
              </div>
            </div>

            <!-- Sisi kanan: Badge sinkronisasi terakhir -->
            <span
              class="inline-flex flex-shrink-0 items-center rounded-full border border-gray-200 bg-white px-3.5 py-1.5 text-xs font-medium text-gray-500 shadow-sm"
            >
              Terakhir diselaraskan: Hari ini, 09:20 WIB
            </span>
          </div>
        </div>

      </div>
    </div>

    <!-- ========================================================== -->
    <!-- Modal Edit Pengguna (Teleport ke body)                     -->
    <!-- ========================================================== -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          @click.self="closeEditModal"
        >
          <!-- Kartu modal -->
          <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">

            <!-- Header modal (tidak ikut scroll) -->
            <div class="flex flex-shrink-0 items-start justify-between border-b border-gray-100 p-6 pb-4">
              <div class="flex items-start gap-3">
                <!-- Ikon header -->
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-orange-100">
                  <Users :size="18" class="text-[#C2410C]" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900">Edit Pengguna</h3>
                  <p class="mt-0.5 text-xs text-gray-500">
                    Perbarui informasi akun staf dan hak akses peran sistem POS Restaurant.
                  </p>
                </div>
              </div>

              <!-- Tombol tutup modal -->
              <button
                type="button"
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                @click="closeEditModal"
              >
                <X :size="18" />
              </button>
            </div>

            <!-- Body modal (bisa di-scroll) -->
            <div class="flex-1 space-y-4 overflow-y-auto p-6">
              <!-- Baris 1: Nama Pengguna & Email -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Input Nama Pengguna -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Nama Pengguna <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="editForm.name"
                    type="text"
                    placeholder="Masukkan nama pengguna"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  />
                </div>

                <!-- Input Email dengan ikon Mail -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Email <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                      <Mail :size="15" class="text-gray-400" />
                    </div>
                    <input
                      v-model="editForm.email"
                      type="email"
                      placeholder="Masukkan email"
                      class="w-full rounded-lg border border-gray-200 bg-slate-50 py-2.5 pl-10 pr-3.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                    />
                  </div>
                </div>
              </div>

              <!-- Baris 2: Role & Status Akun -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Dropdown Role -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Role <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="editForm.role"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  >
                    <option value="" disabled>Pilih role</option>
                    <option v-for="role in availableRoles" :key="role" :value="role">
                      {{ role }}
                    </option>
                  </select>
                </div>

                <!-- Radio button Status Akun -->
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                    Status Akun <span class="text-red-500">*</span>
                  </label>
                  <div class="flex items-center gap-4 pt-1.5">
                    <!-- Radio Aktif -->
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                      <input
                        v-model="editForm.status"
                        type="radio"
                        value="Aktif"
                        class="h-4 w-4 border-gray-300 text-[#C2410C] accent-[#C2410C]"
                      />
                      <span class="flex items-center gap-1.5">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        Aktif
                      </span>
                    </label>

                    <!-- Radio Nonaktif -->
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                      <input
                        v-model="editForm.status"
                        type="radio"
                        value="Nonaktif"
                        class="h-4 w-4 border-gray-300 text-[#C2410C] accent-[#C2410C]"
                      />
                      <span class="flex items-center gap-1.5">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
                        Nonaktif
                      </span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Input Kata Sandi Baru (Opsional) -->
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">
                  Kata Sandi Baru (Opsional)
                </label>
                <div class="relative">
                  <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <Lock :size="15" class="text-gray-400" />
                  </div>
                  <input
                    v-model="editForm.password"
                    :type="showEditPassword ? 'text' : 'password'"
                    placeholder="Masukkan kata sandi baru"
                    class="w-full rounded-lg border border-gray-200 bg-slate-50 py-2.5 pl-10 pr-11 text-sm text-gray-800 outline-none transition focus:border-[#C2410C] focus:bg-white focus:ring-1 focus:ring-[#C2410C]/30"
                  />
                  <!-- Tombol toggle visibilitas kata sandi -->
                  <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition hover:text-gray-600"
                    @click="showEditPassword = !showEditPassword"
                  >
                    <EyeOff v-if="showEditPassword" :size="15" />
                    <Eye v-else :size="15" />
                  </button>
                </div>
                <p class="mt-1.5 text-xs text-gray-400">
                  Kosongkan jika tidak ingin mengubah kata sandi
                </p>
              </div>
            </div>

            <!-- Footer modal (tidak ikut scroll) -->
            <div class="flex flex-shrink-0 items-center justify-end gap-3 border-t border-gray-100 p-6 pt-4">
              <!-- Tombol batal -->
              <button
                type="button"
                class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                @click="closeEditModal"
              >
                Batal
              </button>

              <!-- Tombol simpan perubahan -->
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
                @click="updateUser"
              >
                <Check :size="16" />
                Simpan Perubahan
              </button>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>
  </DashboardLayout>
</template>
