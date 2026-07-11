<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Crown, Plus, Tent, Users } from '@lucide/vue';
import { ref } from 'vue';
import CampFormDialog from '@/components/camp/CampFormDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show } from '@/routes/camps';
import type { ActivityLibraryRef, CampListItem } from '@/types/camp';

defineProps<{ camps: CampListItem[]; libraries: ActivityLibraryRef[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tábory', href: '/camps' }],
    },
});

const dialogOpen = ref(false);

function formatRange(start: string | null, end: string | null): string {
    if (!start || !end) {
        return '';
    }
    const opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'numeric' };
    return `${new Date(start).toLocaleDateString('sk-SK', opts)} – ${new Date(end).toLocaleDateString('sk-SK', opts)}`;
}
</script>

<template>
    <Head title="Tábory" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Tábory" description="Plánovanie denných táborov pre tvoju farnosť." />
            <Button @click="dialogOpen = true">
                <Plus />
                Nový tábor
            </Button>
        </div>

        <div v-if="camps.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="camp in camps" :key="camp.id" :href="show(camp.id).url" class="group">
                <Card class="h-full py-0 transition-colors group-hover:border-primary/50">
                    <CardContent class="flex h-full flex-col gap-3 p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Tent class="size-5" />
                            </div>
                            <Badge v-if="camp.role === 'owner'" variant="secondary" class="gap-1">
                                <Crown class="size-3" /> Vlastník
                            </Badge>
                            <Badge v-else variant="outline">Vedúci</Badge>
                        </div>

                        <div>
                            <h3 class="leading-tight font-semibold">{{ camp.name }}</h3>
                            <p class="text-sm text-muted-foreground">{{ camp.year }}</p>
                        </div>

                        <p v-if="camp.description" class="line-clamp-2 text-sm text-muted-foreground">
                            {{ camp.description }}
                        </p>

                        <div class="mt-auto flex items-center gap-4 pt-2 text-sm text-muted-foreground">
                            <span class="flex items-center gap-1.5">
                                <CalendarDays class="size-4" />
                                {{ formatRange(camp.start_date, camp.end_date) }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <Users class="size-4" />
                                {{ camp.days_count }} dní
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <div v-else class="rounded-xl border border-dashed p-12 text-center">
            <Tent class="mx-auto size-10 text-muted-foreground" />
            <h3 class="mt-4 font-medium">Zatiaľ žiadne tábory</h3>
            <p class="mt-1 text-sm text-muted-foreground">Vytvor svoj prvý denný tábor a naplánuj program.</p>
            <Button class="mt-4" @click="dialogOpen = true">
                <Plus />
                Nový tábor
            </Button>
        </div>
    </div>

    <CampFormDialog v-model:open="dialogOpen" :libraries="libraries" />
</template>
