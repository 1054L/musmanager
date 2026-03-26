<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from './layout/AppLayout.vue'
import MusMasterLayout from './layout/MusMasterLayout.vue'

const route = useRoute()

// Determine layout based on route meta or path
const layout = computed(() => {
  if (route.meta.layout === 'sakai') {
    return AppLayout
  }
  return MusMasterLayout
})
</script>

<template>
  <component :is="layout">
    <router-view />
  </component>
</template>

<style>
/* Global resets or shared transitions can go here if needed */
</style>
