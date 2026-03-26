<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  slotId: {
    type: String,
    default: 'default-ad'
  }
})

const loaded = ref(false)

onMounted(() => {
  // Simulate ad loading delay
  setTimeout(() => {
    loaded.value = true
  }, 300)
})
</script>

<template>
  <div class="ad-container mus-glass-dark my-4 overflow-hidden rounded-2xl border border-white/5">
    <div v-if="!loaded" class="flex flex-column align-items-center justify-content-center h-full py-10 opacity-20">
      <div class="spinner-sm mb-2"></div>
      <span class="text-[8px] font-black uppercase tracking-[0.3em]">Cargando Publicidad...</span>
    </div>
    
    <div v-else class="relative h-full min-h-[100px] flex align-items-center justify-content-center p-4">
      <div class="absolute top-2 right-4 text-[8px] font-black text-slate-600 uppercase tracking-widest">Publicidad de Google</div>
      
      <!-- Placeholder content that looks like a real ad -->
      <div class="flex align-items-center gap-6">
        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#0fb361] to-[#0ca358] flex align-items-center justify-center shadow-lg">
          <i class="pi pi-verified text-2xl text-black"></i>
        </div>
        <div class="space-y-1">
          <h4 class="text-white font-black italic uppercase text-lg m-0">¡Únete al Torneo Nacional!</h4>
          <p class="text-slate-400 text-xs font-bold m-0 italic">Aprende de los maestros del Mus. Solo este fin de semana.</p>
          <div class="mt-2 inline-flex align-items-center bg-[#0fb361] text-black text-[10px] font-black px-4 py-1 rounded-full uppercase">Saber más</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ad-container {
  min-height: 120px;
  background: linear-gradient(135deg, rgba(255,255,255,0.02) 0%, rgba(255,255,255,0) 100%);
  position: relative;
}
.ad-container::before {
  content: 'AD';
  position: absolute;
  top: 10px;
  left: 10px;
  font-size: 8px;
  font-weight: 950;
  color: #1e293b;
  opacity: 0.5;
}
</style>
