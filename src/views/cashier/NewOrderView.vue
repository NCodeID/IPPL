<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const cashierName = ref('Dina')
const orderType = ref<'dine_in' | 'takeaway'>('dine_in')
const customerName = ref('')
const selectedTableId = ref('')// Default meja 5
const showTableModal = ref(false)
const searchQuery = ref('')
const selectedCategory = ref('Semua')
const errorMessage = ref('')

// Daftar meja restoran
const tables = ref([
    { id: 1, number: 1, status: 'tersedia', seats: 2 },
    { id: 2, number: 2, status: 'tersedia', seats: 4 },
    { id: 3, number: 3, status: 'tersedia', seats: 4 },
    { id: 4, number: 4, status: 'tersedia', seats: 2 },
    { id: 5, number: 5, status: 'terisi', seats: 4 },
    { id: 6, number: 6, status: 'tersedia', seats: 4 },
    { id: 7, number: 7, status: 'tersedia', seats: 2 },
    { id: 8, number: 8, status: 'tersedia', seats: 4 },
])

// Katalog menu makanan/minuman
const menuItems = ref([
    { id: 1, name: 'Nasi Goreng Rasa', price: 45000, category: 'Makanan', image: 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=400&q=80', qty: 0 },
    { id: 2, name: 'Sate Ayam', price: 50000, category: 'Makanan', image: 'https://images.unsplash.com/photo-1555126634-323283e090fa?auto=format&fit=crop&w=400&q=80', qty: 0 },
    { id: 3, name: 'Es Teh Lemon', price: 10000, category: 'Minuman', image: 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=400&q=80', qty: 0 },
    { id: 4, name: 'Mie Goreng Spesial', price: 40000, category: 'Makanan', image: 'https://images.unsplash.com/photo-1585032226651-759b368d7246?auto=format&fit=crop&w=400&q=80', qty: 0 },
    { id: 5, name: 'Es Jeruk Manis', price: 12000, category: 'Minuman', image: 'https://images.unsplash.com/photo-1613478223719-2ab802602423?auto=format&fit=crop&w=400&q=80', qty: 0 },
])

const currentTable = computed(() => {
    return tables.value.find(t => t.id === selectedTableId.value)
})

const filteredMenu = computed(() => {
    return menuItems.value.filter(item => {
        const matchesCategory = selectedCategory.value === 'Semua' || item.category === selectedCategory.value
        const matchesSearch = item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        return matchesCategory && matchesSearch
    })
    })

    const orderSummary = computed(() => {
    return menuItems.value.filter(item => item.qty > 0)
    })

    const totalItemsCount = computed(() => {
    return orderSummary.value.reduce((sum, item) => sum + item.qty, 0)
    })

    const subtotal = computed(() => {
    return orderSummary.value.reduce((sum, item) => sum + (item.price * item.qty), 0)
    })

    const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val).replace('IDR', 'Rp')
    }

    const increment = (item: any) => { item.qty++ }
    const decrement = (item: any) => { if (item.qty > 0) item.qty-- }

    // Logika Validasi Form Pesanan (Sesuai tugas 09 IMPAL)
    const handleSendToKitchen = () => {
    errorMessage.value = ''

    // 1. Validasi nama pelanggan kosong atau hanya spasi
    if (!customerName.value || customerName.value.trim() === '') {
        errorMessage.value = 'The customer name field is required.'
        return
    }
    // 2. Validasi panjang karakter nama
    if (customerName.value.length > 255) {
        errorMessage.value = 'The customer name field must not be greater than 255 characters.'
        return
    }
    // 3. Validasi minimal memilih 1 produk
    if (totalItemsCount.value === 0) {
        errorMessage.value = 'The items field is required.'
        return
    }
    // 4. Validasi Dine-in wajib pilih meja
    if (orderType.value === 'dine_in' && !selectedTableId.value) {
        errorMessage.value = 'The table id field is required when order type is dine_in.'
        return
    }
    // 5. Validasi Takeaway tidak boleh pilih meja
    if (orderType.value === 'takeaway' && selectedTableId.value !== null) {
        errorMessage.value = 'The table id field is prohibited when order type is takeaway.'
        return
    }

    alert('Pesanan valid! Berhasil dikirim ke kitchen.')
    router.push('/cashier/dashboard')
}
</script>

<template>
    <div class="min-h-screen bg-[#FDFBF7] text-gray-800 flex flex-col justify-between font-sans pb-24">
        <!-- HEADER -->
        <header class="flex items-center justify-between px-8 py-5 bg-[#FDFBF7] border-b border-orange-100/60">
        <div class="flex items-center space-x-3">
            <div class="bg-[#F58220] p-2.5 rounded-xl text-white flex items-center justify-center shadow-sm font-black">POS</div>
            <h1 class="text-2xl font-black text-gray-900">rasa<span class="text-[#F58220]">.</span> Menteng</h1>
        </div>
        <div class="bg-amber-100/70 border px-4 py-1.5 rounded-full text-xs font-bold text-amber-900 uppercase">
            KASIR • {{ cashierName }}
        </div>
        </header>

        <!-- MAIN -->
        <main class="flex-1 px-8 py-6 max-w-[1440px] mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- KIRI -->
        <div class="lg:col-span-7 flex flex-col space-y-6">
            <div class="flex items-center space-x-3">
            <button @click="router.back()" class="p-1 rounded-full hover:bg-gray-200 cursor-pointer">←</button>
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900">Pesanan baru</h2>
                <p class="text-xs text-gray-500">Lengkapi data dan kirim ke kitchen</p>
            </div>
            </div>

            <!-- Alert Error Validasi -->
            <div v-if="errorMessage" class="bg-red-50 border border-red-200 p-4 rounded-xl text-red-700 text-sm font-semibold">
            ⚠️ {{ errorMessage }}
            </div>

            <!-- Tipe Pesanan Dine In / Takeaway -->
            <div class="bg-gray-200 p-1 rounded-2xl flex">
            <button 
                @click="orderType = 'dine_in'"
                :class="orderType === 'dine_in' ? 'bg-[#F58220] text-white' : 'text-gray-600'"
                class="flex-1 py-3 rounded-xl font-bold text-sm cursor-pointer transition-all"
            >
                Makan di tempat (Dine In)
            </button>
            <button 
                @click="orderType = 'takeaway'; selectedTableId = null"
                :class="orderType === 'takeaway' ? 'bg-[#F58220] text-white' : 'text-gray-600'"
                class="flex-1 py-3 rounded-xl font-bold text-sm cursor-pointer transition-all"
            >
                Bawa pulang (Takeaway)
            </button>
            </div>

            <!-- Info Meja & Input Nama -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
            <div v-if="orderType === 'dine_in'" class="sm:col-span-4 bg-[#FEF6EC] border border-orange-200 p-4 rounded-2xl flex items-center justify-between">
                <div>
                <h4 class="font-bold text-gray-900 text-sm">Meja {{ currentTable ? currentTable.number : '-' }}</h4>
                <span class="text-xs text-gray-500">{{ currentTable ? currentTable.seats : 0 }} kursi</span>
                </div>
                <button @click="showTableModal = true" class="text-xs font-bold text-[#F58220] hover:underline cursor-pointer">Ganti meja</button>
            </div>

            <div :class="orderType === 'dine_in' ? 'sm:col-span-8' : 'sm:col-span-12'" class="bg-white border px-4 py-3 rounded-2xl shadow-sm">
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama pelanggan *</label>
                <input 
                v-model="customerName"
                type="text" 
                placeholder="Masukkan nama pelanggan"
                class="w-full text-sm font-semibold outline-none bg-transparent"
                />
            </div>
            </div>

            <!-- Katalog Menu -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-h-[420px] overflow-y-auto">
            <div v-for="item in filteredMenu" :key="item.id" class="bg-white rounded-2xl border p-3 flex flex-col justify-between shadow-sm">
                <img :src="item.image" class="w-full h-24 object-cover rounded-xl mb-2" />
                <h4 class="font-bold text-xs truncate">{{ item.name }}</h4>
                <p class="text-xs text-gray-500">{{ formatRupiah(item.price) }}</p>
                <div class="flex items-center justify-end space-x-2 mt-2 pt-2 border-t">
                <button @click="decrement(item)" class="w-6 h-6 rounded bg-gray-100 font-bold">-</button>
                <span class="text-xs font-bold">{{ item.qty }}</span>
                <button @click="increment(item)" class="w-6 h-6 rounded bg-amber-500 text-white font-bold">+</button>
                </div>
            </div>
            </div>
        </div>

        <!-- KANAN: Ringkasan -->
        <div class="lg:col-span-5 bg-white rounded-3xl border shadow-sm p-6 flex flex-col justify-between">
            <div>
            <h3 class="font-bold text-lg text-gray-900 mb-4 border-b pb-2">Ringkasan pesanan</h3>
            <div class="space-y-3 max-h-[250px] overflow-y-auto mb-4">
                <div v-for="order in orderSummary" :key="order.id" class="flex justify-between text-sm">
                <span>{{ order.qty }}× {{ order.name }}</span>
                <span class="font-bold">{{ formatRupiah(order.price * order.qty) }}</span>
                </div>
                <p v-if="orderSummary.length === 0" class="text-xs text-gray-400 italic">Belum ada menu dipilih.</p>
            </div>
            </div>
            <div>
            <div class="flex justify-between items-center mb-4 pt-4 border-t">
                <span class="text-xs text-gray-400">Subtotal</span>
                <span class="text-base font-black">{{ formatRupiah(subtotal) }}</span>
            </div>
            <button 
                @click="handleSendToKitchen"
                class="w-full bg-[#F58220] hover:bg-[#e0751b] text-white font-bold py-4 rounded-2xl shadow-lg cursor-pointer"
            >
                Kirim ke kitchen
            </button>
            </div>
        </div>

        </main>

        <!-- MODAL PILIH / GANTI MEJA -->
        <div v-if="showTableModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl">
            <h3 class="text-lg font-bold mb-4">Pilih Meja Restoran</h3>
            <div class="grid grid-cols-3 gap-3 mb-6">
            <button 
                v-for="t in tables" 
                :key="t.id"
                @click="selectedTableId = t.id; showTableModal = false"
                :class="selectedTableId === t.id ? 'bg-[#F58220] text-white' : 'bg-gray-100 hover:bg-gray-200'"
                class="p-4 rounded-2xl font-black text-center cursor-pointer transition-all"
            >
                Meja 0{{ t.number }}
                <span class="block text-[10px] font-normal opacity-80">{{ t.seats }} kursi</span>
            </button>
            </div>
            <button @click="showTableModal = false" class="w-full bg-gray-200 py-3 rounded-xl font-bold cursor-pointer">Tutup</button>
        </div>
        </div>

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