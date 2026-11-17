<script setup lang="ts">
const props = defineProps<{
  name:
    | 'utensils'
    | 'bell'
    | 'chef'
    | 'user'
    | 'clock'
    | 'person'
    | 'note'
    | 'hand'
    | 'check'
    | 'chili'
    | 'leaf'
    | 'clip'
    | 'flame'
    | 'bag'
    | 'pending'
    | 'car'
    | 'sprout'
}>()

const markup: Record<typeof props.name, string> = {
  utensils:
    '<path d="M8 3v8M8 11v10M6 3c0 2.5 2 4 2 8M10 3c0 2.5-2 4-2 8M16 3v18M16 3c2 2 2 5 0 7"/>',
  bell: '<path d="M6 9a6 6 0 1 1 12 0c0 7 2 7 2 9H4c0-2 2-2 2-9"/><path d="M10 21a2 2 0 0 0 4 0"/>',
  chef: '<path d="M6 10a3 3 0 0 1 1-5 4 4 0 0 1 10 0 3 3 0 0 1 1 5v2H6v-2z"/><path d="M7 12h10v7a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-7z"/>',
  user: '<circle cx="12" cy="8" r="3.2"/><path d="M5.5 19.5c1.4-3 3.6-4.5 6.5-4.5s5.1 1.5 6.5 4.5"/>',
  clock: '<circle cx="12" cy="12" r="8"/><path d="M12 8v4.5l3 2"/>',
  person: '<circle cx="12" cy="8" r="3"/><path d="M6 19c1.2-3 3-4.5 6-4.5s4.8 1.5 6 4.5"/>',
  note: '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5M8 13h8M8 17h6"/>',
  hand: '<path d="M8 11V6.5a1.2 1.2 0 0 1 2.4 0V11"/><path d="M10.4 10.2V5.2a1.2 1.2 0 0 1 2.4 0v5"/><path d="M12.8 10.5V6.8a1.2 1.2 0 0 1 2.4 0V12"/><path d="M15.2 11.2V9.2a1.2 1.2 0 0 1 2.3.4c.3 1.6.2 3.2-.4 4.6-.8 2-2.4 4.8-5.6 4.8-2.4 0-3.8-1.2-4.7-2.6L5 13.2a1.4 1.4 0 0 1 2.2-1.6L8 13"/>',
  check: '<path d="M5 12.5 9.2 17 19 7"/>',
  chili:
    '<path d="M14 4c.4 1.6-.2 2.4-1.2 3.2-3.2 2.4-6.8 2-8.3 5.2-1.2 2.6.2 6.2 3.6 7.2 3.6 1 6.6-1.4 8.2-4.2 1.4-2.4 1.2-5.4 2.2-7.6"/><path d="M14 4c1.2.2 2.2-.4 3.2-1"/>',
  leaf: '<path d="M5 19C8 10 14 5 20 4c-1 8-6 14-15 15z"/><path d="M9 15c2-2 4-4 7-6"/>',
  clip: '<path d="M9 8.5 15.5 15a3 3 0 0 1-4.2 4.2L6 14"/><path d="M10 7.2 15.8 13a2 2 0 0 1-2.8 2.8L8.2 11"/>',
  flame: '<path d="M12 3s4 4 4 8a4 4 0 0 1-8 0c0-1.2.6-2.2 1.2-3.2C8.4 10 9 12 10.2 12 10 8 12 3 12 3z"/>',
  bag: '<path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V7a3 3 0 0 1 6 0v1"/>',
  pending: '<path d="M7 4h10M7 20h10M8 4c0 4 4 4 4 8s-4 4-4 8M16 4c0 4-4 4-4 8s4 4 4 8"/>',
  car: '<path d="M4 15h16M6 15l1.5-4.5h9L18 15"/><circle cx="7.5" cy="17.5" r="1.4"/><circle cx="16.5" cy="17.5" r="1.4"/>',
  sprout: '<path d="M12 20V10"/><path d="M12 12c-3-1-5-4-5-7 4 0 6 2 6 5"/><path d="M12 14c3-.6 5-3 6-6-4 .2-6 2.2-6 5"/>',
}
</script>

<template>
  <svg
    class="kds-icon"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    v-html="markup[name]"
  />
</template>
