<script setup lang="ts">
import KitchenIcon from './KitchenIcon.vue'
</script>

<template>
  <header class="kds-topbar">
    <div class="kds-bell" aria-hidden="true">
      <KitchenIcon name="bell" />
      <span class="kds-bell__badge" />
    </div>

    <div class="kds-tab">
      <KitchenIcon name="chef" />
      <span>Dapur</span>
    </div>

    <div class="kds-user">
      <span class="kds-user__text">
        <span class="kds-user__name">Dimas Arya</span>
        <span class="kds-user__role">Supervisor On-Duty</span>
      </span>
      <span class="kds-avatar" aria-hidden="true">
        <KitchenIcon name="user" />
        <span class="kds-avatar__status" />
      </span>
    </div>
  </header>
</template>
