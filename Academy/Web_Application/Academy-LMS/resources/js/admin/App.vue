<template>
    <div class="min-h-screen bg-slate-100">
        <div class="flex min-h-screen">
            <NavigationSidebar :items="navigation" />
            <div class="flex flex-1 flex-col">
                <TopBar />
                <main class="flex-1 overflow-y-auto bg-slate-100 p-4 md:p-6">
                    <RouterView />
                </main>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router';
import NavigationSidebar from '@/admin/components/NavigationSidebar.vue';
import TopBar from '@/admin/components/TopBar.vue';
import { moduleNavigation } from '@/core/modules/module-registry';
import { useAppContext } from '@/core/composables/useAppContext';

useAppContext();

const navigation = moduleNavigation;
</script>
