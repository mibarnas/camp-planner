<script setup lang="ts">
import {
    Check,
    CheckSquare,
    Coffee,
    Eye,
    EyeOff,
    MapPin,
    Package,
    Pencil,
    Plus,
    RotateCcw,
    Star,
    StickyNote,
    Trash2,
    User,
    UserCheck,
    X,
    ZoomIn,
    ZoomOut,
} from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { colorStyle } from '@/lib/campColors';
import {
    assignLanes,
    containingSlot,
    dayBounds,
    durationLabel,
    effectiveSlots,
    minToTime,
    timeToMin,
} from '@/lib/timeline';
import type {
    CampDay,
    EffectiveSlot,
    EntryStatus,
    ProgramEntry,
    SlotOverride,
    SlotOverridePatch,
    TimeSlot,
} from '@/types/camp';

const props = withDefaults(
    defineProps<{ days: CampDay[]; slots: TimeSlot[]; editable?: boolean }>(),
    { editable: true },
);

const emit = defineEmits<{
    edit: [day: CampDay, entry: ProgramEntry];
    add: [day: CampDay, startMin: number];
    editDay: [day: CampDay];
    review: [day: CampDay];
    setStatus: [entry: ProgramEntry, status: EntryStatus];
    commit: [entries: ProgramEntry[]];
    bulkDelete: [ids: number[]];
    bulkResponsible: [ids: number[], responsible: string];
    slotOverride: [day: CampDay, slot: EffectiveSlot, patch: SlotOverridePatch];
    slotOverrideReset: [day: CampDay, slot: EffectiveSlot];
}>();

// --- Layout: px-per-minute stretches to fill the available width -------------
const LANE_H = 74;
// Height of the per-day strip of block handles above the activity cards. The
// blocks used to be draggable via their full-height background tint, which sat
// underneath the cards — in a full day there was barely any of it left to grab.
const BAR_H = 18;
const LABEL_W = 156;
const PAD = 6;
const CARD_GAP = 4;
const SNAP = 5; // minutes
const MIN_PX = 1.3;

// Below Tailwind's `sm`, the day label moves to a strip above each track — a
// 156px column would eat nearly half of a phone's width.
const isMobile = useMediaQuery('(max-width: 639px)');
const labelW = computed(() => (isMobile.value ? 0 : LABEL_W));

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
        const contentX = el.scrollLeft + (anchorClientX - rect.left) - labelW.value;
        const ratio = next / zoom.value;
        zoom.value = next;
        requestAnimationFrame(() => {
            el.scrollLeft = contentX * ratio - (anchorClientX - rect.left) + labelW.value;
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
    () => Math.max(MIN_PX, (containerW.value - labelW.value - 2) / totalMin.value) * zoom.value,
);
const trackWidth = computed(() => totalMin.value * pxPerMin.value);
// The mobile day strip spans the visible scrollport, but never wider than the row
// itself — an overflowing strip would add its own sliver of horizontal scroll.
const stripW = computed(() => Math.min(containerW.value, labelW.value + trackWidth.value));

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

// Each day gets the camp skeleton as it actually applies to it: blocks moved or
// hidden for that day only, the rest straight from the template.
const layout = computed(() =>
    props.days.map((day) => {
        const { items, lanes } = assignLanes(day.entries);
        const slots = effectiveSlots(props.slots, day);

        return {
            day,
            items,
            slots: slots.filter((s) => !s.hidden),
            hiddenSlots: slots.filter((s) => s.hidden),
            height: BAR_H + lanes * LANE_H + PAD * 2,
        };
    }),
);

const allEntries = computed(() => props.days.flatMap((d) => d.entries));

function cardColor(entry: ProgramEntry, slots: EffectiveSlot[]): string {
    // Simple blocks stay neutral so the real programme keeps the colour.
    if (entry.kind === 'simple') {
        return 'slate';
    }

    return (
        entry.activity?.color ??
        containingSlot(timeToMin(entry.start_time), slots)?.color ??
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

// --- Progress state ------------------------------------------------------------
const STATUS_LABELS: Record<EntryStatus, string> = {
    todo: 'treba doriešiť',
    none: 'rozpracované',
    done: 'hotové',
};
const OTHER_STATUSES: EntryStatus[] = ['done', 'none', 'todo'];

function statusTitle(entry: ProgramEntry): string {
    return `Stav: ${STATUS_LABELS[entry.status]}${props.editable ? ' — klikni pre prepnutie hotové/rozpracované' : ''}`;
}
// A quick tap only flips between done and in-progress; "treba doriešiť" is
// deliberate enough to belong in the menu or the dialog.
function cycleStatus(entry: ProgramEntry) {
    emit('setStatus', entry, entry.status === 'done' ? 'none' : 'done');
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
    slot: EffectiveSlot | null;
    startMin: number;
};
const contextMenu = ref<ContextMenuState | null>(null);
const menuEl = ref<HTMLElement | null>(null);

// Open at the pointer, then pull back inside the viewport — near the right or
// bottom edge of a phone the menu would otherwise render off-screen.
watch(contextMenu, async (menu) => {
    if (!menu) {
return;
}

    await nextTick();
    const rect = menuEl.value?.getBoundingClientRect();

    if (!rect) {
return;
}

    const x = Math.max(8, Math.min(menu.x, window.innerWidth - rect.width - 8));
    const y = Math.max(8, Math.min(menu.y, window.innerHeight - rect.height - 8));

    if (x !== menu.x || y !== menu.y) {
        contextMenu.value = { ...menu, x, y };
    }
});

function openCardMenu(e: MouseEvent, day: CampDay, entry: ProgramEntry) {
    if (!props.editable) {
return;
}

    e.preventDefault();
    e.stopPropagation();
    contextMenu.value = { x: e.clientX, y: e.clientY, day, entry, slot: null, startMin: 0 };
}
function openSlotMenu(e: MouseEvent, day: CampDay, slot: EffectiveSlot) {
    if (!props.editable) {
return;
}

    e.preventDefault();
    e.stopPropagation();
    contextMenu.value = { x: e.clientX, y: e.clientY, day, entry: null, slot, startMin: 0 };
}
function openTrackMenu(e: MouseEvent, day: CampDay) {
    if (!props.editable) {
return;
}

    if ((e.target as HTMLElement).closest('[data-card]')) {
return;
}

    e.preventDefault();
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    const min = snap(bounds.value.start + (e.clientX - rect.left) / pxPerMin.value);
    contextMenu.value = { x: e.clientX, y: e.clientY, day, entry: null, slot: null, startMin: min };
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
function menuSetStatus(status: EntryStatus) {
    const m = contextMenu.value;

    if (m?.entry) {
emit('setStatus', m.entry, status);
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
function menuSlotHidden(isHidden: boolean) {
    const m = contextMenu.value;

    if (m?.slot) {
        setLocalOverride(m.day, m.slot.id, { is_hidden: isHidden });
        emit('slotOverride', m.day, m.slot, { is_hidden: isHidden });
    }

    closeMenu();
}
function menuSlotReset() {
    const m = contextMenu.value;

    if (m?.slot) {
        removeLocalOverride(m.day, m.slot.id);
        emit('slotOverrideReset', m.day, m.slot);
    }

    closeMenu();
}

// --- Per-day block overrides ----------------------------------------------------
// The day list is the page's own mutable copy, so the drag can write straight
// into it for instant feedback; the server call follows on pointerup.
function findOverride(day: CampDay, slotId: number): SlotOverride | undefined {
    return day.slot_overrides?.find((o) => o.time_slot_id === slotId);
}

function setLocalOverride(day: CampDay, slotId: number, patch: SlotOverridePatch) {
    if (!day.slot_overrides) {
        day.slot_overrides = [];
    }

    const existing = findOverride(day, slotId);

    if (existing) {
        Object.assign(existing, patch);

        return;
    }

    day.slot_overrides.push({
        time_slot_id: slotId,
        start_time: null,
        end_time: null,
        is_hidden: false,
        ...patch,
    });
}

function removeLocalOverride(day: CampDay, slotId: number) {
    const i = day.slot_overrides?.findIndex((o) => o.time_slot_id === slotId) ?? -1;

    if (i >= 0) {
        day.slot_overrides.splice(i, 1);
    }
}

function restoreLocalOverride(day: CampDay, slotId: number, original: SlotOverride | null) {
    removeLocalOverride(day, slotId);

    if (original) {
        day.slot_overrides.push({ ...original });
    }
}

function slotTooltip(slot: EffectiveSlot): string {
    const lines = [slot.name, `${slot.start_time}–${slot.end_time}`];

    if (slot.hidden) {
        lines.push('V tomto dni skrytý — klikni pre zobrazenie');
    } else if (props.editable) {
        lines.push('Potiahni pre presun len v tomto dni, klikni pre menu');
    }

    if (slot.overridden) {
        const template = props.slots.find((s) => s.id === slot.id);
        lines.push(
            template
                ? `Upravené pre tento deň (šablóna ${template.start_time}–${template.end_time})`
                : 'Upravené pre tento deň',
        );
    }

    return lines.join('\n');
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

type SlotDragState = {
    day: CampDay;
    slot: EffectiveSlot;
    mode: 'move' | 'resize';
    startX: number;
    origStart: number;
    origEnd: number;
    curStart: number;
    curEnd: number;
    original: SlotOverride | null;
    moved: boolean;
};
const slotDrag = ref<SlotDragState | null>(null);

function snap(min: number): number {
    return Math.round(min / SNAP) * SNAP;
}

function capturePointer(e: PointerEvent) {
    try {
        (e.currentTarget as HTMLElement | null)?.setPointerCapture(e.pointerId);
    } catch {
        // Pointer capture is a nicety; dragging still works without it.
    }
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

    if (!props.editable) {
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

    capturePointer(e);

    const detach = () => {
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onUp);
        window.removeEventListener('pointercancel', onCancel);
    };

    const onUp = () => {
        const d = drag.value;
        detach();

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

    // The browser took the gesture away (scroll, an incoming call): put the
    // card back where it started rather than saving a half-finished move.
    const onCancel = () => {
        const d = drag.value;
        detach();

        if (!d) {
return;
}

        for (const g of d.group) {
            g.entry.start_time = minToTime(g.origStart);
        }

        d.entry.duration = d.origDur;
        drag.value = null;
    };

    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onUp);
    window.addEventListener('pointercancel', onCancel);
}

function onSlotPointerMove(e: PointerEvent) {
    const d = slotDrag.value;

    if (!d) {
return;
}

    // A hidden block has nowhere to move to — its handle is click-only.
    if (d.slot.hidden) {
return;
}

    const deltaMin = snap((e.clientX - d.startX) / pxPerMin.value);

    // The threshold only decides when a drag has begun. Once it has, every
    // delta is applied — including back to zero, so a drag can be taken back
    // without letting go.
    if (!d.moved) {
        if (Math.abs(deltaMin) < SNAP) {
return;
}

        d.moved = true;
    }

    if (d.mode === 'move') {
        const span = d.origEnd - d.origStart;
        const clamped = Math.max(
            bounds.value.start,
            Math.min(bounds.value.end - span, d.origStart + deltaMin),
        );
        d.curStart = clamped;
        d.curEnd = clamped + span;
    } else {
        d.curEnd = Math.max(
            d.origStart + SNAP,
            Math.min(bounds.value.end, d.origEnd + deltaMin),
        );
    }

    setLocalOverride(d.day, d.slot.id, {
        start_time: minToTime(d.curStart),
        end_time: minToTime(d.curEnd),
    });
}

function beginSlotDrag(e: PointerEvent, slot: EffectiveSlot, day: CampDay) {
    if (e.button !== 0 || !props.editable) {
return;
}

    const mode = (e.target as HTMLElement).closest('[data-slotresize]') ? 'resize' : 'move';
    const origStart = timeToMin(slot.start_time);
    const origEnd = timeToMin(slot.end_time);
    const existing = findOverride(day, slot.id);

    slotDrag.value = {
        day,
        slot,
        mode,
        startX: e.clientX,
        origStart,
        origEnd,
        curStart: origStart,
        curEnd: origEnd,
        original: existing ? { ...existing } : null,
        moved: false,
    };

    capturePointer(e);

    const detach = () => {
        window.removeEventListener('pointermove', onSlotPointerMove);
        window.removeEventListener('pointerup', onUp);
        window.removeEventListener('pointercancel', onCancel);
    };

    const onUp = (ev: PointerEvent) => {
        const d = slotDrag.value;
        detach();
        slotDrag.value = null;

        if (!d) {
return;
}

        // A handle that wasn't dragged opens its menu — the only way to hide,
        // restore or reset a block with a touch screen and no right mouse button.
        if (!d.moved) {
            contextMenu.value = {
                x: ev.clientX,
                y: ev.clientY,
                day: d.day,
                entry: null,
                slot: d.slot,
                startMin: 0,
            };

            return;
        }

        emit('slotOverride', d.day, d.slot, {
            start_time: minToTime(d.curStart),
            end_time: minToTime(d.curEnd),
        });
    };

    const onCancel = () => {
        const d = slotDrag.value;
        detach();
        slotDrag.value = null;

        if (d) {
            restoreLocalOverride(d.day, d.slot.id, d.original);
        }
    };

    window.addEventListener('pointermove', onSlotPointerMove);
    window.addEventListener('pointerup', onUp);
    window.addEventListener('pointercancel', onCancel);
}

// Read-only mode still opens the dialog so details stay reachable; when editing
// is on, the pointerup path already emits `edit` for a click that didn't move.
function onCardClick(day: CampDay, entry: ProgramEntry) {
    if (!props.editable) {
        emit('edit', day, entry);
    }
}

// Click on empty track: clear selection first; if nothing selected, add here.
function onTrackClick(e: MouseEvent, day: CampDay) {
    if (drag.value || slotDrag.value || !props.editable) {
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
            <div :style="{ minWidth: labelW + trackWidth + 'px' }">
                <!-- Header: hour ruler + block guides (the camp-wide template) -->
                <div class="sticky top-0 z-30 flex border-b bg-muted/80 backdrop-blur">
                    <div
                        v-if="!isMobile"
                        class="sticky left-0 z-10 flex shrink-0 items-end bg-muted/80 p-2 text-xs font-medium text-muted-foreground backdrop-blur"
                        :style="{ width: LABEL_W + 'px' }"
                    >
                        Deň / Čas
                    </div>
                    <div class="relative h-10 sm:h-[54px]" :style="{ width: trackWidth + 'px' }">
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
                            class="absolute bottom-1 hidden h-8 flex-col justify-center overflow-hidden rounded border px-1.5 sm:flex"
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
                    class="border-b last:border-b-0"
                    :class="row.day.is_trip ? 'bg-fuchsia-50/60 dark:bg-fuchsia-950/20' : ''"
                >
                    <!-- Mobile: the day label sits above its track, pinned to the
                         viewport so it stays put while the track scrolls sideways. -->
                    <div
                        v-if="isMobile"
                        class="sticky left-0 z-10 border-b p-2"
                        :class="row.day.is_trip ? 'bg-fuchsia-50 dark:bg-fuchsia-950/30' : 'bg-card'"
                        :style="{ width: stripW + 'px' }"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="min-w-0 truncate font-semibold leading-tight">
                                {{ capitalize(row.day.weekday) }}
                                <span class="font-normal text-muted-foreground">{{ row.day.label }}</span>
                                <span
                                    v-if="row.day.review_summary.avg != null"
                                    class="ml-1 inline-flex items-center gap-0.5 align-middle text-xs font-normal text-amber-500"
                                    :title="`Priemer ${row.day.review_summary.avg} · ${row.day.review_summary.reviewers} hodnotení`"
                                >
                                    <Star class="size-3 fill-amber-400 text-amber-400" />{{ row.day.review_summary.avg }}
                                </span>
                            </p>
                            <div class="flex shrink-0 gap-1">
                                <button
                                    class="flex size-10 items-center justify-center rounded-md hover:bg-accent"
                                    :class="row.day.my_review ? 'text-amber-500' : 'text-muted-foreground'"
                                    title="Zhodnotiť deň"
                                    @click="emit('review', row.day)"
                                >
                                    <Star class="size-4" :class="row.day.my_review ? 'fill-amber-400' : ''" />
                                </button>
                                <button
                                    class="flex size-10 items-center justify-center rounded-md text-muted-foreground hover:bg-accent"
                                    title="Pridať aktivitu"
                                    @click="emit('add', row.day, bounds.start)"
                                >
                                    <Plus class="size-4" />
                                </button>
                                <button
                                    class="flex size-10 items-center justify-center rounded-md text-muted-foreground hover:bg-accent"
                                    title="Upraviť deň"
                                    @click="emit('editDay', row.day)"
                                >
                                    <Pencil class="size-4" />
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="row.day.is_trip || row.day.name_days || row.day.birthdays || row.day.materials"
                            class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] leading-tight"
                        >
                            <span
                                v-if="row.day.is_trip"
                                class="flex items-center gap-1 rounded bg-fuchsia-100 px-1.5 py-0.5 text-fuchsia-800 dark:bg-fuchsia-900/50 dark:text-fuchsia-200"
                            >
                                <MapPin class="size-3 shrink-0" />{{ row.day.trip_name || 'Výlet' }}
                            </span>
                            <span v-if="row.day.name_days" class="text-muted-foreground">
                                <span class="font-medium">Meniny:</span> {{ row.day.name_days }}
                            </span>
                            <span v-if="row.day.birthdays" class="text-muted-foreground">🎂 {{ row.day.birthdays }}</span>
                            <span
                                v-if="row.day.materials"
                                class="flex min-w-0 items-center gap-1 text-amber-700 dark:text-amber-400"
                            >
                                <Package class="size-3 shrink-0" />
                                <span class="line-clamp-1">{{ row.day.materials }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="flex">
                        <div
                            v-if="!isMobile"
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
                                <div class="flex gap-0.5 opacity-0 transition group-hover/day:opacity-100 pointer-coarse:opacity-100">
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
                            <!-- Block tint: background only, so it never competes with
                                 the cards or with click-to-add. -->
                            <div
                                v-for="slot in row.slots"
                                :key="'bg' + slot.id"
                                class="pointer-events-none absolute bottom-0 border-l"
                                :class="[
                                    colorStyle(slot.color).cell,
                                    slot.kind === 'fixed' ? 'opacity-90' : 'opacity-40',
                                    slot.overridden ? 'border-solid' : 'border-dashed',
                                ]"
                                :style="{
                                    top: BAR_H + 'px',
                                    left: xFor(timeToMin(slot.start_time)) + 'px',
                                    width: wFor(timeToMin(slot.end_time) - timeToMin(slot.start_time)) + 'px',
                                }"
                            >
                                <span
                                    v-if="slot.kind === 'fixed'"
                                    class="absolute inset-0 flex items-center justify-center text-[11px] font-medium text-muted-foreground"
                                >
                                    {{ slot.name }}
                                </span>
                            </div>

                            <!-- Handles for this day's blocks: always on top, never covered
                                 by an activity. Hidden blocks stay here as a dashed ghost so
                                 they can be brought back. -->
                            <!-- Stays above the activity cards but below the sticky day
                                 column (z-10), so it scrolls under it like everything else. -->
                            <div
                                data-slotbar
                                class="absolute top-0 right-0 left-0 z-[5]"
                                :style="{ height: BAR_H + 'px' }"
                                @click.stop
                            >
                                <div
                                    v-for="slot in [...row.slots, ...row.hiddenSlots]"
                                    :key="'chip' + slot.id"
                                    class="absolute top-0.5 flex items-center overflow-hidden rounded-sm border px-1"
                                    :class="[
                                        colorStyle(slot.color).cell,
                                        slot.hidden ? 'border-dashed opacity-50' : '',
                                        slot.overridden ? 'ring-1 ring-foreground/30' : '',
                                        editable ? (slot.hidden ? 'cursor-pointer' : 'cursor-grab active:cursor-grabbing') : 'cursor-default',
                                    ]"
                                    :style="{
                                        left: xFor(timeToMin(slot.start_time)) + 'px',
                                        width: Math.max(10, wFor(timeToMin(slot.end_time) - timeToMin(slot.start_time)) - 2) + 'px',
                                        height: BAR_H - 4 + 'px',
                                        touchAction: editable ? 'none' : 'auto',
                                    }"
                                    :title="slotTooltip(slot)"
                                    @pointerdown="beginSlotDrag($event, slot, row.day)"
                                    @contextmenu="openSlotMenu($event, row.day, slot)"
                                >
                                    <EyeOff v-if="slot.hidden" class="mr-0.5 size-2.5 shrink-0" />
                                    <span class="truncate text-[10px] leading-none font-medium">{{ slot.name }}</span>
                                    <div
                                        v-if="editable && !slot.hidden"
                                        data-slotresize
                                        class="absolute top-0 right-0 bottom-0 w-1.5 cursor-ew-resize hover:bg-foreground/20"
                                        title="Potiahni pre zmenu dĺžky bloku (len tento deň)"
                                    />
                                </div>
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
                                class="absolute flex flex-col overflow-hidden rounded-md border shadow-sm transition-shadow hover:shadow-md"
                                :class="[
                                    colorStyle(cardColor(item.entry, row.slots)).cell,
                                    item.entry.kind === 'simple' ? 'border-l-2 border-dashed opacity-80' : 'border-l-4',
                                    editable ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer',
                                    isSelected(item.entry)
                                        ? 'ring-2 ring-primary'
                                        : item.entry.status === 'todo'
                                          ? 'ring-1 ring-amber-500/70'
                                          : '',
                                ]"
                                :style="{
                                    left: xFor(timeToMin(item.entry.start_time)) + 'px',
                                    width: Math.max(24, wFor(item.entry.duration) - 2) + 'px',
                                    top: BAR_H + PAD + item.lane * LANE_H + 'px',
                                    height: LANE_H - CARD_GAP + 'px',
                                    touchAction: editable ? 'none' : 'auto',
                                }"
                                :title="cardTooltip(item.entry)"
                                @pointerdown="beginDrag($event, item.entry, row.day)"
                                @contextmenu="openCardMenu($event, row.day, item.entry)"
                                @click.stop="onCardClick(row.day, item.entry)"
                            >
                                <div class="flex items-start justify-between gap-1 px-1.5 pt-1">
                                    <span
                                        class="flex min-w-0 items-center gap-1 truncate text-xs leading-tight"
                                        :class="item.entry.kind === 'simple' ? 'font-medium text-muted-foreground' : 'font-semibold'"
                                    >
                                        <Coffee v-if="item.entry.kind === 'simple'" class="size-3 shrink-0" />
                                        <span class="truncate">
                                            {{ item.entry.title || item.entry.activity?.name || 'Aktivita' }}
                                        </span>
                                    </span>
                                    <span
                                        data-nodrag
                                        class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border"
                                        :class="[
                                            editable ? 'cursor-pointer' : '',
                                            item.entry.status === 'done'
                                                ? 'border-emerald-500 bg-emerald-500 text-white'
                                                : item.entry.status === 'todo'
                                                  ? 'border-amber-500 bg-amber-500 text-white'
                                                  : 'border-muted-foreground/40 bg-background/50',
                                        ]"
                                        :title="statusTitle(item.entry)"
                                        @click.stop="editable && cycleStatus(item.entry)"
                                        @pointerdown.stop
                                    >
                                        <Check v-if="item.entry.status === 'done'" class="size-3" />
                                        <span v-else-if="item.entry.status === 'todo'" class="text-[9px] leading-none font-bold">!</span>
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
                                    v-if="editable"
                                    data-resize
                                    class="absolute top-0 right-0 bottom-0 w-2 cursor-ew-resize hover:bg-foreground/10"
                                    title="Potiahni pre zmenu dĺžky"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context menu (fixed overlay; stays inside the single root element) -->
        <div v-if="contextMenu" class="fixed inset-0 z-40" @click="closeMenu" @contextmenu.prevent="closeMenu" />
        <div
            v-if="contextMenu"
            ref="menuEl"
            class="fixed z-50 min-w-44 rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
            :style="{ left: contextMenu.x + 'px', top: contextMenu.y + 'px' }"
        >
                <template v-if="contextMenu.entry">
                    <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent pointer-coarse:py-2.5" @click="menuEdit">
                        <Pencil class="size-4" /> Upraviť
                    </button>
                    <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent pointer-coarse:py-2.5" @click="menuToggleSelect">
                        <CheckSquare class="size-4" />
                        {{ isSelected(contextMenu.entry) ? 'Odznačiť' : 'Označiť' }}
                    </button>
                    <div class="my-1 h-px bg-border" />
                    <button
                        v-for="s in OTHER_STATUSES.filter((s) => s !== contextMenu!.entry!.status)"
                        :key="s"
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent pointer-coarse:py-2.5"
                        @click="menuSetStatus(s)"
                    >
                        <Check class="size-4" /> Označiť ako {{ STATUS_LABELS[s] }}
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
                <template v-else-if="contextMenu.slot">
                    <p class="px-2 py-1.5 text-xs font-medium text-muted-foreground">
                        {{ contextMenu.slot.name }} · {{ contextMenu.slot.start_time }}–{{ contextMenu.slot.end_time }}
                    </p>
                    <button
                        v-if="contextMenu.slot.hidden"
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent pointer-coarse:py-2.5"
                        @click="menuSlotHidden(false)"
                    >
                        <Eye class="size-4" /> Zobraziť v tomto dni
                    </button>
                    <button
                        v-else
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent pointer-coarse:py-2.5"
                        @click="menuSlotHidden(true)"
                    >
                        <EyeOff class="size-4" /> Skryť v tomto dni
                    </button>
                    <button
                        v-if="contextMenu.slot.overridden"
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent pointer-coarse:py-2.5"
                        @click="menuSlotReset"
                    >
                        <RotateCcw class="size-4" /> Obnoviť podľa šablóny
                    </button>
                </template>
                <template v-else>
                    <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent pointer-coarse:py-2.5" @click="menuAdd">
                        <Plus class="size-4" /> Pridať aktivitu o {{ minToTime(contextMenu.startMin) }}
                    </button>
                </template>
        </div>
    </div>
</template>
