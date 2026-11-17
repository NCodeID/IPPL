<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const cashierName = ref('Dina')
const activeTab = ref('semua') // 'semua' | 'aktif' | 'disajikan'

// Data status pesanan meja
const orderStatuses = ref([
    { id: 1, table: 7, customer: 'Sari Dewi', orderId: '#ORD-023', itemsCount: 4, status: 'pending', progress: 1 },
    { id: 2, table: 2, customer: 'Dimas Putra', orderId: '#ORD-022', itemsCount: 8, status: 'cooking', progress: 2 },
    { id: 3, table: 5, customer: 'Budi Santoso', orderId: '#ORD-021', itemsCount: 6, status: 'ready', progress: 3 },
    { id: 4, table: 1, customer: 'Ayu Lestari', orderId: '#ORD-020', itemsCount: 4, status: 'served', progress: 4 },
    { id: 5, table: 9, customer: 'Rizky Aditya', orderId: '#ORD-019', itemsCount: 6, status: 'served', progress: 4 },
])

// Filter data berdasarkan tab atas
const filteredOrders = computed(() => {
    if (activeTab.value === 'aktif') {
        // Pending, Cooking, Ready
        return orderStatuses.value.filter(o => o.status === 'pending' || o.status === 'cooking' || o.status === 'ready')
    } else if (activeTab.value === 'disajikan') {
        // Served
        return orderStatuses.value.filter(o => o.status === 'served')
    }
    return orderStatuses.value
})

const formatTableNum = (num: number) => (num < 10 ? `0${num}` : `${num}`)

// Konfigurasi badge status
const getStatusBadge = (status: string) => {
    switch (status) {
        case 'pending': return { text: 'Pending', bg: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' }
        case 'cooking': return { text: 'Cooking', bg: 'bg-amber-50 text-amber-700 border border-amber-200/60', dot: 'bg-amber-500' }
        case 'ready': return { text: 'Ready', bg: 'bg-blue-50 text-blue-700 border border-blue-200/60', dot: 'bg-blue-500' }
        case 'served': return { text: 'Served', bg: 'bg-emerald-50 text-emerald-700 border border-emerald-200/60', dot: 'bg-emerald-500' }
        default: return { text: status, bg: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' }
    }
}

const handleDetailOrder = (orderId: string) => {
    alert(`Melihat detail pesanan untuk ${orderId}`)
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

        <!-- User Badge -->
        <div class="bg-amber-100/70 border border-amber-200/60 px-4 py-1.5 rounded-full text-xs font-bold text-amber-900 tracking-wide uppercase">
            KASIR • {{ cashierName }}
        </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 px-8 py-6 max-w-[1440px] mx-auto w-full">
        
        <!-- Subheader & Breadcrumb -->
        <div class="mb-6">
            <p class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-1">Kitchen / Pembaruan langsung</p>
            <h2 class="text-3xl font-extrabold text-gray-900">Pantau pesanan</h2>
        </div>

        <!-- CARD CONTAINER UTAMA -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            
            <!-- Tab Filter Atas -->
            <div class="flex items-center justify-between mb-6 bg-gray-50 p-1.5 rounded-2xl border border-gray-100 max-w-3xl">
            <button 
                @click="activeTab = 'semua'"
                :class="activeTab === 'semua' ? 'bg-[#F58220] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-3 rounded-xl text-xs font-bold transition-all cursor-pointer"
            >
                Semua 5
            </button>
            <button 
                @click="activeTab = 'aktif'"
                :class="activeTab === 'aktif' ? 'bg-[#F58220] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-3 rounded-xl text-xs font-bold transition-all cursor-pointer"
            >
                Aktif 3
            </button>
            <button 
                @click="activeTab = 'disajikan'"
                :class="activeTab === 'disajikan' ? 'bg-[#F58220] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-3 rounded-xl text-xs font-bold transition-all cursor-pointer"
            >
                Disajikan 2
            </button>
            </div>

            <!-- Info Sinkronisasi -->
            <div class="flex items-center space-x-2 text-xs text-gray-400 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Tersinkron 12:55 • 5 meja sedang dilayani</span>
            </div>

            <!-- GRID KARTU PESANAN -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="order in filteredOrders" 
                :key="order.id"
                class="bg-white rounded-2xl border border-gray-200/70 p-5 shadow-sm flex flex-col justify-between hover:border-orange-300 transition-all"
            >
                <div>
                <!-- Header Kartu: Meja & Badge Status -->
                <div class="flex items-start justify-between mb-2">
                    <div>
                    <h3 class="font-extrabold text-lg text-gray-900">Meja {{ formatTableNum(order.table) }} • {{ order.customer }}</h3>
                    <p class="text-xs text-gray-400 font-mono mt-0.5">{{ order.orderId }} • {{ order.itemsCount }} item</p>
                    </div>
                    <span :class="getStatusBadge(order.status).bg" class="text-xs px-3 py-1 rounded-full font-semibold inline-flex items-center shrink-0">
                    <span :class="getStatusBadge(order.status).dot" class="w-1.5 h-1.5 rounded-full mr-1.5"></span>
                    {{ getStatusBadge(order.status).text }}
                    </span>
                </div>

                <!-- Progress Bar Tahapan (Pending -> Cooking -> Ready -> Served) -->
                <div class="mt-5 mb-6">
                    <!-- Baris Garis Progress -->
                    <div class="grid grid-cols-4 gap-1.5 mb-2">
                    <div :class="order.progress >= 1 ? 'bg-orange-600' : 'bg-gray-200'" class="h-1.5 rounded-full transition-all"></div>
                    <div :class="order.progress >= 2 ? 'bg-orange-600' : 'bg-gray-200'" class="h-1.5 rounded-full transition-all"></div>
                    <div :class="order.progress >= 3 ? 'bg-orange-600' : 'bg-gray-200'" class="h-1.5 rounded-full transition-all"></div>
                    <div :class="order.progress >= 4 ? 'bg-emerald-600' : 'bg-gray-200'" class="h-1.5 rounded-full transition-all"></div>
                    </div>
                    <!-- Label Tahapan -->
                    <div class="flex justify-between text-[11px] font-semibold text-gray-400">
                    <span :class="order.progress >= 1 ? 'text-gray-800' : ''">Pending</span>
                    <span :class="order.progress >= 2 ? 'text-gray-800' : ''">Cooking</span>
                    <span :class="order.progress >= 3 ? 'text-gray-800' : ''">Ready</span>
                    <span :class="order.progress >= 4 ? 'text-emerald-700' : ''">Served</span>
                    </div>
                </div>
                </div>

                <!-- Tombol Lihat Detail Pesanan -->
                <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
                <button 
                    @click="handleDetailOrder(order.orderId)" 
                    class="text-xs font-bold text-[#F58220] hover:underline flex items-center cursor-pointer"
                >
                    Lihat detail pesanan 
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                </div>

            </div>
            </div>

        </div>

        </main>

        <!-- BOTTOM TAB BAR NAVIGATION -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200/80 px-8 py-3 flex justify-around items-center z-50 shadow-lg">
        <button @click="router.push('/cashier/dashboard')" class="flex flex-col items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
            <div class="p-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg></div>
            <span class="text-[10px] font-medium mt-1">Beranda</span>
        </button>
        <button @click="router.push('/cashier/tables')" class="flex flex-col items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
            <div class="p-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg></div>
            <span class="text-[10px] font-medium mt-1">Meja</span>
        </button>
        <!-- Pesanan (Active) -->
        <button class="flex flex-col items-center text-[#F58220] focus:outline-none cursor-pointer">
            <div class="p-1.5 bg-orange-50 rounded-xl"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg></div>
            <span class="text-[10px] font-bold mt-1">Pesanan</span>
        </button>
        <button @click="router.push('/cashier/bills')" class="flex flex-col items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
            <div class="p-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg></div>
            <span class="text-[10px] font-medium mt-1">Bill</span>
        </button>
        </nav>

    </div>
</template>