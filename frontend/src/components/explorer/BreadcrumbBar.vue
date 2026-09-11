<script setup>
defineProps({
  breadcrumbs: { type: Array, required: true },
})
const emit = defineEmits(['navigate'])
</script>

<template>
  <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1 text-sm">
    <button
      type="button"
      class="rounded-lg px-2 py-1 font-medium hover:bg-slate-100 dark:hover:bg-slate-800"
      @click="emit('navigate', null)"
    >
      🏠 Home
    </button>
    <template v-for="crumb in breadcrumbs" :key="crumb.id">
      <span class="text-slate-400">/</span>
      <button
        type="button"
        class="rounded-lg px-2 py-1 hover:bg-slate-100 dark:hover:bg-slate-800"
        :class="crumb.id === breadcrumbs.at(-1)?.id ? 'font-semibold text-brand-600 dark:text-brand-100' : ''"
        @click="emit('navigate', crumb.id)"
      >
        {{ crumb.name }}
      </button>
    </template>
  </nav>
</template>
