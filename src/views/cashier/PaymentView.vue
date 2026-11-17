<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const cashierName = ref('Dina')
const orderId = ref('#ORD-021')
const customerName = ref('Budi Santoso')
const tableNumber = ref(5)

const totalTagihan = ref(184800)
const selectedPaymentMethod = ref<string | null>(null)
const uangDiterimaInput = ref<any>(200000)
const printReceipt = ref(true)
const errorMessage = ref('')

const parsedUangDiterima = computed(() => {
    const num = Number(uangDiterimaInput.value)
    return isNaN(num) ? 0 : num
})

const kembalian = computed(() => {
    const diff = parsedUangDiterima.value - totalTagihan.value
    return diff > 0 ? diff : 0
    })

    const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val).replace('IDR', 'Rp')
    }

    // Validasi Pembayaran Sesuai Tugas 09 IMPAL
    const handleConfirmPayment = () => {
    errorMessage.value = ''

    if (!selectedPaymentMethod.value) {
        errorMessage.value = 'Pilih metode pembayaran terlebih dahulu'
        return
    }

    if (selectedPaymentMethod.value === 'tunai') {
        if (uangDiterimaInput.value === '' || uangDiterimaInput.value === null) {
        errorMessage.value = 'Nominal pembayaran wajib diisi'
        return
        }
        if (isNaN(Number(uangDiterimaInput.value)) || parsedUangDiterima.value <= 0) {
        errorMessage.value = 'Nominal pembayaran tidak valid'
        return
        }
        if (parsedUangDiterima.value < totalTagihan.value) {
        errorMessage.value = 'Uang kurang'
        return
        }
    }

    alert('Pembayaran berhasil dikonfirmasi!')
    router.push('/cashier/payment/success')
}
</script>

<template>
    <div class="min-h-screen bg-[#FDFBF7] text-gray-800 flex flex-col justify-between font-sans pb-28">
        
        <!-- HEADER -->
        <header class="flex items-center justify-between px-8 py-5 bg-[#FDFBF7] border-b border-orange-100/60">
        <div class="flex items-center space-x-3">
            <div class="bg-[#F58220] p-2.5 rounded-xl text-white flex items-center justify-center shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            </div>
            <h1 class="text-2xl font-black text-gray-900">rasa<span class="text-[#F58220]">.</span> <span class="text-sm font-normal text-gray-500 ml-2 pl-2 border-l">Menteng</span></h1>
        </div>
        <div class="bg-amber-100/70 border border-amber-200 px-4 py-1.5 rounded-full text-xs font-bold text-amber-900 uppercase">
            KASIR • {{ cashierName }}
        </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 px-8 py-6 max-w-[1440px] mx-auto w-full">
        <div class="mb-6 flex items-center space-x-3">
            <button @click="router.back()" class="p-1 rounded-full hover:bg-gray-200 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div>
            <h2 class="text-3xl font-extrabold text-gray-900">Pembayaran</h2>
            <p class="text-xs text-gray-500">Selesaikan tagihan {{ orderId }}</p>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-xl text-red-700 text-sm font-semibold">
            ⚠️ {{ errorMessage }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- KIRI -->
            <div class="lg:col-span-6 flex flex-col space-y-6">
            <div class="bg-white rounded-3xl border p-6 shadow-sm">
                <div class="flex items-center space-x-3 bg-[#FEF6EC] -mx-6 -mt-6 p-6 rounded-t-3xl border-b border-orange-100 mb-4">
                <div class="bg-[#F58220] text-white font-black text-sm px-3 py-2 rounded-xl">0{{ tableNumber }}</div>
                <h3 class="font-bold text-base text-gray-900">{{ customerName }}</h3>
                </div>
                <div class="flex justify-between items-center py-4 px-5 bg-gray-50 border rounded-2xl">
                <span class="text-sm font-bold text-gray-800">Total tagihan</span>
                <span class="text-2xl font-black text-[#F58220]">{{ formatRupiah(totalTagihan) }}</span>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Metode pembayaran</h3>
                <div class="grid grid-cols-3 gap-4">
                <button @click="selectedPaymentMethod = 'tunai'" :class="selectedPaymentMethod === 'tunai' ? 'border-[#F58220] bg-orange-50 ring-1 ring-[#F58220]' : 'bg-white'" class="rounded-2xl border p-4 flex flex-col items-center justify-center cursor-pointer h-24">
                    <span class="text-xs font-bold">Tunai</span>
                </button>
                <button @click="selectedPaymentMethod = 'qris'" :class="selectedPaymentMethod === 'qris' ? 'border-[#F58220] bg-orange-50 ring-1 ring-[#F58220]' : 'bg-white'" class="rounded-2xl border p-4 flex flex-col items-center justify-center cursor-pointer h-24">
                    <span class="text-xs font-bold">QRIS</span>
                </button>
                <button @click="selectedPaymentMethod = 'kartu'" :class="selectedPaymentMethod === 'kartu' ? 'border-[#F58220] bg-orange-50 ring-1 ring-[#F58220]' : 'bg-white'" class="rounded-2xl border p-4 flex flex-col items-center justify-center cursor-pointer h-24">
                    <span class="text-xs font-bold">Kartu</span>
                </button>
                </div>
            </div>
            </div>

            <!-- KANAN -->
            <div class="lg:col-span-6 flex flex-col justify-between space-y-6">
            <div class="bg-white rounded-3xl border p-6 space-y-6 shadow-sm">
                <div v-if="selectedPaymentMethod === 'tunai'">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Uang diterima</label>
                <input v-model="uangDiterimaInput" type="text" class="w-full text-2xl font-black border-2 border-orange-300 rounded-2xl px-5 py-3.5 outline-none bg-white" />
                </div>
                <div v-else class="bg-blue-50 border border-blue-100 p-4 rounded-2xl text-xs text-blue-700 font-medium">
                Metode {{ selectedPaymentMethod ? selectedPaymentMethod.toUpperCase() : 'belum dipilih' }}. Nominal otomatis sesuai total tagihan.
                </div>

                <div class="flex justify-between items-center pt-4 border-t">
                <span class="text-xs font-semibold text-gray-400 uppercase">Kembalian</span>
                <span class="text-lg font-black text-emerald-600">{{ selectedPaymentMethod === 'tunai' ? formatRupiah(kembalian) : 'Rp0' }}</span>
                </div>

                <div class="flex items-center space-x-3 pt-2">
                <input type="checkbox" id="print" v-model="printReceipt" class="w-4 h-4 rounded text-[#F58220] cursor-pointer" />
                <label for="print" class="text-xs font-semibold text-gray-700 cursor-pointer">Cetak struk setelah pembayaran</label>
                </div>
            </div>

            <button @click="handleConfirmPayment" class="w-full bg-[#F58220] hover:bg-[#e0751b] text-white font-bold py-4 rounded-2xl shadow-lg shadow-orange-500/20 cursor-pointer transition-all">
                Konfirmasi pembayaran
            </button>
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