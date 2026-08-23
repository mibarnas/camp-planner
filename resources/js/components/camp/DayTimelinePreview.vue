<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/i18n';
import { colorStyle } from '@/lib/campColors';
import { minToTime, timeToMin } from '@/lib/timeline';
import type { TimeSlot } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    slots: TimeSlot[];
    highlightId?: number | null;
}>();

const emit = defineEmits<{ select: [slot: TimeSlot] }>();

/** One pixel per minute keeps the preview honest about how long a block is. */
const PX_PER_MIN = 1;
const GUTTER = 44;

const bounds = computed(() => {
    if (!props.slots.length) {
        return { start: 8 * 60, end: 22 * 60 };
    }

    const starts = props.slots.map((s) => timeToMin(s.start_time));
    const ends = props.slots.map((s) => timeToMin(s.end_time));

    return {
        start: Math.max(0, Math.floor(Math.min(...starts) / 60) * 60 - 30),
        end: Math.min(1440, Math.ceil(Math.max(...ends) / 60) * 60 + 30),
    };
});

const height = computed(
    () => (bounds.value.end - bounds.value.start) * PX_PER_MIN,
);

const hours = computed(() => {
    const marks: number[] = [];

    for (
        let min = Math.ceil(bounds.value.start / 60) * 60;
        min <= bounds.value.end;
        min += 60
    ) {
        marks.push(min);
    }

    return marks;
});

/**
 * Blocks that overlap in time share the width, so a clash is visible instead of
 * one block hiding behind another.
 */
const placed = computed(() => {
    const sorted = [...props.slots].sort(
        (a, b) => timeToMin(a.start_time) - timeToMin(b.start_time),
    );
    const laneEnds: number[] = [];
    const rows = sorted.map((slot) => {
        const start = timeToMin(slot.start_time);
        const end = timeToMin(slot.end_time);

        let lane = laneEnds.findIndex((laneEnd) => laneEnd <= start);

        if (lane === -1) {
            lane = laneEnds.length;
        }

        laneEnds[lane] = end;

        return { slot, start, end, lane };
    });

    const lanes = Math.max(1, laneEnds.length);

    return rows.map((row) => ({
        ...row,
        top: (row.start - bounds.value.start) * PX_PER_MIN,
        height: Math.max(18, (row.end - row.start) * PX_PER_MIN),
        widthPct: 100 / lanes,
        leftPct: (100 / lanes) * row.lane,
    }));
});
</script>

<template>
    <div>
        <p
            v-if="!slots.length"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            {{ t('slots.empty') }}
        </p>

        <div v-else class="relative" :style="{ height: `${height}px` }">
            <!-- hour ruler -->
            <div
                v-for="mark in hours"
                :key="mark"
                class="absolute right-0 left-0 flex items-center gap-2"
                :style="{
                    top: `${(mark - bounds.start) * PX_PER_MIN}px`,
                }"
            >
                <span
                    class="w-9 shrink-0 text-right text-[11px] text-muted-foreground tabular-nums"
                >
                    {{ minToTime(mark) }}
                </span>
                <span class="h-px flex-1 bg-border" />
            </div>

            <!-- blocks, on their own track so lane widths stay proportional -->
            <div
                class="absolute inset-y-0 right-0"
                :style="{ left: `${GUTTER}px` }"
            >
                <button
                    v-for="row in placed"
                    :key="row.slot.id"
                    type="button"
                    class="absolute overflow-hidden rounded-md border px-2 py-1 text-left transition-shadow hover:shadow-sm"
                    :class="[
                        colorStyle(row.slot.color).cell,
                        row.slot.kind === 'fixed' ? 'border-dashed' : '',
                        highlightId === row.slot.id
                            ? 'ring-2 ring-primary ring-offset-1'
                            : '',
                    ]"
                    :style="{
                        top: `${row.top}px`,
                        height: `${row.height}px`,
                        left: `${row.leftPct}%`,
                        width: `calc(${row.widthPct}% - 4px)`,
                    }"
                    @click="emit('select', row.slot)"
                >
                    <span class="block truncate text-xs font-medium">
                        {{ row.slot.name }}
                    </span>
                    <span
                        v-if="row.height >= 34"
                        class="block truncate text-[11px] tabular-nums opacity-75"
                    >
                        {{ row.slot.start_time }}–{{ row.slot.end_time }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
