<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { watchEffect } from 'vue';
import SlotsPanel from '@/components/camp/SlotsPanel.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
import { index as campsIndex, show } from '@/routes/camps';
import type { TimeSlot } from '@/types/camp';

const props = defineProps<{
    camp: { id: number; name: string; schedule_locked: boolean };
    slots: TimeSlot[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tábory', href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: 'Časové bloky', href: '#' },
        ],
    });
});
</script>

<template>
    <Head :title="`Časové bloky — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Časové bloky"
            description="Denná časová kostra — šablóna pre všetky dni tábora."
        />

        <p class="-mt-4 max-w-3xl text-sm text-muted-foreground">
            „Pevné" bloky (obed, oddych) sú kostrou dňa; do „programových"
            blokov dopĺňaš obsah po dňoch. Blok sa dá v rozvrhu presunúť alebo
            skryť aj len pre jeden deň (napr. výlet) — také dni si svoju úpravu
            ponechajú aj po zmene šablóny.
        </p>

        <div
            v-if="camp.schedule_locked"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-950/30"
        >
            <Lock class="size-5 text-amber-600 dark:text-amber-400" />
            <p class="text-sm">
                <strong>Program je uzamknutý</strong> — bloky sa nedajú
                upravovať, kým ho vlastník neodomkne.
            </p>
        </div>

        <Card class="max-w-4xl">
            <CardContent>
                <SlotsPanel
                    :camp-id="camp.id"
                    :slots="slots"
                    :editable="!camp.schedule_locked"
                />
            </CardContent>
        </Card>
    </div>
</template>
