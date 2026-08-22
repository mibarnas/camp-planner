<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    Columns3,
    Flag,
    Settings,
    Star,
    Trophy,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { colorStyle } from '@/lib/campColors';
import { campIcon } from '@/lib/campIcons';
import { feedback, leaderboard, settings, show } from '@/routes/camps';
import { index as groupsIndex } from '@/routes/groups';
import { index as leadersIndex } from '@/routes/leaders';
import { index as slotsIndex } from '@/routes/slots';
import type { NavItem } from '@/types';

const page = usePage();
const camp = computed(() => page.props.campContext);

const items = computed<NavItem[]>(() => {
    const c = camp.value;

    if (!c) {
        return [];
    }

    const list: NavItem[] = [
        { title: 'Plánovač', href: show(c.id), icon: Calendar },
        { title: 'Vedúci', href: leadersIndex(c.id), icon: Users },
        { title: 'Skupiny', href: groupsIndex(c.id), icon: Flag },
        { title: 'Časové bloky', href: slotsIndex(c.id), icon: Columns3 },
        { title: 'Spätná väzba', href: feedback(c.id), icon: Star },
        { title: 'Rebríček', href: leaderboard(c.id), icon: Trophy },
    ];

    if (c.is_owner) {
        list.push({
            title: 'Nastavenia',
            href: settings(c.id),
            icon: Settings,
        });
    }

    return list;
});

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup v-if="camp" class="px-2 py-0">
        <SidebarGroupLabel class="gap-1.5">
            <span
                class="flex size-4 shrink-0 items-center justify-center rounded"
                :class="colorStyle(camp.color).chip"
            >
                <component :is="campIcon(camp.icon)" class="size-2.5" />
            </span>
            <span class="truncate">{{ camp.name }}</span>
        </SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
