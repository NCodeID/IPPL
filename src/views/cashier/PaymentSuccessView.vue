<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const cashierName = ref('Dina')

// Data Transaksi Berhasil
const receiptData = ref({
    trxId: '#TRX-024',
    billId: '#BILL-024',
    customerName: 'Budi Santoso',
    tableNumber: 5,
    dateTime: '17 Okt 2026 • 12:55 WIB',
    items: [
        { qty: 2, name: 'Nasi Goreng Rasa', price: 90000 },
        { qty: 1, name: 'Sate Ayam', price: 50000 },
        { qty: 2, name: 'Es Teh Lemon', price: 20000 },
    ],
    subtotal: 160000,
    taxAndService: 24800, // Pajak 10% + layanan 5%
    totalPaid: 184800,
    cashReceived: 200000,
    change: 15200,
    paymentMethod: 'Pembayaran tunai',
    status: 'Lunas'
})

const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val).replace('IDR', 'Rp')
}

const formatTableNum = (num: number) => (num < 10 ? `0${num}` : `${num}`)

const handlePrintReceipt = () => {
    window.print()
}

const handleBackToDashboard = () => {
    router.push('/cashier/dashboard')
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
        <main class="flex-1 px-8 py-6 max-w-[1200px] mx-auto w-full">
        
        <!-- Subheader & Breadcrumb -->
        <div class="mb-6 flex items-center justify-between">
            <div>
            <p class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-1">TRANSAKSI / {{ receiptData.trxId }}</p>
            <h2 class="text-3xl font-extrabold text-gray-900">Pembayaran berhasil</h2>
            </div>
            <button @click="router.back()" class="p-2 rounded-full hover:bg-gray-200/60 cursor-pointer text-gray-700 bg-white border border-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            </button>
        </div>

        <!-- KARTU BUKTI PEMBAYARAN (RECEIPT CARD) -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-8">
            
            <!-- Banner Sukses Lunas -->
            <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-4 mb-8 flex items-center space-x-4">
            <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div>
                <h3 class="font-extrabold text-emerald-900 text-base">Bill sudah lunas.</h3>
                <p class="text-xs text-emerald-700 mt-0.5">Terima kasih, {{ receiptData.customerName }}!</p>
            </div>
            </div>

            <!-- Header Struk (Brand & Status Lunas) -->
            <div class="flex justify-between items-start pb-6 mb-6 border-b border-gray-100">
            <div>
                <h3 class="text-2xl font-black tracking-tight text-gray-900">rasa<span class="text-[#F58220]">.</span></h3>
                <p class="text-xs text-gray-400 mt-1">{{ receiptData.dateTime }}</p>
            </div>
            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-xs px-3.5 py-1.5 rounded-full font-bold uppercase tracking-wide">
                • LUNAS
            </span>
            </div>

            <!-- Info Meja & No Bill -->
            <div class="flex justify-between items-center text-xs text-gray-500 mb-6 font-semibold">
            <span>Meja {{ formatTableNum(receiptData.tableNumber) }}</span>
            <span class="font-mono">{{ receiptData.billId }}</span>
            </div>

            <!-- Daftar Item -->
            <div class="divide-y divide-gray-100 mb-6">
            <div v-for="(item, index) in receiptData.items" :key="index" class="py-3 first:pt-0 last:pb-0 flex justify-between items-center text-sm">
                <div>
                <span class="text-xs font-bold text-gray-800 bg-gray-100 px-2 py-0.5 rounded mr-2">{{ item.qty }}×</span>
                <span class="font-medium text-gray-900">{{ item.name }}</span>
                </div>
                <span class="font-bold text-gray-900">{{ formatRupiah(item.price) }}</span>
            </div>
            </div>

            <!-- Rincian Biaya -->
            <div class="space-y-2.5 pt-4 border-t border-gray-100 text-sm mb-6 text-gray-600">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span class="font-semibold text-gray-900">{{ formatRupiah(receiptData.subtotal) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Pajak 10% + layanan 5%</span>
                <span class="font-semibold text-gray-900">{{ formatRupiah(receiptData.taxAndService) }}</span>
            </div>
            </div>

            <!-- Total Dibayar -->
            <div class="flex justify-between items-center py-4 px-6 bg-gray-50 border border-gray-100 rounded-2xl mb-6">
            <span class="text-base font-bold text-gray-900">Total dibayar</span>
            <span class="text-2xl font-black text-[#F58220]">{{ formatRupiah(receiptData.totalPaid) }}</span>
            </div>

            <!-- Tunai & Kembalian -->
            <div class="space-y-2 text-xs text-gray-500 mb-6">
            <div class="flex justify-between">
                <span>Tunai diterima</span>
                <span class="font-semibold text-gray-800">{{ formatRupiah(receiptData.cashReceived) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Kembalian</span>
                <span class="font-semibold text-gray-800">{{ formatRupiah(receiptData.change) }}</span>
            </div>
            </div>

            <!-- Footer Struk Meta -->
            <div class="pt-4 border-t border-gray-100 text-[11px] text-gray-400 font-mono">
            {{ receiptData.trxId }} • Kasir: {{ cashierName }} • {{ receiptData.paymentMethod }}
            </div>

        </div>

        <!-- Tombol Aksi Bawah: Cetak Struk & Kembali ke Beranda -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button 
            @click="handlePrintReceipt"
            class="bg-[#F58220] hover:bg-[#e0751b] active:scale-[0.99] transition-all text-white font-bold py-4 px-6 rounded-2xl shadow-lg shadow-orange-500/20 flex items-center justify-center space-x-2 text-base cursor-pointer"
            >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Cetak struk</span>
            </button>

            <button 
            @click="handleBackToDashboard"
            class="bg-white hover:bg-gray-50 border border-gray-200 active:scale-[0.99] transition-all text-[#F58220] font-bold py-4 px-6 rounded-2xl shadow-sm flex items-center justify-center space-x-2 text-base cursor-pointer"
            >
            <span>Kembali ke beranda</span>
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