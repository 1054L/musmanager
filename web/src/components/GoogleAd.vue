<script setup>
import { onMounted, ref } from 'vue'

const props = defineProps({
  slotId: {
    type: String,
    default: ''
  },
  format: {
    type: String,
    default: 'auto'
  },
  fullWidthResponsive: {
    type: Boolean,
    default: true
  }
})

const adInitialized = ref(false)

onMounted(() => {
  try {
    if (typeof window !== 'undefined') {
      ;(window.adsbygoogle = window.adsbygoogle || []).push({})
      adInitialized.value = true
    }
  } catch (e) {
    // AdSense script blocked by adblocker or already initialized for this slot
  }
})
</script>

<template>
  <div class="ad-container my-4 overflow-hidden rounded-2xl">
    <ins
      class="adsbygoogle"
      style="display: block"
      data-ad-client="ca-pub-4985794971723153"
      :data-ad-slot="props.slotId || undefined"
      :data-ad-format="props.format"
      :data-full-width-responsive="props.fullWidthResponsive ? 'true' : 'false'"
    ></ins>
  </div>
</template>

<style scoped>
.ad-container {
  position: relative;
  text-align: center;
}

/* Hide container collapse when AdSense has no fill */
:deep(ins.adsbygoogle[data-ad-status="unfilled"]) {
  display: none !important;
}
</style>
