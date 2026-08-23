<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { Clock, Lock, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref, watchEffect } from 'vue';
import DayTimelinePreview from '@/components/camp/DayTimelinePreview.vue';
import SlotFormDialog from '@/components/camp/SlotFormDialog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useI18n } from '@/i18n';
import { colorStyle } from '@/lib/campColors';
import { index as campsIndex, show } from '@/routes/camps';
import { destroy as destroySlot } from '@/routes/slots';
import type { TimeSlot } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    camp: { id: number; name: string; schedule_locked: boolean };
    slots: TimeSlot[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.camps'), href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: t('nav.camp.slots'), href: '#' },
        ],
    });
});

const editing = ref<TimeSlot | null>(null);
const slotDialog = ref(false);
const removing = ref<TimeSlot | null>(null);
const hoveredId = ref<number | null>(null);

function add() {
    editing.value = null;
    slotDialog.value = true;
}

function edit(slot: TimeSlot) {
    if (props.camp.schedule_locked) {
        return;
    }

    editing.value = slot;
    slotDialog.value = true;
}

function remove() {
    if (!removing.value) {
        return;
    }

    router.delete(destroySlot(removing.value.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${t('nav.camp.slots')} — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="grid gap-2">
                <Heading
                    :title="t('nav.camp.slots')"
                    :description="t('camps.blocks.description')"
                />
                <p class="-mt-4 max-w-3xl text-sm text-muted-foreground">
                    {{ t('camps.blocks.help') }}
                </p>
            </div>
            <Button
                v-if="!camp.schedule_locked"
                class="shrink-0 sm:w-fit"
                @click="add"
            >
                <Plus /> {{ t('slots.add') }}
            </Button>
        </div>

        <div
            v-if="camp.schedule_locked"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-950/30"
        >
            <Lock class="size-5 text-amber-600 dark:text-amber-400" />
            <p class="text-sm">
                <strong>{{ t('camps.locked.title') }}</strong>
                {{ t('camps.blocks.lockedBody') }}
            </p>
        </div>

        <div
            class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,26rem)]"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('camps.blocks.editorTitle') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid content-start gap-2">
                    <div
                        v-for="slot in slots"
                        :key="slot.id"
                        class="flex items-center justify-between gap-2 rounded-lg border p-2.5"
                        @mouseenter="hoveredId = slot.id"
                        @mouseleave="hoveredId = null"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="size-3 shrink-0 rounded-full"
                                :class="colorStyle(slot.color).dot"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ slot.name }}
                                </p>
                                <p
                                    class="flex items-center gap-1 text-xs text-muted-foreground"
                                >
                                    <Clock class="size-3" />
                                    {{ slot.start_time }}–{{ slot.end_time }}
                                    <span
                                        v-if="slot.kind === 'fixed'"
                                        class="ml-1 rounded bg-muted px-1"
                                    >
                                        {{ t('slots.fixedTag') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div
                            v-if="!camp.schedule_locked"
                            class="flex shrink-0 gap-1"
                        >
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :title="t('common.edit')"
                                @click="edit(slot)"
                            >
                                <Pencil />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :title="t('common.remove')"
                                @click="removing = slot"
                            >
                                <Trash2 class="text-destructive" />
                            </Button>
                        </div>
                    </div>
                    <p
                        v-if="!slots.length"
                        class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ t('slots.empty') }}
                    </p>
                </CardContent>
            </Card>

            <Card class="self-start lg:sticky lg:top-4">
                <CardHeader>
                    <CardTitle>{{ t('camps.blocks.previewTitle') }}</CardTitle>
                    <CardDescription>
                        {{ t('camps.blocks.previewHint') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <DayTimelinePreview
                        :slots="slots"
                        :highlight-id="hoveredId"
                        @select="edit"
                    />
                </CardContent>
            </Card>
        </div>
    </div>

    <SlotFormDialog
        v-model:open="slotDialog"
        :camp-id="camp.id"
        :editing="editing"
    />

    <ConfirmDialog
        :open="!!removing"
        :title="t('common.remove')"
        :description="t('slots.confirmDelete', { name: removing?.name ?? '' })"
        @update:open="removing = null"
        @confirm="remove"
    />
</template>
