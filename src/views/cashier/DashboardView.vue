<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Data reaktif untuk ringkasan kasir
const cashierName = ref('Dina')
const totalBill = ref(486000)
const activeBillCount = ref(4)
const occupiedTables = ref(4)
const availableTables = ref(8)
const totalTables = ref(12)
const currentDate = ref('Sabtu, 17 Okt 2026')

// Data daftar pesanan yang "Perlu Perhatian"
const attentionOrders = ref([
    { id: 1, table: 7, customer: 'Sari Dewi', orderId: '#ORD-023', status: 'pending' },
    { id: 2, table: 2, customer: 'Dimas Putra', orderId: '#ORD-022', status: 'cooking' },
    { id: 3, table: 5, customer: 'Bayu Santoso', orderId: '#ORD-021', status: 'ready' },
    { id: 4, table: 9, customer: 'Rizky Aditya', orderId: '#ORD-019', status: 'served' },
    { id: 5, table: 1, customer: 'Ayu Lestari', orderId: '#ORD-020', status: 'served' },
])

// Helper untuk format Rupiah
const formatRupiah = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount).replace('IDR', 'Rp');
}

// Helper untuk format nomor meja (misal: 7 jadi 07)
const formatTableNumber = (num: number) => {
    return num < 10 ? `0${num}` : `${num}`
}

// Konfigurasi style badge berdasarkan status pesanan
const getStatusBadge = (status: string) => {
    switch (status) {
        case 'pending':
        return { text: 'Pending', bg: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' }
        case 'cooking':
        return { text: 'Cooking', bg: 'bg-amber-50 text-amber-700 border border-amber-200/60', dot: 'bg-amber-500' }
        case 'ready':
        return { text: 'Ready', bg: 'bg-blue-50 text-blue-700 border border-blue-200/60', dot: 'bg-blue-500' }
        case 'served':
        return { text: 'Served', bg: 'bg-emerald-50 text-emerald-700 border border-emerald-200/60', dot: 'bg-emerald-500' }
        default:
        return { text: status, bg: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' }
    }
}

// Fungsi interaksi tombol
const handleNewOrder = () => {
    alert('Membuka form Pesanan Baru...')
    // Jika nanti ada halaman form pesanan baru, bisa diarahkan pakai router.push(...)
}
</script>

<template>
    <div class="min-h-screen bg-[#FDFBF7] text-gray-800 flex flex-col justify-between font-sans selection:bg-orange-500 selection:text-white pb-24">
    
    <!-- HEADER -->
    <header class="flex items-center justify-between px-8 py-5 bg-[#FDFBF7] border-b border-orange-100/60">
        <div class="flex items-center space-x-3">
            <!-- Logo -->
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
    <main class="flex-1 px-8 py-6 grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-[1440px] mx-auto w-full">
        
        <!-- LEFT SECTION (Greeting, Summary Card, Stats & Action) -->
        <div class="lg:col-span-7 flex flex-col justify-between space-y-6">
            
            <div>
            <h2 class="text-3xl font-extrabold text-gray-900">Selamat siang, {{ cashierName }}</h2>
            <p class="text-sm text-gray-500 mt-1">Pantau bill aktif, meja, dan aktivitas layanan dari satu layar.</p>
            </div>

            <!-- Total Bill Card (Orange Banner) -->
            <div class="bg-gradient-to-r from-[#F58220] to-[#E06E12] rounded-3xl p-7 text-white shadow-lg shadow-orange-500/20 relative overflow-hidden flex flex-col justify-between">
            <div class="absolute right-4 top-4 text-white/10 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            
            <div class="flex justify-between items-start z-10">
                <span class="text-xs font-semibold uppercase tracking-wider text-orange-100">Total bill belum dibayar</span>
                <div class="bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                </div>
            </div>

            <div class="my-5 z-10">
                <div class="text-4xl lg:text-5xl font-black tracking-tight">{{ formatRupiah(totalBill) }}</div>
            </div>

            <div class="flex justify-between items-center z-10 pt-2 border-t border-white/15">
                <span class="text-xs font-medium text-orange-100">{{ activeBillCount }} bill aktif</span>
                <button class="text-xs font-bold text-white flex items-center hover:underline focus:outline-none cursor-pointer">
                Lihat bill 
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                </button>
            </div>
            </div>

            <!-- 3 Stat Boxes -->
            <div class="grid grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-center">
                <span class="text-2xl lg:text-3xl font-black text-gray-900">{{ occupiedTables }}</span>
                <span class="text-xs font-medium text-gray-400 mt-1">Meja terisi</span>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-center">
                <span class="text-2xl lg:text-3xl font-black text-gray-900">{{ availableTables }}</span>
                <span class="text-xs font-medium text-gray-400 mt-1">Meja tersedia</span>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-center">
                <span class="text-2xl lg:text-3xl font-black text-gray-900">{{ totalTables }}</span>
                <span class="text-xs font-medium text-gray-400 mt-1">Total meja</span>
            </div>
            </div>

            <!-- Action Button: Pesanan baru -->
            <button 
            @click="handleNewOrder"
            class="w-full bg-[#F58220] hover:bg-[#e0751b] active:scale-[0.99] transition-all text-white font-bold py-4 px-6 rounded-2xl shadow-lg shadow-orange-500/20 flex items-center justify-center space-x-2 text-base cursor-pointer"
            >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Pesanan baru</span>
            </button>

            <!-- Kitchen Status Footer -->
            <div class="flex items-center space-x-2 text-xs text-gray-500 pt-2">
            <span class="h-2 w-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
            <span>Kitchen terhubung</span>
            <span class="text-gray-300">•</span>
            <span>{{ currentDate }}</span>
            </div>

        </div>

        <!-- RIGHT SECTION (Perlu Perhatian / Active Orders List) -->
        <div class="lg:col-span-5 bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col">
            
            <div class="flex items-center justify-between pb-5 border-b border-gray-100">
            <h3 class="font-bold text-lg text-gray-900">Perlu perhatian</h3>
            <button class="text-xs font-bold text-[#F58220] hover:underline flex items-center cursor-pointer">
                Pantau 
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            </div>

            <!-- Order Items Loop -->
            <div class="divide-y divide-gray-100 flex-1 overflow-y-auto mt-2">
            <div v-for="order in attentionOrders" :key="order.id" class="py-4 flex items-center justify-between first:pt-2 last:pb-2">
                
                <div class="flex items-center space-x-4">
                <!-- Table Number Badge -->
                <div class="w-11 h-11 rounded-xl bg-gray-50 text-gray-700 font-black text-sm flex items-center justify-center shrink-0 border border-gray-200/60">
                    {{ formatTableNumber(order.table) }}
                </div>
                <!-- Customer Info -->
                <div>
                    <h4 class="font-bold text-gray-900 text-sm">{{ order.customer }}</h4>
                    <span class="text-xs text-gray-400 font-mono">{{ order.orderId }}</span>
                </div>
                </div>

                <!-- Status Badge -->
                <div>
                <span :class="getStatusBadge(order.status).bg" class="text-xs px-3 py-1 rounded-full font-semibold inline-flex items-center">
                    <span :class="getStatusBadge(order.status).dot" class="w-1.5 h-1.5 rounded-full mr-1.5"></span>
                    {{ getStatusBadge(order.status).text }}
                </span>
                </div>

            </div>
            </div>

        </div>

    </main>

    <!-- BOTTOM TAB BAR NAVIGATION -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200/80 px-8 py-3 flex justify-around items-center z-50 shadow-lg">
        
        <!-- Beranda (Active) -->
        <button class="flex flex-col items-center text-[#F58220] focus:outline-none cursor-pointer">
            <div class="p-1.5 bg-orange-50 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            </div>
            <span class="text-[10px] font-bold mt-1">Beranda</span>
        </button>

        <!-- Meja -->
        <button @click="router.push('/cashier/tables')" class="flex flex-col items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
            <div class="p-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            </div>
            <span class="text-[10px] font-medium mt-1">Meja</span>
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