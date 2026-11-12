<script setup lang="ts">
// ─── Layout pembungkus utama untuk halaman dashboard ────────────────────────
// Menggabungkan tiga bagian: sidebar navigasi di kiri, header di atas,
// dan area konten utama (<slot />) di tengah-kanan dengan latar abu-abu terang.
// Digunakan oleh semua halaman yang membutuhkan navigasi sidebar dan header.
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
</script>

<template>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar navigasi tetap di sisi kiri -->
    <AppSidebar />

    <!-- Kolom utama: header di atas + konten di bawahnya -->
    <div class="flex flex-1 flex-col overflow-hidden">
      <!-- Header bar di bagian atas area konten -->
      <AppHeader />

      <!-- Area konten utama dengan scroll mandiri dan latar abu-abu terang -->
      <main class="flex-1 overflow-y-auto bg-[#F8FAFC] p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

