<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const model = ref([
    {
        label: 'Home',
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/dashboard' }]
    },
    {
        label: 'Tournaments',
        items: [
            { label: 'My Tournaments', icon: 'pi pi-fw pi-id-card', to: '/dashboard' },
            { label: 'Live Events', icon: 'pi pi-fw pi-check-square', to: '/' }
        ]
    },
    {
        label: 'Profile',
        items: [
            { label: 'Settings', icon: 'pi pi-fw pi-cog', to: '/' },
            { label: 'Logout', icon: 'pi pi-fw pi-sign-out', to: '/login' }
        ]
    }
]);
</script>

<template>
    <ul class="layout-menu" style="border: 2px solid red">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
