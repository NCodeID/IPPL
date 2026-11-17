<script setup lang="ts">
import KitchenIcon from './KitchenIcon.vue'
</script>

<template>
  <aside class="kds-sidebar">
    <div class="kds-brand">
      <span class="kds-brand__mark">
        <KitchenIcon name="utensils" />
      </span>
      <span class="kds-brand__text">
        <span class="kds-brand__name">Resto POS</span>
        <span class="kds-brand__sub">KDS SYSTEM</span>
      </span>
    </div>

    <section class="kds-connection" aria-label="Koneksi sistem">
      <header class="kds-connection__title">
        <span>KONEKSI SISTEM</span>
        <span class="kds-connection__dot" aria-hidden="true" />
      </header>
      <p class="kds-connection__line">
        <span>KDS Sync</span>
        <span class="kds-connection__ok">Online (14ms)</span>
      </p>
      <p class="kds-connection__line">
        <span>POS Terminal #1</span>
        <span class="kds-connection__ok">Terhubung</span>
      </p>
    </section>
  </aside>
</template>
