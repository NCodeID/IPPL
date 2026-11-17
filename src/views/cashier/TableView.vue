<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Data reaktif kasir
const cashierName = ref('Dina')
const currentDate = ref('Sabtu, 17 Oktober • Shift siang')

// Filter tab aktif ('semua', 'terisi', 'tersedia')
const activeFilter = ref('semua')

// Data meja (01 - 12)
const tables = ref([
    { id: 1, number: 1, status: 'terisi', customer: 'Ayu Lestari', seats: 2, notes: 'Tidak ada catatan khusus untuk meja ini. Meja 01 sedang terisi dan siap dilayani.' },
    { id: 2, number: 2, status: 'terisi', customer: 'Dimas Putra', seats: 4, notes: '' },
    { id: 3, number: 3, status: 'tersedia', customer: '', seats: 4, notes: '' },
    { id: 4, number: 4, status: 'tersedia', customer: '', seats: 2, notes: '' },
    { id: 5, number: 5, status: 'draft', customer: 'Budi Santoso', seats: 4, notes: 'Draft pesanan baru untuk meja 5.' },
    { id: 6, number: 6, status: 'tersedia', customer: '', seats: 4, notes: '' },
    { id: 7, number: 7, status: 'terisi', customer: 'Sari Dewi', seats: 2, notes: 'Pelanggan meminta makanan disajikan secara bersamaan.' },
    { id: 8, number: 8, status: 'tersedia', customer: '', seats: 4, notes: '' },
    { id: 9, number: 9, status: 'terisi', customer: 'Rizky Aditya', seats: 4, notes: '' },
    { id: 10, number: 10, status: 'tersedia', customer: '', seats: 2, notes: '' },
    { id: 11, number: 11, status: 'tersedia', customer: '', seats: 4, notes: '' },
    { id: 12, number: 12, status: 'tersedia', customer: '', seats: 4, notes: '' },
])

// Meja yang sedang dipilih untuk melihat detail di sebelah kanan (Default Meja 01)
const selectedTableId = ref(1)

const selectedTable = computed(() => {
    return tables.value.find(t => t.id === selectedTableId.value) || tables.value[0]
})

// Filter meja berdasarkan tab atas
const filteredTables = computed(() => {
    if (activeFilter.value === 'terisi') {
        return tables.value.filter(t => t.status === 'terisi' || t.status === 'draft')
    } else if (activeFilter.value === 'tersedia') {
        return tables.value.filter(t => t.status === 'tersedia')
    }
    return tables.value
})

const formatTableNum = (num: number) => (num < 10 ? `0${num}` : `${num}`)

const handleNewOrder = () => {
    alert('Membuka form Pesanan Baru / Pilih Meja...')
}
</script>

<template>
    <div class="min-h-screen bg-[#FDFBF7] text-gray-800 flex flex-col justify-between font-sans selection:bg-orange-500 selection:text-white pb-24">
        
        <!-- HEADER -->
        <header class="flex items-center justify-between px-8 py-5 bg-[#FDFBF7] border-b border-orange-100/60">
        <div class="flex items-center space-x-3">
            <div class="bg-[#F58220] p-2.5 rounded-xl text-white flex items-center justify-center shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            </div>
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 flex items-center">
                rasa<span class="text-[#F58220]">.</span> 
                <span class="text-sm font-normal text-gray-500 ml-2.5 pl-2.5 border-l border-gray-300">Menteng</span>
            </h1>
            </div>
        </div>

        <!-- User Role Badge -->
        <div class="bg-amber-100/70 border border-amber-200/60 px-4 py-1.5 rounded-full text-xs font-bold text-amber-900 tracking-wide uppercase">
            KASIR • {{ cashierName }}
        </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 px-8 py-6 max-w-[1440px] mx-auto w-full">
        
        <!-- Title & Subtitle -->
        <div class="mb-6">
            <h2 class="text-3xl font-extrabold text-gray-900">Meja & pelanggan</h2>
            <p class="text-sm text-gray-500 mt-1">{{ currentDate }}</p>
        </div>

        <!-- Grid Layout Utama (Kiri: Daftar Meja, Kanan: Detail Meja) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- BAGIAN KIRI: List Meja -->
            <div class="lg:col-span-7 bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
            
            <div>
                <!-- Top Filters Tab -->
                <div class="flex items-center justify-between mb-6 bg-gray-50 p-1.5 rounded-2xl border border-gray-100">
                <button 
                    @click="activeFilter = 'semua'"
                    :class="activeFilter === 'semua' ? 'bg-[#F58220] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="flex-1 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                >
                    Semua 12
                </button>
                <button 
                    @click="activeFilter = 'terisi'"
                    :class="activeFilter === 'terisi' ? 'bg-[#F58220] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="flex-1 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                >
                    Terisi 4
                </button>
                <button 
                    @click="activeFilter = 'tersedia'"
                    :class="activeFilter === 'tersedia' ? 'bg-[#F58220] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="flex-1 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                >
                    Tersedia 8
                </button>
                </div>

                <div class="flex items-center justify-between mb-4 px-1">
                <span class="text-xs font-semibold text-gray-400">Area utama • 12 meja</span>
                <span class="text-xs font-semibold text-orange-600">1 draft pesanan</span>
                </div>

                <!-- Grid Kartu Meja -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div 
                    v-for="t in filteredTables" 
                    :key="t.id"
                    @click="selectedTableId = t.id"
                    :class="[
                    selectedTableId === t.id ? 'ring-2 ring-[#F58220] shadow-md' : 'border-gray-100 hover:border-orange-200',
                    t.status === 'terisi' ? 'bg-[#EAF5EC]/60 border-emerald-100' : '',
                    t.status === 'draft' ? 'bg-[#FEF6EC] border-orange-200' : '',
                    t.status === 'tersedia' ? 'bg-white border-gray-100' : ''
                    ]"
                    class="p-4 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between h-28 relative"
                >
                    <!-- Nomor Meja & Ikon Status -->
                    <div class="flex items-center justify-between">
                    <span class="text-xl font-black text-gray-900">{{ formatTableNum(t.number) }}</span>
                    
                    <!-- Ikon Kanan Atas Kartu -->
                    <svg v-if="t.status === 'terisi'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <svg v-else-if="t.status === 'draft'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    </div>

                    <!-- Keterangan Isi Kartu -->
                    <div>
                    <div v-if="t.status === 'terisi' || t.status === 'draft'">
                        <p class="text-xs font-bold text-gray-900 truncate">{{ t.customer }}</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">{{ t.status === 'draft' ? 'Draft • ' : '' }}{{ t.seats }} kursi</p>
                    </div>
                    <div v-else>
                        <p class="text-xs font-semibold text-gray-500">Tersedia</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ t.seats }} kursi</p>
                    </div>
                    </div>

                </div>
                </div>

            </div>

            <!-- Tombol Pesanan Baru di Bawah Kiri -->
            <button @click="router.push('/cashier/orders')" class="w-full bg-[#F58220] hover:bg-[#e0751b] active:scale-[0.99] transition-all text-white font-bold py-4 px-6 rounded-2xl shadow-lg shadow-orange-500/20 flex items-center justify-center space-x-2 text-base cursor-pointer mt-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Pesanan baru</span>
            </button>

            </div>

            <!-- BAGIAN KANAN: Detail Meja yang Dipilih -->
            <div class="lg:col-span-5 bg-white rounded-3xl border border-gray-100 shadow-sm p-8 flex flex-col justify-between">
            
            <div>
                <!-- Header Detail Meja -->
                <div class="border-b border-gray-100 pb-6 mb-6">
                <h3 class="text-2xl font-black text-gray-900">Meja {{ formatTableNum(selectedTable.number) }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">{{ selectedTable.customer || 'Belum ada pelanggan' }}</p>
                </div>

                <!-- Kursi & Status Grid -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-[#FAF8F5] p-5 rounded-2xl border border-gray-100">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Kursi</span>
                    <p class="text-3xl font-black text-gray-900 mt-1">{{ selectedTable.seats }}</p>
                </div>
                <div class="bg-[#FAF8F5] p-5 rounded-2xl border border-gray-100">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</span>
                    <p class="text-2xl font-black text-gray-900 mt-1 capitalize">{{ selectedTable.status }}</p>
                </div>
                </div>

                <!-- Catatan Meja (Jika kosong, dikosongkan/ditampilkan pesan kosong sesuai instruksi) -->
                <div class="bg-[#FAF8F5] p-5 rounded-2xl border border-gray-100">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-2">Catatan</span>
                <p class="text-sm leading-relaxed text-gray-600">
                    {{ selectedTable.notes || 'Tidak ada catatan khusus untuk meja ini.' }}
                </p>
                </div>
            </div>

            <!-- Tombol Aksi Tambahan di Panel Kanan (Opsional) -->
            <div class="pt-6 border-t border-gray-100 mt-6">
                <button 
                @click="handleNewOrder" 
                class="w-full bg-gray-900 hover:bg-gray-800 text-white font-bold py-3.5 px-4 rounded-xl text-sm transition-all cursor-pointer shadow-sm"
                >
                Kelola Pesanan Meja Ini
                </button>
            </div>

            </div>

        </div>

        </main>

        <!-- BOTTOM TAB BAR NAVIGATION -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200/80 px-8 py-3 flex justify-around items-center z-50 shadow-lg">
        
        <!-- Beranda -->
        <button @click="router.push('/cashier/dashboard')" class="flex flex-col items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
            <div class="p-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            </div>
            <span class="text-[10px] font-medium mt-1">Beranda</span>
        </button>

        <!-- Meja (Active) -->
        <button class="flex flex-col items-center text-[#F58220] focus:outline-none cursor-pointer">
            <div class="p-1.5 bg-orange-50 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            </div>
            <span class="text-[10px] font-bold mt-1">Meja</span>
        </button>

        <!-- Pesanan -->
        <button @click="router.push('/cashier/orders')" class="flex flex-col items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
            <div class="p-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            </div>
            <span class="text-[10px] font-medium mt-1">Pesanan</span>
        </button>

        <!-- Bill -->
        <button @click="router.push('/cashier/bills')" class="flex flex-col items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
            <div class="p-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
            </svg>
            </div>
            <span class="text-[10px] font-medium mt-1">Bill</span>
        </button>

        </nav>

    </div>
</template>