<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const cashierName = ref('Dina')

// Data tagihan pelanggan
const billData = ref({
    tableNumber: 5,
    customerName: 'Budi Santoso',
    orderId: '#ORD-042',
    orderType: 'Dine-in',
    time: '12.32',
    status: 'Siap checkout',
    items: [
        { id: 1, qty: 2, name: 'Nasi Goreng Rasa', price: 90000, note: 'Tidak pedas, tanpa acar' },
        { id: 2, qty: 1, name: 'Sate Ayam', price: 50000, note: 'Saus kacang dipisah' },
        { id: 3, qty: 2, name: 'Es Teh Lemon', price: 20000, note: 'Sedikit es' },
    ],
    subtotal: 160000,
    serviceFee: 8000, // 5%
    tax: 16800, // 10% PB1
    total: 184800,
    runnerInfo: 'Semua item disajikan • Runner Raka • 12.52'
})

const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val).replace('IDR', 'Rp')
}

const handleOpenBill = () => {
    alert('Membuka Buka Bill Pelanggan / Proses Pembayaran...')
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
        <main class="flex-1 px-8 py-6 max-w-[1200px] mx-auto w-full">
        
        <!-- Subheader & Breadcrumb -->
        <div class="mb-6 flex items-center space-x-3">
            <button @click="router.back()" class="p-1 rounded-full hover:bg-gray-200/60 cursor-pointer text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            </button>
            <div>
            <h2 class="text-2xl font-extrabold text-gray-900">Ringkasan bill</h2>
            <p class="text-xs text-gray-500 mt-0.5">Detail tagihan pelanggan</p>
            </div>
        </div>

        <!-- KARTU UTAMA BILL -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
            
            <!-- Info Pelanggan & Meja -->
            <div class="bg-[#FEF6EC] border border-orange-200/60 rounded-2xl p-5 mb-6 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-[#F58220] text-white font-black text-xl px-4 py-3 rounded-xl shadow-sm">
                0{{ billData.tableNumber }}
                </div>
                <div>
                <h3 class="font-extrabold text-lg text-gray-900">{{ billData.customerName }}</h3>
                <p class="text-xs text-gray-500 mt-0.5 font-mono">
                    {{ billData.orderId }} • {{ billData.orderType }} • {{ billData.time }}
                </p>
                </div>
            </div>
            <div class="text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            </div>

            <!-- Header Tabel Item & Status Checkout -->
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
            <span class="text-xs font-bold text-gray-500">3 menu • 5 item</span>
            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-xs px-3 py-1 rounded-full font-semibold">
                Siap checkout
            </span>
            </div>

            <!-- Daftar Item Pesanan -->
            <div class="divide-y divide-gray-100 mb-6">
            <div v-for="item in billData.items" :key="item.id" class="py-4 first:pt-0 last:pb-0 flex justify-between items-start">
                <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-gray-800 bg-gray-100 px-2 py-0.5 rounded">{{ item.qty }}×</span>
                    <h4 class="font-bold text-gray-900 text-sm">{{ item.name }}</h4>
                </div>
                <!-- Catatan kecil item -->
                <div class="flex items-center space-x-1.5 mt-1.5 ml-7 text-xs text-gray-400 italic">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>{{ item.note }}</span>
                </div>
                </div>
                <span class="font-bold text-gray-900 text-sm">{{ formatRupiah(item.price) }}</span>
            </div>
            </div>

            <!-- Rincian Biaya (Subtotal, Layanan, Pajak) -->
            <div class="space-y-2.5 pt-4 border-t border-gray-100 text-sm mb-6 text-gray-600">
            <div class="flex justify-between">
                <span>Subtotal • 5 item</span>
                <span class="font-semibold text-gray-900">{{ formatRupiah(billData.subtotal) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Layanan (5%)</span>
                <span class="font-semibold text-gray-900">{{ formatRupiah(billData.serviceFee) }}</span>
            </div>
            <div class="flex justify-between">
                <span>PB1 (10%)</span>
                <span class="font-semibold text-gray-900">{{ formatRupiah(billData.tax) }}</span>
            </div>
            </div>

            <!-- Total Tagihan -->
            <div class="flex justify-between items-center py-5 px-6 bg-[#FEF6EC] border border-orange-100 rounded-2xl mb-6">
            <span class="text-base font-extrabold text-gray-900">Total tagihan</span>
            <span class="text-2xl font-black text-[#F58220]">{{ formatRupiah(billData.total) }}</span>
            </div>

            <!-- Info Runner / Status Disajikan -->
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-8 bg-gray-50 p-3 rounded-xl border border-gray-100">
            <span class="h-2 w-2 rounded-full bg-emerald-500 inline-block"></span>
            <span>{{ billData.runnerInfo }}</span>
            </div>

            <!-- Tombol Aksi Utama: Buka Bill Pelanggan -->
            <button @click="router.push('/cashier/payment')"
            class="w-full bg-[#F58220] hover:bg-[#e0751b] active:scale-[0.99] transition-all text-white font-bold py-4 px-6 rounded-2xl shadow-lg shadow-orange-500/20 flex items-center justify-center space-x-2 text-base cursor-pointer"
            >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
            </svg>
            <span>Lanjut Pembayaran</span>
            </button>

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