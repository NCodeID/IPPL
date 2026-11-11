<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

function handleLogout(): void {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-100">
    <div class="w-full max-w-lg rounded-2xl bg-white p-10 shadow-lg">
      <h1 class="mb-6 text-center text-3xl font-bold text-gray-800">
        Dashboard Akuntan
      </h1>

      <p class="mb-8 text-center text-gray-500">
        Selamat datang di halaman akuntan. Gunakan menu navigasi untuk mengelola
        data keuangan.
      </p>

      <div class="flex justify-center">
        <button
          type="button"
          class="rounded-lg bg-red-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2"
          @click="handleLogout"
        >
          Logout
        </button>
      </div>
    </div>
  </div>
</template>

