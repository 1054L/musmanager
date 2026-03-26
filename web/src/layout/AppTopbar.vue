<script setup>
import { useLayout } from './composables/layout';
import { useI18n } from 'vue-i18n';
import { authService } from '../services/api';

const { t } = useI18n();
const { layoutConfig, toggleMenu } = useLayout();
</script>

<template>
    <div class="layout-topbar">
        <router-link to="/" class="layout-topbar-logo flex align-items-center gap-3 no-underline">
            <div class="w-10 h-10 bg-[#0fb361] rounded-xl flex items-center justify-center text-[#050505] font-black text-xl shadow-lg shadow-[#0fb361]/20">M</div>
            <span class="text-white font-black italic tracking-tighter text-xl">MUS MANAGER</span>
        </router-link>

        <button class="p-link layout-menu-button layout-topbar-button ml-4" @click="toggleMenu()">
            <i class="pi pi-bars text-slate-400"></i>
        </button>

        <div class="layout-topbar-menu ml-auto flex align-items-center gap-2">
            <button class="p-link layout-topbar-button p-3 rounded-xl hover:bg-white/5 transition-all text-slate-400 hover:text-white">
                <i class="pi pi-bell"></i>
            </button>
            <div class="h-6 w-px bg-white/10 mx-2"></div>
            <button class="p-link layout-topbar-button flex align-items-center gap-3 px-4 py-2 rounded-xl bg-white/5 border border-white/5 hover:border-[#0fb361]/30 transition-all group">
                <div class="w-8 h-8 rounded-lg bg-[#0fb361]/10 flex items-center justify-center text-[#0fb361] font-bold text-xs uppercase">
                    {{ authService.getUser()?.email?.charAt(0)?.toUpperCase() || 'U' }}
                </div>
                <span class="text-slate-300 text-xs font-black uppercase tracking-widest hidden md:block group-hover:text-white transition-colors">
                    {{ authService.getUser()?.email?.split('@')[0] || t('common.user') }}
                </span>
            </button>
        </div>
    </div>
</template>
