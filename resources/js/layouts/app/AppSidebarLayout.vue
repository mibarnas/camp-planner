<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppFooter from '@/components/AppFooter.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import CampOnboardingDialog from '@/components/camp/CampOnboardingDialog.vue';
import ChangelogDialog from '@/components/ChangelogDialog.vue';
import { Toaster } from '@/components/ui/sonner';
import { campOnboardingOpen } from '@/lib/campOnboarding';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
            <AppFooter />
        </AppContent>
        <Toaster />

        <!-- Both are reachable from anywhere: the changelog after an update,
             onboarding from the sidebar's help button. -->
        <ChangelogDialog />
        <CampOnboardingDialog v-model:open="campOnboardingOpen" />
    </AppShell>
</template>
