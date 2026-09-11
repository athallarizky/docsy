<script setup>
import { useToast } from '../../composables/useToast'

const { toasts } = useToast()
</script>

<template>
  <Teleport to="body">
    <div class="pointer-events-none fixed bottom-4 right-4 z-[60] space-y-2">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-lg"
          :class="toast.type === 'error' ? 'bg-red-600' : 'bg-slate-900 dark:bg-slate-700'"
        >
          {{ toast.message }}
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
