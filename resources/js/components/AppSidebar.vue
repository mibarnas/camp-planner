<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CircleQuestionMark, ListChecks, Sparkles, Tent } from '@lucide/vue';
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
import { campOnboardingOpen } from '@/lib/campOnboarding';
import { changelogOpen } from '@/lib/changelog';
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
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        :tooltip="t('nav.help')"
                        @click="campOnboardingOpen = true"
                    >
                        <CircleQuestionMark />
                        <span>{{ t('nav.help') }}</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        :tooltip="t('nav.whatsNew')"
                        @click="changelogOpen = true"
                    >
                        <Sparkles />
                        <span>{{ t('nav.whatsNew') }}</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
