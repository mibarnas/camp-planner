<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Crown, Search, Tent, UserX, Users } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/i18n';
import { colorStyle } from '@/lib/campColors';
import { campIcon } from '@/lib/campIcons';
import { dateLocale } from '@/lib/datetime';
import { index as adminIndex } from '@/routes/admin';

type AdminCamp = {
    id: number;
    name: string;
    icon: string;
    color: string;
    year: number;
    start_date: string;
    end_date: string;
    role: 'owner' | 'leader';
};

type AdminUser = {
    id: number;
    name: string;
    email: string;
    email_verified: boolean;
    created_at: string | null;
    camps: AdminCamp[];
};

const props = defineProps<{
    users: AdminUser[];
    stats: { users: number; camps: number; usersWithoutCamp: number };
}>();

const { t } = useI18n();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('nav.admin'), href: adminIndex().url }],
    });
});

const query = ref('');

// Matches the person or any of their camps, so "who is on camp X" is one search.
const filtered = computed(() => {
    const q = query.value.trim().toLocaleLowerCase();

    if (!q) {
        return props.users;
    }

    return props.users.filter(
        (user) =>
            user.name.toLocaleLowerCase().includes(q) ||
            user.email.toLocaleLowerCase().includes(q) ||
            user.camps.some((camp) =>
                camp.name.toLocaleLowerCase().includes(q),
            ),
    );
});

const statCards = computed(() => [
    { label: t('admin.stats.users'), value: props.stats.users, icon: Users },
    { label: t('admin.stats.camps'), value: props.stats.camps, icon: Tent },
    {
        label: t('admin.stats.usersWithoutCamp'),
        value: props.stats.usersWithoutCamp,
        icon: UserX,
    },
]);

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString(dateLocale()) : '—';
}

function formatRange(start: string, end: string): string {
    const opts: Intl.DateTimeFormatOptions = {
        day: 'numeric',
        month: 'numeric',
    };

    return `${new Date(start).toLocaleDateString(dateLocale(), opts)} – ${new Date(end).toLocaleDateString(dateLocale(), opts)}`;
}
</script>

<template>
    <Head :title="t('nav.admin')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('nav.admin')"
            :description="t('admin.description')"
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <Card v-for="stat in statCards" :key="stat.label" class="py-0">
                <CardContent class="flex items-center gap-4 p-5">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <component :is="stat.icon" class="size-5" />
                    </div>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ stat.value }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ stat.label }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="relative max-w-sm">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="query"
                type="search"
                class="pl-9"
                :placeholder="t('admin.search')"
            />
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">
                            {{ t('admin.column.user') }}
                        </th>
                        <th class="px-4 py-3 font-medium whitespace-nowrap">
                            {{ t('admin.column.registered') }}
                        </th>
                        <th class="px-4 py-3 font-medium">
                            {{ t('admin.column.camps') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="user in filtered"
                        :key="user.id"
                        class="align-top"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ user.name }}</div>
                            <div
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                {{ user.email }}
                                <Badge
                                    v-if="!user.email_verified"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ t('admin.unverified') }}
                                </Badge>
                            </div>
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                        >
                            {{ formatDate(user.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <ul
                                v-if="user.camps.length"
                                class="flex flex-wrap gap-2"
                            >
                                <li
                                    v-for="camp in user.camps"
                                    :key="camp.id"
                                    class="flex items-center gap-2 rounded-lg border py-1 pr-2.5 pl-1"
                                >
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-md"
                                        :class="colorStyle(camp.color).chip"
                                    >
                                        <component
                                            :is="campIcon(camp.icon)"
                                            class="size-3.5"
                                        />
                                    </span>
                                    <span class="leading-tight">
                                        <span class="font-medium">
                                            {{ camp.name }}
                                        </span>
                                        <span
                                            class="block text-xs text-muted-foreground"
                                        >
                                            {{ camp.year }} ·
                                            {{
                                                formatRange(
                                                    camp.start_date,
                                                    camp.end_date,
                                                )
                                            }}
                                        </span>
                                    </span>
                                    <Badge
                                        v-if="camp.role === 'owner'"
                                        variant="secondary"
                                        class="gap-1"
                                    >
                                        <Crown class="size-3" />
                                        {{ t('camps.role.owner') }}
                                    </Badge>
                                    <Badge v-else variant="outline">
                                        {{ t('camps.role.leader') }}
                                    </Badge>
                                </li>
                            </ul>
                            <span v-else class="text-muted-foreground">
                                {{ t('admin.noCamps') }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!filtered.length">
                        <td
                            colspan="3"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{ t('common.noResults') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
