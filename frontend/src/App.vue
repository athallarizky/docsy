<script setup>
import { useRoute } from 'vue-router'
import { computed } from 'vue'
import AppShell from './layouts/AppShell.vue'

const route = useRoute()
const isBare = computed(() => route.meta.guestOnly)
</script>

<template>
  <component :is="isBare ? 'div' : AppShell" class="min-h-screen">
    <RouterView />
  </component>
</template>
