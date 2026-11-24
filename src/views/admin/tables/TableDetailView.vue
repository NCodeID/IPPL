<script setup lang="ts">
import { ref, computed, onMounted, markRaw } from 'vue'
import type { Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import {
  ArrowLeft,
  UtensilsCrossed,
  Armchair,
  AlignLeft,
  Info,
  Pencil,
  Hash,
  X,
  SquarePen,
  Check,
} from 'lucide-vue-next'

// ═══════════════════════════════════════════════════════════════════════════════
// TIPE DATA (Interfaces)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Tipe status meja yang diizinkan ────────────────────────────────────────
type TableStatus = 'Tersedia' | 'Digunakan' | 'Reservasi'

// ─── Representasi lengkap satu entitas meja untuk halaman detail ────────────
interface TableDetail {
  id: number
  code: string
  name: string
  capacity: number
  area: string
  status: TableStatus
  notes: string
}

// ─── Data form yang digunakan di modal edit ──────────────────────────────────
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

const route = useRoute()
const router = useRouter()

// ─── ID meja dari parameter rute ────────────────────────────────────────────
const tableId = computed<string>(() => {
  const id = route.params['id']
  return Array.isArray(id) ? (id[0] ?? '') : (id ?? '')
})

// ═══════════════════════════════════════════════════════════════════════════════
// IKON HELPER
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Ikon default untuk meja (di-markRaw agar tidak reaktif) ────────────────
const tableIcon: Component = markRaw(UtensilsCrossed)

// ═══════════════════════════════════════════════════════════════════════════════
// STATE (Data Reaktif)
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Data detail meja yang sedang ditampilkan ───────────────────────────────
const table = ref<TableDetail>({
  id: 4,
  code: 'MJ-004',
  name: 'Meja 04',
  capacity: 4,
  area: 'Area VIP / Keluarga',
  status: 'Digunakan',
  notes: 'Dekat stopkontak dan jendela utama',
})

// ─── Nomor meja yang digunakan di judul (tanpa prefix "Meja") ───────────────
const tableNumber = computed<string>(() => {
  const match = table.value.name.match(/\d+/)
  return match ? match[0] : table.value.name
})

// ─── Visibilitas modal edit (true = tampil) ─────────────────────────────────
const showEditModal = ref(false)

// ─── Data form yang digunakan di modal edit ─────────────────────────────────
const formData = ref<TableForm>({
  name: '',
  capacity: 4,
  area: '',
  status: 'Tersedia',
  notes: '',
})

// ═══════════════════════════════════════════════════════════════════════════════
// HELPER — Kelas warna & label berdasarkan status meja
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Mengembalikan kelas Tailwind CSS badge berdasarkan status meja ──────────
function getStatusClasses(status: TableStatus): string {
  switch (status) {
    case 'Tersedia': return 'bg-green-50 text-green-700'
    case 'Digunakan': return 'bg-orange-50 text-orange-700'
    case 'Reservasi': return 'bg-blue-50 text-blue-700'
    default: return 'bg-gray-50 text-gray-700'
  }
}

// ─── Mengembalikan kelas warna titik dot status ─────────────────────────────
function getStatusDotClass(status: TableStatus): string {
  switch (status) {
    case 'Tersedia': return 'bg-green-500'
    case 'Digunakan': return 'bg-orange-500'
    case 'Reservasi': return 'bg-blue-500'
    default: return 'bg-gray-500'
  }
}

// ─── Mengembalikan label teks panjang berdasarkan status ────────────────────
function getStatusLabel(status: TableStatus): string {
  switch (status) {
    case 'Tersedia': return 'Tersedia (Siap Dine-In)'
    case 'Digunakan': return 'Sedang Digunakan'
    case 'Reservasi': return 'Reservasi Terkonfirmasi'
    default: return status
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// FUNGSI NAVIGASI & DATA
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Navigasi kembali ke halaman daftar meja ────────────────────────────────
function goBack(): void {
  router.push('/admin/tables')
}

// ─── Membuka modal edit dengan data meja yang sedang dilihat ────────────────
function openEditModal(): void {
  formData.value = {
    name: table.value.name,
    capacity: table.value.capacity,
    area: table.value.area,
    status: table.value.status,
    notes: table.value.notes,
  }
  showEditModal.value = true
}

// ─── Menutup modal edit ─────────────────────────────────────────────────────
function closeEditModal(): void {
  showEditModal.value = false
}

// ─── Menyimpan perubahan data meja dari modal edit ──────────────────────────
// [API-READY] Akan memanggil PUT /api/admin/tables/{id} dengan formData,
// lalu memperbarui data detail meja di halaman.
async function saveEdit(): Promise<void> {
  if (!formData.value.name.trim()) return

  // [API-READY] const { data } = await api.put(`/admin/tables/${table.value.id}`, formData.value)
  // table.value = data

  table.value = {
    ...table.value,
    name: formData.value.name.trim(),
    capacity: formData.value.capacity,
    area: formData.value.area,
    status: formData.value.status,
    notes: formData.value.notes.trim(),
  }

  closeEditModal()
}

// ─── Mengambil detail meja dari API berdasarkan ID ──────────────────────────
// [API-READY] Akan memanggil GET /api/admin/tables/{id} dan mengisi
// `table` dengan response data dari server.
async function fetchTableDetail(_id: string): Promise<void> {
  // [API-READY] const { data } = await api.get(`/admin/tables/${_id}`)
  // table.value = data
}

// ═══════════════════════════════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════════

// ─── Memuat data detail meja saat komponen pertama kali dimuat ──────────────
onMounted(() => {
  fetchTableDetail(tableId.value)
})
</script>

<template>
  <DashboardLayout>
    <!-- ═══════════ Page Header ═══════════ -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Detail Meja {{ tableNumber }}</h1>
        <p class="mt-1 text-sm text-gray-500">Rincian status dan identitas meja restoran.</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="goBack"
        >
          <ArrowLeft class="h-4 w-4" />
          Kembali
        </button>
        <button
          type="button"
          class="flex items-center gap-2 rounded-lg bg-[#C2410C] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#B45309]"
          @click="openEditModal"
        >
          <Pencil class="h-4 w-4" />
          Edit Meja
        </button>
      </div>
    </div>

    <!-- ═══════════ Kartu Informasi Meja ═══════════ -->
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white">
      <!-- Header kartu -->
      <div class="flex items-start justify-between border-b border-gray-100 px-6 py-5">
        <div>
          <h2 class="text-lg font-bold text-gray-900">Informasi Meja</h2>
          <p class="mt-0.5 text-sm text-gray-500">Rincian status dan identitas meja restoran</p>
        </div>
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
          <component :is="tableIcon" class="h-5 w-5 text-[#C2410C]" />
        </div>
      </div>

      <!-- Grid 2x2 Info -->
      <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
        <!-- Nomor Meja -->
        <div class="rounded-xl bg-slate-50 px-5 py-4">
          <div class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
            <Hash class="h-3.5 w-3.5" />
            Nomor Meja
          </div>
          <p class="text-lg font-bold text-gray-900">{{ table.name }}</p>
        </div>

        <!-- Status Meja -->
        <div class="rounded-xl bg-slate-50 px-5 py-4">
          <div class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
            <Info class="h-3.5 w-3.5" />
            Status Meja
          </div>
          <span
            :class="[
              'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium',
              getStatusClasses(table.status),
            ]"
          >
            <span :class="['h-2 w-2 rounded-full', getStatusDotClass(table.status)]"></span>
            {{ getStatusLabel(table.status) }}
          </span>
        </div>

        <!-- Kapasitas Kursi -->
        <div class="rounded-xl bg-slate-50 px-5 py-4">
          <div class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
            <Armchair class="h-3.5 w-3.5" />
            Kapasitas Kursi
          </div>
          <p class="text-lg font-bold text-gray-900">{{ table.capacity }} Tamu (Kursi)</p>
        </div>

        <!-- Catatan / Keterangan -->
        <div class="rounded-xl bg-slate-50 px-5 py-4">
          <div class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
            <AlignLeft class="h-3.5 w-3.5" />
            Catatan / Keterangan
          </div>
          <p class="text-sm font-medium text-gray-700">{{ table.notes || '-' }}</p>
        </div>
      </div>

      <!-- Catatan Bisnis -->
      <div class="mx-6 mb-6 rounded-xl border border-blue-100 bg-blue-50/50 px-5 py-3.5">
        <div class="flex items-start gap-2.5">
          <Info class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" />
          <p class="text-sm leading-relaxed text-gray-600">
            <span class="font-semibold text-gray-800">Catatan Bisnis:</span>
            Nomor meja bersifat unik. Status meja dan data meja yang sedang aktif dalam pesanan dine-in tidak dapat dihapus.
          </p>
        </div>
      </div>


    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Edit Data Meja (sama seperti di TableListView)                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeEditModal"></div>
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
                  <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-[#C2410C]">{{ table.name }}</span>
                </div>
                <p class="mt-0.5 text-sm text-gray-500">Perbarui informasi nomor meja, kapasitas, dan status meja restoran</p>
              </div>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" @click="closeEditModal">
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Form Body (pre-filled, scrollable) -->
          <form id="editTableDetailForm" @submit.prevent="saveEdit" class="flex-1 space-y-4 overflow-y-auto p-6">
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
              @click="closeEditModal"
            >
              Batal
            </button>
            <button
              type="submit"
              form="editTableDetailForm"
              class="inline-flex items-center gap-2 rounded-lg bg-[#C2410C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#B45309]"
            >
              <Check class="h-4 w-4" />
              Simpan Perubahan
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </DashboardLayout>
</template>
