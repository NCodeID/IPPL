<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import {
  ShoppingBag,
  Mail,
  LockKeyhole,
  Eye,
  EyeOff,
  AlertCircle,
  ShieldCheck,
  LogIn,
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)
const emailError = ref('')
const passwordError = ref('')

function validateFields(): boolean {
  let valid = true
  emailError.value = ''
  passwordError.value = ''

  if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    emailError.value = 'Format email tidak valid atau belum diisi'
    valid = false
  }

  if (!password.value) {
    passwordError.value = 'Password wajib diisi'
    valid = false
  }

  return valid
}

async function handleLogin(): Promise<void> {
  errorMessage.value = ''
  emailError.value = ''
  passwordError.value = ''

  if (!validateFields()) return

  isLoading.value = true

  try {
    await authStore.login({ email: email.value, password: password.value })

    if (authStore.userRole === 'admin') {
      await router.push('/admin/dashboard')
    } else if (authStore.userRole === 'accountant') {
      await router.push('/accountant/dashboard')
    } else {
      errorMessage.value = 'Role tidak dikenali.'
    }
  } catch (error: unknown) {
    if (error instanceof Error) {
      errorMessage.value = error.message
    } else {
      errorMessage.value =
        'Email atau password yang Anda masukkan salah. Silakan periksa kembali email dan password akun Anda.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-[#F8FAFC] px-4">
    <!-- ===== Card ===== -->
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_4px_24px_rgba(0,0,0,0.08)]">
      <!-- Top accent bar -->
      <div class="h-1.5 bg-[#C2410C]"></div>

      <!-- Card body -->
      <div class="px-8 pb-8 pt-8">
        <!-- Brand icon -->
        <div class="mb-4 flex justify-center">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#C2410C] shadow-md">
            <ShoppingBag class="h-6 w-6 text-white" />
          </div>
        </div>

        <!-- Header text -->
        <div class="mb-6 text-center">
          <!-- Title + badge row -->
          <div class="mb-1 flex items-center justify-center gap-2">
            <h1 class="text-xl font-bold text-gray-800">POS Restaurant</h1>
            <span
              class="rounded-full border border-[#C2410C] px-2.5 py-0.5 text-[10px] font-semibold text-[#C2410C]"
            >
              Sistem POS Restaurant
            </span>
          </div>

          <p class="text-xs text-gray-500">Sistem Point of Sale Restaurant</p>

          <!-- Main heading -->
          <h2 class="mt-5 text-xl font-bold text-gray-900">Masuk ke POS Restaurant</h2>
          <p class="mt-1.5 text-sm leading-relaxed text-gray-500">
            Masukkan email dan password akun Anda<br />untuk mengakses sistem POS Restaurant.
          </p>
        </div>

        <!-- ===== Error alert (API / auth failure) ===== -->
        <div
          v-if="errorMessage"
          class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4"
        >
          <div class="flex items-start gap-3">
            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-500">
              <AlertCircle class="h-4 w-4 text-white" />
            </div>
            <div>
              <p class="text-sm font-semibold text-red-700">Autentikasi Gagal</p>
              <p class="mt-0.5 text-sm leading-relaxed text-red-600">{{ errorMessage }}</p>
            </div>
          </div>
        </div>

        <!-- ===== Form ===== -->
        <form @submit.prevent="handleLogin" class="space-y-5">
          <!-- Email field -->
          <div>
            <label for="email" class="mb-2 block text-sm font-bold text-gray-800">Email</label>
            <div
              :class="[
                'flex items-center gap-3 py-2.5 transition-all',
                emailError
                  ? 'rounded-lg border border-red-500 px-3'
                  : 'border-b border-gray-200 px-1',
              ]"
            >
              <Mail class="h-5 w-5 shrink-0 text-gray-400" />
              <input
                id="email"
                v-model="email"
                type="text"
                placeholder="Masukkan email Anda"
                class="w-full bg-transparent text-sm text-gray-700 placeholder-gray-400 outline-none"
              />
            </div>
            <p v-if="emailError" class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-500">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ emailError }}
            </p>
          </div>

          <!-- Password field -->
          <div>
            <label for="password" class="mb-2 block text-sm font-bold text-gray-800">Password</label>
            <div
              :class="[
                'flex items-center gap-3 py-2.5 transition-all',
                passwordError
                  ? 'rounded-lg border border-red-500 px-3'
                  : 'border-b border-gray-200 px-1',
              ]"
            >
              <LockKeyhole class="h-5 w-5 shrink-0 text-gray-400" />
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Masukkan password Anda"
                class="w-full bg-transparent text-sm text-gray-700 placeholder-gray-400 outline-none"
              />
              <button
                type="button"
                class="shrink-0 text-gray-400 transition-colors hover:text-gray-600"
                tabindex="-1"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="h-5 w-5" />
                <Eye v-else class="h-5 w-5" />
              </button>
            </div>
            <p v-if="passwordError" class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-500">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ passwordError }}
            </p>
          </div>

          <!-- Info hint box -->
          <div class="flex items-start gap-2.5 rounded-lg bg-gray-50 px-4 py-3">
            <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
            <p class="text-xs leading-relaxed text-gray-500">
              Pastikan email dan password yang anda masukkan sudah benar.
            </p>
          </div>

          <!-- Submit button -->
          <button
            type="submit"
            :disabled="isLoading"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#C2410C] px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#B45309] focus:outline-none focus:ring-2 focus:ring-[#C2410C]/50 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <span v-if="isLoading">Memproses…</span>
            <template v-else>
              Masuk
              <LogIn class="h-4 w-4" />
            </template>
          </button>
        </form>
      </div>
    </div>

    <!-- ===== Footer ===== -->
    <div class="mt-6 flex w-full max-w-md items-center justify-between px-2 text-xs text-gray-400">
      <div class="flex items-center gap-1.5">
        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
        <span>Siap Digunakan</span>
      </div>
      <span>POS Restaurant</span>
    </div>
  </div>
</template>
