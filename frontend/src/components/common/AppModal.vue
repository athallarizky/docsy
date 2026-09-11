<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  wide: { type: Boolean, default: false },
})
const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4 backdrop-blur-sm"
      @click.self="emit('close')"
    >
      <div
        class="w-full rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
        :class="wide ? 'max-w-4xl' : 'max-w-md'"
      >
        <header class="flex items-center justify-between border-b border-slate-200 px-5 py-3 dark:border-slate-700">
          <h3 class="font-semibold">{{ title }}</h3>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
            @click="emit('close')"
          >
            ✕
          </button>
        </header>
        <div class="max-h-[75vh] overflow-y-auto p-5">
          <slot />
        </div>
      </div>
    </div>
  </Teleport>
</template>
