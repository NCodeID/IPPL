<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const cashierName = ref('Dina')
const activeTab = ref('menunggu') // 'menunggu' | 'selesai'

// Data tagihan checkout
const checkoutList = ref([
    { id: 1, table: 5, customer: 'Budi Santoso', orderId: '#ORD-042', itemsCount: 5, total: 184800, timeAgo: 'Baru saja', status: 'menunggu' },
    { id: 2, table: 8, customer: 'Bima Pratama', orderId: '#ORD-039', itemsCount: 4, total: 138600, timeAgo: '3 menit lalu', status: 'menunggu' },
    { id: 3, table: 11, customer: 'Maya Sari', orderId: '#ORD-037', itemsCount: 7, total: 242550, timeAgo: '5 menit lalu', status: 'menunggu' },
])

// Meja yang sedang dipilih di card
const selectedCheckoutId = ref(1)

const selectedCheckout = computed(() => {
    return checkoutList.value.find(item => item.id === selectedCheckoutId.value) || checkoutList.value[0]
})

const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val).replace('IDR', 'Rp')
}

const formatTableNum = (num: number) => (num < 10 ? `0${num}` : `${num}`)

const handleProceedPayment = () => {
    alert(`Melanjutkan pembayaran untuk Meja ${formatTableNum(selectedCheckout.value.table)} (${formatRupiah(selectedCheckout.value.total)})`)
    // Contoh navigasi ke halaman pembayaran sukses / kasir bayar:
    // router.push('/cashier/payment')
}
</script>

<template>
    <div class="min-h-screen bg-[#FDFBF7] text-gray-800 flex flex-col justify-between font-sans selection:bg-orange-500 selection:text-white pb-28">
        
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
        <main class="flex-1 px-8 py-6 max-w-[1440px] mx-auto w-full flex flex-col justify-between">
        
        <div>
            <!-- Subheader & Breadcrumb -->
            <div class="mb-6">
            <h2 class="text-3xl font-extrabold text-gray-900">Checkout</h2>
            <p class="text-xs text-gray-500 mt-0.5">Tagihan siap dibayar</p>
            </div>

            <!-- Orange Banner Total Tagihan Menunggu -->
            <div class="bg-gradient-to-r from-[#F58220] to-[#E06E12] rounded-3xl p-7 text-white shadow-lg shadow-orange-500/20 relative overflow-hidden flex flex-col justify-between mb-6">
            <div class="absolute right-4 top-4 text-white/10 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            
            <div class="flex justify-between items-start z-10">
                <span class="text-xs font-semibold uppercase tracking-wider text-orange-100">3 tagihan menunggu</span>
                <div class="bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                </div>
            </div>

            <div class="my-4 z-10">
                <div class="text-4xl lg:text-5xl font-black tracking-tight">Rp565.950</div>
            </div>
            </div>

            <!-- Tab Filter (Menunggu vs Selesai) -->
            <div class="bg-gray-200/60 p-1 rounded-2xl flex max-w-xl mb-6">
            <button 
                @click="activeTab = 'menunggu'"
                :class="activeTab === 'menunggu' ? 'bg-[#F58220] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-3 rounded-xl font-bold text-xs transition-all cursor-pointer"
            >
                Menunggu • 3
            </button>
            <button 
                @click="activeTab = 'selesai'"
                :class="activeTab === 'selesai' ? 'bg-[#F58220] text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-3 rounded-xl font-bold text-xs transition-all cursor-pointer"
            >
                Selesai • 18
            </button>
            </div>

            <!-- Grid Kartu Daftar Checkout -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div 
                v-for="item in checkoutList" 
                :key="item.id"
                @click="selectedCheckoutId = item.id"
                :class="selectedCheckoutId === item.id ? 'ring-2 ring-[#F58220] bg-white shadow-md' : 'bg-white border-gray-100 hover:border-orange-200'"
                class="rounded-3xl border p-6 flex flex-col justify-between cursor-pointer transition-all h-44 relative"
            >
                <!-- Atas: Meja, Nama, Radio/Check Icon -->
                <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-2xl bg-orange-100/70 text-amber-900 font-black text-base flex items-center justify-center shrink-0">
                    {{ formatTableNum(item.table) }}
                    </div>
                    <div>
                    <h4 class="font-extrabold text-base text-gray-900">{{ item.customer }}</h4>
                    <p class="text-xs text-gray-400 font-mono mt-0.5">{{ item.orderId }} • {{ item.itemsCount }} item</p>
                    </div>
                </div>
                
                <!-- Ikon Radio Pilih -->
                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" :class="selectedCheckoutId === item.id ? 'border-[#F58220] bg-orange-50' : 'border-gray-300'">
                    <div v-if="selectedCheckoutId === item.id" class="w-2.5 h-2.5 rounded-full bg-[#F58220]"></div>
                </div>
                </div>

                <!-- Bawah: Harga & Waktu -->
                <div class="flex items-end justify-between pt-3 border-t border-gray-100">
                <div>
                    <span class="text-[11px] text-gray-400 block mb-0.5">Total Tagihan</span>
                    <span class="text-xl font-black text-gray-900">{{ formatRupiah(item.total) }}</span>
                </div>
                <span class="text-xs text-gray-400 font-medium">{{ item.timeAgo }}</span>
                </div>
            </div>
            </div>

            <!-- Info Bar Status Terpilih -->
            <div class="bg-[#EAF5EC] border border-emerald-100 p-4 rounded-2xl flex items-center space-x-2 text-xs text-emerald-800 mb-6 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Meja {{ formatTableNum(selectedCheckout.table) }} dipilih • pembayaran diproses oleh kasir.</span>
            </div>

        </div>

        <!-- Tombol Aksi Utama Bawah: Lanjut Pembayaran -->
        <button 
        @click="router.push('/cashier/billsm')"
            class="w-full bg-[#F58220] hover:bg-[#e0751b] active:scale-[0.99] transition-all text-white font-bold py-4 px-6 rounded-2xl shadow-lg shadow-orange-500/20 flex items-center justify-center space-x-2 text-base cursor-pointer"
        >
            <span>Buka Bill </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>

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
        <button @click="router.push('/cashier/orders')" class="flex flex-col items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
            <div class="p-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg></div>
            <span class="text-[10px] font-medium mt-1">Pesanan</span>
        </button>
        <!-- Bill (Active) -->
        <button class="flex flex-col items-center text-[#F58220] focus:outline-none cursor-pointer">
            <div class="p-1.5 bg-orange-50 rounded-xl"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg></div>
            <span class="text-[10px] font-bold mt-1">Bill</span>
        </button>
        </nav>

    </div>
</template>