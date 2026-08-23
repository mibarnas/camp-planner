<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ListChecks, Tent } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavCamp from '@/components/NavCamp.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useI18n } from '@/i18n';
import { index as activitiesIndex } from '@/routes/activities';
import { index as campsIndex } from '@/routes/camps';
import type { NavItem } from '@/types';

const { t } = useI18n();

const mainNavItems: NavItem[] = [
    {
        title: t('nav.camps'),
        href: campsIndex(),
        icon: Tent,
    },
    {
        title: t('nav.activities'),
        href: activitiesIndex(),
        icon: ListChecks,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="campsIndex()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="gap-4">
            <NavMain :items="mainNavItems" />
            <NavCamp />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
