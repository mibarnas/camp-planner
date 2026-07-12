<script setup lang="ts">
import {
    Check,
    CheckSquare,
    MapPin,
    Package,
    Pencil,
    Plus,
    Star,
    StickyNote,
    Trash2,
    User,
    UserCheck,
    X,
    ZoomIn,
    ZoomOut,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { colorStyle } from '@/lib/campColors';
import {
    assignLanes,
    containingSlot,
    dayBounds,
    durationLabel,
    minToTime,
    timeToMin,
} from '@/lib/timeline';
import type { CampDay, ProgramEntry, TimeSlot } from '@/types/camp';

const props = defineProps<{ days: CampDay[]; slots: TimeSlot[] }>();

const emit = defineEmits<{
    edit: [day: CampDay, entry: ProgramEntry];
    add: [day: CampDay, startMin: number];
    editDay: [day: CampDay];
    review: [day: CampDay];
    toggleDone: [entry: ProgramEntry];
    commit: [entries: ProgramEntry[]];
    bulkDelete: [ids: number[]];
    bulkResponsible: [ids: number[], responsible: string];
}>();

// --- Layout: px-per-minute stretches to fill the available width -------------
const LANE_H = 74;
const LABEL_W = 156;
const PAD = 6;
const CARD_GAP = 4;
const SNAP = 5; // minutes
const MIN_PX = 1.3;

const wrapEl = ref<HTMLElement | null>(null);
const containerW = ref(1200);
let resizeObserver: ResizeObserver | null = null;

// --- Zoom -------------------------------------------------------------------
const ZOOM_MIN = 1;
const ZOOM_MAX = 6;
const zoom = ref(1);

function zoomBy(factor: number, anchorClientX?: number) {
    const next = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, zoom.value * factor));

    if (next === zoom.value) {
return;
}

    const el = wrapEl.value;

    if (el && anchorClientX !== undefined) {
        // Keep the time under the cursor in place while zooming.
        const rect = el.getBoundingClientRect();
        const contentX = el.scrollLeft + (anchorClientX - rect.left) - LABEL_W;
        const ratio = next / zoom.value;
        zoom.value = next;
        requestAnimationFrame(() => {
            el.scrollLeft = contentX * ratio - (anchorClientX - rect.left) + LABEL_W;
        });
    } else {
        zoom.value = next;
    }
}
function resetZoom() {
    zoom.value = 1;
}
function onWheel(e: WheelEvent) {
    if (!e.ctrlKey && !e.metaKey) {
return;
}

    e.preventDefault();
    zoomBy(e.deltaY < 0 ? 1.25 : 1 / 1.25, e.clientX);
}

onMounted(() => {
    resizeObserver = new ResizeObserver((entries) => {
        containerW.value = entries[0]?.contentRect.width ?? containerW.value;
    });

    if (wrapEl.value) {
        resizeObserver.observe(wrapEl.value);
        wrapEl.value.addEventListener('wheel', onWheel, { passive: false });
    }

    window.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    wrapEl.value?.removeEventListener('wheel', onWheel);
    window.removeEventListener('keydown', onKeydown);
});

const bounds = computed(() => dayBounds(props.slots, props.days));
const totalMin = computed(() => bounds.value.end - bounds.value.start);
const pxPerMin = computed(
    () => Math.max(MIN_PX, (containerW.value - LABEL_W - 2) / totalMin.value) * zoom.value,
);
const trackWidth = computed(() => totalMin.value * pxPerMin.value);

const hourTicks = computed(() => {
    const ticks: number[] = [];
    const first = Math.ceil(bounds.value.start / 60) * 60;

    // Stop strictly before the end so a boundary that lands exactly on the hour
    // (e.g. last block ends 16:00) doesn't leave a stray label at the edge.
    for (let m = first; m < bounds.value.end; m += 60) {
ticks.push(m);
}

    return ticks;
});

function xFor(min: number): number {
    return (min - bounds.value.start) * pxPerMin.value;
}
function wFor(min: number): number {
    return min * pxPerMin.value;
}
function capitalize(v: string): string {
    return v.charAt(0).toUpperCase() + v.slice(1);
}

const layout = computed(() =>
    props.days.map((day) => {
        const { items, lanes } = assignLanes(day.entries);

        return { day, items, height: lanes * LANE_H + PAD * 2 };
    }),
);

const allEntries = computed(() => props.days.flatMap((d) => d.entries));

function cardColor(entry: ProgramEntry): string {
    return (
        entry.activity?.color ??
        containingSlot(timeToMin(entry.start_time), props.slots)?.color ??
        'slate'
    );
}

function cardTooltip(entry: ProgramEntry): string {
    const end = minToTime(timeToMin(entry.start_time) + entry.duration);
    const lines = [
        entry.title || entry.activity?.name || 'Aktivita',
        `${entry.start_time}–${end} · ${durationLabel(entry.duration)}`,
    ];

    if (entry.responsible) {
lines.push(`Zodpovedný: ${entry.responsible}`);
}

    if (entry.notes) {
lines.push(`Poznámka: ${entry.notes}`);
}

    return lines.join('\n');
}

// --- Selection ----------------------------------------------------------------
const selectedIds = ref<Set<number>>(new Set());
const bulkResponsible = ref('');

function isSelected(entry: ProgramEntry): boolean {
    return selectedIds.value.has(entry.id);
}
function toggleSelect(entry: ProgramEntry) {
    const next = new Set(selectedIds.value);

    if (next.has(entry.id)) {
next.delete(entry.id);
} else {
next.add(entry.id);
}

    selectedIds.value = next;
}
function clearSelection() {
    selectedIds.value = new Set();
}
function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        clearSelection();
        contextMenu.value = null;
    }
}

function applyBulkResponsible() {
    if (!selectedIds.value.size) {
return;
}

    emit('bulkResponsible', [...selectedIds.value], bulkResponsible.value.trim());
    bulkResponsible.value = '';
}
function deleteSelected() {
    if (!selectedIds.value.size) {
return;
}

    if (!confirm(`Zmazať ${selectedIds.value.size} vybraných aktivít?`)) {
return;
}

    emit('bulkDelete', [...selectedIds.value]);
    clearSelection();
}

// --- Context menu ---------------------------------------------------------------
type ContextMenuState = {
    x: number;
    y: number;
    day: CampDay;
    entry: ProgramEntry | null;
    startMin: number;
};
const contextMenu = ref<ContextMenuState | null>(null);

function openCardMenu(e: MouseEvent, day: CampDay, entry: ProgramEntry) {
    e.preventDefault();
    e.stopPropagation();
    contextMenu.value = { x: e.clientX, y: e.clientY, day, entry, startMin: 0 };
}
function openTrackMenu(e: MouseEvent, day: CampDay) {
    if ((e.target as HTMLElement).closest('[data-card]')) {
return;
}

    e.preventDefault();
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    const min = snap(bounds.value.start + (e.clientX - rect.left) / pxPerMin.value);
    contextMenu.value = { x: e.clientX, y: e.clientY, day, entry: null, startMin: min };
}
function closeMenu() {
    contextMenu.value = null;
}
function menuEdit() {
    const m = contextMenu.value;

    if (m?.entry) {
emit('edit', m.day, m.entry);
}

    closeMenu();
}
function menuToggleSelect() {
    const m = contextMenu.value;

    if (m?.entry) {
toggleSelect(m.entry);
}

    closeMenu();
}
function menuToggleDone() {
    const m = contextMenu.value;

    if (m?.entry) {
emit('toggleDone', m.entry);
}

    closeMenu();
}
function menuDelete() {
    const m = contextMenu.value;

    if (!m?.entry) {
return closeMenu();
}

    if (selectedIds.value.size > 1 && selectedIds.value.has(m.entry.id)) {
        emit('bulkDelete', [...selectedIds.value]);
        clearSelection();
    } else {
        emit('bulkDelete', [m.entry.id]);
    }

    closeMenu();
}
function menuAdd() {
    const m = contextMenu.value;

    if (m) {
emit('add', m.day, m.startMin);
}

    closeMenu();
}

// --- Drag / resize (single card or whole selection) ----------------------------
type DragState = {
    entry: ProgramEntry;
    mode: 'move' | 'resize';
    startX: number;
    group: { entry: ProgramEntry; origStart: number }[];
    origDur: number;
    minDelta: number;
    maxDelta: number;
    moved: boolean;
};
const drag = ref<DragState | null>(null);

function snap(min: number): number {
    return Math.round(min / SNAP) * SNAP;
}

function onPointerMove(e: PointerEvent) {
    const d = drag.value;

    if (!d) {
return;
}

    let deltaMin = snap((e.clientX - d.startX) / pxPerMin.value);

    if (Math.abs(deltaMin) >= SNAP) {
d.moved = true;
}

    if (d.mode === 'move') {
        deltaMin = Math.max(d.minDelta, Math.min(d.maxDelta, deltaMin));

        for (const g of d.group) {
            g.entry.start_time = minToTime(g.origStart + deltaMin);
        }
    } else {
        const maxDur = bounds.value.end - d.group[0].origStart;
        d.entry.duration = Math.max(SNAP, Math.min(maxDur, d.origDur + deltaMin));
    }
}

function beginDrag(e: PointerEvent, entry: ProgramEntry, day: CampDay) {
    if (e.button !== 0) {
return;
} // left button only; right button = context menu

    if ((e.target as HTMLElement).closest('[data-nodrag]')) {
return;
}

    // Ctrl/Cmd+click toggles selection instead of dragging.
    if (e.ctrlKey || e.metaKey) {
        toggleSelect(entry);

        return;
    }

    const mode = (e.target as HTMLElement).closest('[data-resize]') ? 'resize' : 'move';

    // Dragging a selected card moves the whole selection; otherwise just this card.
    const groupEntries =
        mode === 'move' && isSelected(entry)
            ? allEntries.value.filter((x) => selectedIds.value.has(x.id))
            : [entry];
    const group = groupEntries.map((g) => ({ entry: g, origStart: timeToMin(g.start_time) }));

    let minDelta = -Infinity;
    let maxDelta = Infinity;

    for (const g of group) {
        minDelta = Math.max(minDelta, bounds.value.start - g.origStart);
        maxDelta = Math.min(maxDelta, bounds.value.end - g.entry.duration - g.origStart);
    }

    drag.value = {
        entry,
        mode,
        startX: e.clientX,
        group,
        origDur: entry.duration,
        minDelta,
        maxDelta,
        moved: false,
    };

    const onUp = () => {
        const d = drag.value;
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onUp);

        if (!d) {
return;
}

        if (d.moved) {
            emit('commit', d.group.map((g) => g.entry));
        } else if (d.mode === 'move') {
            emit('edit', day, d.entry);
        }

        drag.value = null;
    };

    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onUp);
}

// Click on empty track: clear selection first; if nothing selected, add here.
function onTrackClick(e: MouseEvent, day: CampDay) {
    if (drag.value) {
return;
}

    if ((e.target as HTMLElement).closest('[data-card]')) {
return;
}

    if (contextMenu.value) {
        closeMenu();

        return;
    }

    if (selectedIds.value.size) {
        clearSelection();

        return;
    }

    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    const min = snap(bounds.value.start + (e.clientX - rect.left) / pxPerMin.value);
    emit('add', day, Math.max(bounds.value.start, min));
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <!-- Toolbar: zoom -->
        <div class="flex items-center justify-end gap-1">
            <Button variant="outline" size="icon-sm" title="Oddialiť (Ctrl+koliesko)" :disabled="zoom <= ZOOM_MIN" @click="zoomBy(1 / 1.25)">
                <ZoomOut />
            </Button>
            <button
                class="min-w-12 rounded-md px-1 py-1 text-center text-xs text-muted-foreground tabular-nums hover:bg-accent"
                title="Obnoviť priblíženie"
                @click="resetZoom"
            >
                {{ Math.round(zoom * 100) }}%
            </button>
            <Button variant="outline" size="icon-sm" title="Priblížiť (Ctrl+koliesko)" :disabled="zoom >= ZOOM_MAX" @click="zoomBy(1.25)">
                <ZoomIn />
            </Button>
        </div>

        <!-- Bulk action bar -->
        <div
            v-if="selectedIds.size"
            class="flex flex-wrap items-center gap-2 rounded-lg border bg-accent/50 px-3 py-2"
        >
            <span class="text-sm font-medium">Vybrané: {{ selectedIds.size }}</span>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1">
                    <Input
                        v-model="bulkResponsible"
                        placeholder="Zodpovedný pre všetky…"
                        class="h-8 w-48 text-sm"
                        @keydown.enter="applyBulkResponsible"
                    />
                    <Button size="sm" variant="outline" @click="applyBulkResponsible">
                        <UserCheck /> Priradiť
                    </Button>
                </div>
                <Button size="sm" variant="outline" class="text-destructive" @click="deleteSelected">
                    <Trash2 /> Zmazať
                </Button>
                <Button size="sm" variant="ghost" @click="clearSelection">
                    <X /> Zrušiť výber
                </Button>
            </div>
        </div>

        <div ref="wrapEl" class="overflow-x-auto rounded-xl border bg-card select-none">
            <div :style="{ minWidth: LABEL_W + trackWidth + 'px' }">
                <!-- Header: hour ruler + block guides -->
                <div class="sticky top-0 z-30 flex border-b bg-muted/80 backdrop-blur">
                    <div
                        class="sticky left-0 z-10 flex shrink-0 items-end bg-muted/80 p-2 text-xs font-medium text-muted-foreground backdrop-blur"
                        :style="{ width: LABEL_W + 'px' }"
                    >
                        Deň / Čas
                    </div>
                    <div class="relative" :style="{ width: trackWidth + 'px', height: '54px' }">
                        <div
                            v-for="t in hourTicks"
                            :key="'h' + t"
                            class="absolute top-1 border-l border-border/60 pl-1 text-[11px] text-muted-foreground"
                            :style="{ left: xFor(t) + 'px' }"
                        >
                            {{ minToTime(t) }}
                        </div>
                        <div
                            v-for="slot in slots"
                            :key="slot.id"
                            class="absolute bottom-1 flex h-8 flex-col justify-center overflow-hidden rounded border px-1.5"
                            :class="colorStyle(slot.color).cell"
                            :style="{ left: xFor(timeToMin(slot.start_time)) + 'px', width: wFor(timeToMin(slot.end_time) - timeToMin(slot.start_time)) - 2 + 'px' }"
                        >
                            <span class="truncate text-[11px] font-semibold leading-none" :class="colorStyle(slot.color).header">
                                {{ slot.name }}
                            </span>
                            <span class="truncate text-[10px] leading-none text-muted-foreground">
                                {{ slot.start_time }}–{{ slot.end_time }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Day rows -->
                <div
                    v-for="row in layout"
                    :key="row.day.id"
                    class="flex border-b last:border-b-0"
                    :class="row.day.is_trip ? 'bg-fuchsia-50/60 dark:bg-fuchsia-950/20' : ''"
                >
                    <div
                        class="group/day sticky left-0 z-10 shrink-0 border-r p-2 backdrop-blur"
                        :class="row.day.is_trip ? 'bg-fuchsia-50 dark:bg-fuchsia-950/30' : 'bg-card'"
                        :style="{ width: LABEL_W + 'px' }"
                    >
                        <div class="flex items-start justify-between gap-1">
                            <div>
                                <p class="font-semibold leading-tight">{{ capitalize(row.day.weekday) }}</p>
                                <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    {{ row.day.label }}
                                    <span
                                        v-if="row.day.review_summary.avg != null"
                                        class="flex items-center gap-0.5 text-amber-500"
                                        :title="`Priemer ${row.day.review_summary.avg} · ${row.day.review_summary.reviewers} hodnotení`"
                                    >
                                        <Star class="size-3 fill-amber-400 text-amber-400" />{{ row.day.review_summary.avg }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex gap-0.5 opacity-0 transition group-hover/day:opacity-100">
                                <button
                                    class="rounded p-1 hover:bg-accent"
                                    :class="row.day.my_review ? 'text-amber-500' : 'text-muted-foreground'"
                                    title="Zhodnotiť deň"
                                    @click="emit('review', row.day)"
                                >
                                    <Star class="size-3.5" :class="row.day.my_review ? 'fill-amber-400' : ''" />
                                </button>
                                <button
                                    class="rounded p-1 text-muted-foreground hover:bg-accent"
                                    title="Pridať aktivitu"
                                    @click="emit('add', row.day, bounds.start)"
                                >
                                    <Plus class="size-3.5" />
                                </button>
                                <button
                                    class="rounded p-1 text-muted-foreground hover:bg-accent"
                                    title="Upraviť deň"
                                    @click="emit('editDay', row.day)"
                                >
                                    <Pencil class="size-3.5" />
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="row.day.is_trip"
                            class="mt-1 flex items-center gap-1 rounded bg-fuchsia-100 px-1.5 py-0.5 text-[11px] text-fuchsia-800 dark:bg-fuchsia-900/50 dark:text-fuchsia-200"
                        >
                            <MapPin class="size-3 shrink-0" />
                            <span class="truncate">{{ row.day.trip_name || 'Výlet' }}</span>
                        </div>
                        <p v-if="row.day.name_days" class="mt-1 text-[11px] leading-tight text-muted-foreground">
                            <span class="font-medium">Meniny:</span> {{ row.day.name_days }}
                        </p>
                        <p v-if="row.day.birthdays" class="text-[11px] leading-tight text-muted-foreground">
                            🎂 {{ row.day.birthdays }}
                        </p>
                        <p
                            v-if="row.day.materials"
                            class="mt-1 flex items-start gap-1 text-[11px] leading-tight text-amber-700 dark:text-amber-400"
                        >
                            <Package class="mt-px size-3 shrink-0" />
                            <span class="line-clamp-2">{{ row.day.materials }}</span>
                        </p>
                    </div>

                    <!-- Track -->
                    <div
                        class="relative"
                        :style="{ width: trackWidth + 'px', height: row.height + 'px' }"
                        @click="onTrackClick($event, row.day)"
                        @contextmenu="openTrackMenu($event, row.day)"
                    >
                        <div
                            v-for="slot in slots"
                            :key="'bg' + slot.id"
                            class="absolute top-0 bottom-0 border-l border-dashed"
                            :class="[colorStyle(slot.color).cell, slot.kind === 'fixed' ? 'opacity-90' : 'opacity-40']"
                            :style="{ left: xFor(timeToMin(slot.start_time)) + 'px', width: wFor(timeToMin(slot.end_time) - timeToMin(slot.start_time)) + 'px' }"
                        >
                            <span
                                v-if="slot.kind === 'fixed'"
                                class="pointer-events-none absolute inset-0 flex items-center justify-center text-[11px] font-medium text-muted-foreground"
                            >
                                {{ slot.name }}
                            </span>
                        </div>
                        <div
                            v-for="t in hourTicks"
                            :key="'g' + t"
                            class="pointer-events-none absolute top-0 bottom-0 w-px bg-border/60"
                            :style="{ left: xFor(t) + 'px' }"
                        />

                        <!-- Activity cards -->
                        <div
                            v-for="item in row.items"
                            :key="item.entry.id"
                            data-card
                            class="absolute flex cursor-grab flex-col overflow-hidden rounded-md border-l-4 border shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
                            :class="[colorStyle(cardColor(item.entry)).cell, isSelected(item.entry) ? 'ring-2 ring-primary' : '']"
                            :style="{
                                left: xFor(timeToMin(item.entry.start_time)) + 'px',
                                width: Math.max(24, wFor(item.entry.duration) - 2) + 'px',
                                top: PAD + item.lane * LANE_H + 'px',
                                height: LANE_H - CARD_GAP + 'px',
                            }"
                            :title="cardTooltip(item.entry)"
                            @pointerdown="beginDrag($event, item.entry, row.day)"
                            @contextmenu="openCardMenu($event, row.day, item.entry)"
                            @click.stop
                        >
                            <div class="flex items-start justify-between gap-1 px-1.5 pt-1">
                                <span class="truncate text-xs font-semibold leading-tight">
                                    {{ item.entry.title || item.entry.activity?.name || 'Aktivita' }}
                                </span>
                                <span
                                    data-nodrag
                                    class="mt-0.5 flex size-4 shrink-0 cursor-pointer items-center justify-center rounded-full border"
                                    :class="item.entry.is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-muted-foreground/40 bg-background/50'"
                                    title="Hotovo / rozpracované"
                                    @click.stop="emit('toggleDone', item.entry)"
                                    @pointerdown.stop
                                >
                                    <Check v-if="item.entry.is_done" class="size-3" />
                                </span>
                            </div>
                            <div class="flex items-center gap-1 truncate px-1.5 text-[10px] text-muted-foreground">
                                <span class="truncate">
                                    {{ item.entry.start_time }} · {{ durationLabel(item.entry.duration) }}
                                </span>
                                <StickyNote
                                    v-if="item.entry.notes"
                                    class="size-3 shrink-0 text-amber-600 dark:text-amber-400"
                                />
                                <span
                                    v-if="item.entry.avg_rating != null"
                                    class="ml-auto flex shrink-0 items-center gap-0.5 font-medium text-amber-500"
                                    :title="`${item.entry.rating_count} hodnotení`"
                                >
                                    <Star class="size-2.5 fill-amber-400 text-amber-400" />{{ item.entry.avg_rating }}
                                </span>
                            </div>
                            <span
                                v-if="item.entry.responsible"
                                class="mx-1.5 mt-1 mb-1 flex w-fit max-w-[calc(100%-0.75rem)] items-center gap-0.5 truncate rounded bg-background/70 px-1 py-px text-[10px] font-medium"
                            >
                                <User class="size-2.5 shrink-0" />
                                <span class="truncate">{{ item.entry.responsible }}</span>
                            </span>

                            <div
                                data-resize
                                class="absolute top-0 right-0 bottom-0 w-2 cursor-ew-resize hover:bg-foreground/10"
                                title="Potiahni pre zmenu dĺžky"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context menu (fixed overlay; stays inside the single root element) -->
        <div v-if="contextMenu" class="fixed inset-0 z-40" @click="closeMenu" @contextmenu.prevent="closeMenu" />
        <div
            v-if="contextMenu"
            class="fixed z-50 min-w-44 rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
            :style="{ left: contextMenu.x + 'px', top: contextMenu.y + 'px' }"
        >
                <template v-if="contextMenu.entry">
                    <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent" @click="menuEdit">
                        <Pencil class="size-4" /> Upraviť
                    </button>
                    <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent" @click="menuToggleSelect">
                        <CheckSquare class="size-4" />
                        {{ isSelected(contextMenu.entry) ? 'Odznačiť' : 'Označiť' }}
                    </button>
                    <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent" @click="menuToggleDone">
                        <Check class="size-4" />
                        {{ contextMenu.entry.is_done ? 'Označiť ako rozpracované' : 'Označiť ako hotové' }}
                    </button>
                    <div class="my-1 h-px bg-border" />
                    <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-destructive hover:bg-accent" @click="menuDelete">
                        <Trash2 class="size-4" />
                        {{
                            selectedIds.size > 1 && selectedIds.has(contextMenu.entry.id)
                                ? `Zmazať vybrané (${selectedIds.size})`
                                : 'Zmazať'
                        }}
                    </button>
                </template>
                <template v-else>
                    <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent" @click="menuAdd">
                        <Plus class="size-4" /> Pridať aktivitu o {{ minToTime(contextMenu.startMin) }}
                    </button>
                </template>
        </div>
    </div>
</template>
