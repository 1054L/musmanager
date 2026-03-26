<script setup>
import { computed, watch, ref } from 'vue';
import AppTopbar from './AppTopbar.vue';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import { useLayout } from './composables/layout';

const { layoutConfig, layoutState } = useLayout();

const containerClass = computed(() => {
    return {
        'layout-theme-light': !layoutConfig.darkTheme,
        'layout-theme-dark': layoutConfig.darkTheme,
        'layout-overlay': layoutConfig.menuMode === 'overlay',
        'layout-static': layoutConfig.menuMode === 'static',
        'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
        'layout-overlay-active': layoutState.overlayMenuActive,
        'layout-mobile-active': layoutState.staticMenuMobileActive,
        'p-ripple-disabled': layoutConfig.ripple === false
    };
});
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <app-topbar></app-topbar>
        <div class="layout-sidebar">
            <app-sidebar></app-sidebar>
        </div>
        <div class="layout-main-container">
            <div class="layout-main">
                <slot />
                <router-view v-if="!$slots.default"></router-view>
            </div>
            <app-footer></app-footer>
        </div>
        <div class="layout-mask"></div>
    </div>
</template>

<style lang="scss">
/* Simple Sakai-like Styles if not importing full SCSS */
.layout-wrapper {
    min-height: 100vh;
}
</style>
