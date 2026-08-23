<script setup lang="ts">
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    CalendarHeart,
    Copy,
    History,
    Lock,
    LockOpen,
    MapPin,
    Pencil,
    Star,
    Trophy,
} from '@lucide/vue';
import { computed, ref, watch, watchEffect } from 'vue';
import DayDialog from '@/components/camp/DayDialog.vue';
import DayReviewDialog from '@/components/camp/DayReviewDialog.vue';
import EntryDialog from '@/components/camp/EntryDialog.vue';
import PlanVersionsDialog from '@/components/camp/PlanVersionsDialog.vue';
import TimetableTimeline from '@/components/camp/TimetableTimeline.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n';
import { colorStyle } from '@/lib/campColors';
import { campIcon } from '@/lib/campIcons';
import { index as campsIndex } from '@/routes/camps';
import {
    duplicate as duplicateCamp,
    fillNameDays,
    leaderboard,
    lock as lockCamp,
    unlock as unlockCamp,
} from '@/routes/camps';
import {
    bulkDestroy,
    bulkUpdate,
    setStatus as setEntryStatus,
} from '@/routes/entries';
import {
    destroy as destroyOverride,
    upsert as upsertOverride,
} from '@/routes/slotOverrides';
import type {
    Activity,
    ActivityCategory,
    ActivityLibraryRef,
    Camp,
    CampDay,
    CampLeader,
    EffectiveSlot,
    EntryStatus,
    FeedbackQuestion,
    PlanVersion,
    ProgramEntry,
    SlotOverridePatch,
    TimeSlot,
} from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    camp: Camp;
    slots: TimeSlot[];
    days: CampDay[];
    membersCount: number;
    leaders: CampLeader[];
    feedbackQuestions: FeedbackQuestion[];
    myGroupIds: number[];
    activities: Activity[];
    categories: ActivityCategory[];
    library: ActivityLibraryRef | null;
    planVersions: PlanVersion[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.camps'), href: campsIndex().url },
            { title: props.camp.name, href: '#' },
        ],
    });
});

// Local, mutable copy of the days so drag/resize feels instant; re-synced whenever
// the server sends fresh props.
const localDays = ref<CampDay[]>(clone(props.days));
watch(
    () => props.days,
    (d) => (localDays.value = clone(d)),
);
function clone<T>(value: T): T {
    return JSON.parse(JSON.stringify(value));
}

// --- Entry (activity) dialog ---
const entryOpen = ref(false);
const selectedDay = ref<CampDay | null>(null);
const selectedEntry = ref<ProgramEntry | null>(null);
const addStartMin = ref<number | undefined>(undefined);

function onEdit(day: CampDay, entry: ProgramEntry) {
    selectedDay.value = day;
    selectedEntry.value = entry;
    addStartMin.value = undefined;
    entryOpen.value = true;
}
function onAdd(day: CampDay, startMin: number) {
    selectedDay.value = day;
    selectedEntry.value = null;
    addStartMin.value = startMin;
    entryOpen.value = true;
}
// Someone else may have locked the schedule since this page loaded — reload so
// the banner shows up instead of leaving a silent failure on screen.
const scheduleWrite = { preserveScroll: true, onError: () => router.reload() };

function onSetStatus(entry: ProgramEntry, status: EntryStatus) {
    entry.status = status; // optimistic
    router.put(setEntryStatus(entry.id).url, { status }, scheduleWrite);
}
function onCommit(entries: ProgramEntry[]) {
    router.put(
        bulkUpdate().url,
        {
            entries: entries.map((e) => ({
                id: e.id,
                start_time: e.start_time,
                duration: e.duration,
            })),
        },
        scheduleWrite,
    );
}
function onBulkDelete(ids: number[]) {
    router.post(bulkDestroy().url, { ids }, scheduleWrite);
}
function onBulkResponsible(ids: number[], responsible: string) {
    router.put(
        bulkUpdate().url,
        { entries: ids.map((id) => ({ id, responsible })) },
        scheduleWrite,
    );
}

// --- Per-day block overrides (the timeline already updated localDays) ---
function onSlotOverride(
    day: CampDay,
    slot: EffectiveSlot,
    patch: SlotOverridePatch,
) {
    router.put(
        upsertOverride({ day: day.id, slot: slot.id }).url,
        patch,
        scheduleWrite,
    );
}
function onSlotOverrideReset(day: CampDay, slot: EffectiveSlot) {
    router.delete(
        destroyOverride({ day: day.id, slot: slot.id }).url,
        scheduleWrite,
    );
}

// --- Editing on touch devices is opt-in, and the owner can freeze it for all ---
const isCoarsePointer =
    typeof window !== 'undefined' &&
    window.matchMedia('(pointer: coarse)').matches;
const mobileEdit = ref(false);
const canEditSchedule = computed(
    () => !props.camp.schedule_locked && (!isCoarsePointer || mobileEdit.value),
);

function toggleLock() {
    if (props.camp.schedule_locked) {
        router.delete(unlockCamp(props.camp.id).url, { preserveScroll: true });

        return;
    }

    if (!confirm(t('camps.show.confirmLock'))) {
        return;
    }

    router.post(lockCamp(props.camp.id).url, {}, { preserveScroll: true });
}

// --- Day dialog ---
const dayOpen = ref(false);
const dayForDialog = ref<CampDay | null>(null);
function onEditDay(day: CampDay) {
    dayForDialog.value = day;
    dayOpen.value = true;
}

// --- Day review ---
const reviewOpen = ref(false);
const reviewDay = ref<CampDay | null>(null);
function onReview(day: CampDay) {
    reviewDay.value = day;
    reviewOpen.value = true;
}
// Suggest reviewing the latest day that has already happened but isn't reviewed yet.
const todayIso = new Date().toISOString().slice(0, 10);
const dayToReview = computed(() =>
    [...localDays.value]
        .reverse()
        .find(
            (d) => d.date <= todayIso && d.entries.length > 0 && !d.my_review,
        ),
);
function capitalize(v: string): string {
    return v.charAt(0).toUpperCase() + v.slice(1);
}

// Nudge a group's leaders when a scoring activity that already happened is
// still missing their group's result.
const entryNeedingPoints = computed(() => {
    if (!props.myGroupIds.length) {
        return null;
    }

    for (const day of localDays.value) {
        if (day.date > todayIso) {
            continue;
        }

        for (const entry of day.entries) {
            if (entry.points_mode === 'none') {
                continue;
            }

            const recorded = new Set(entry.points.map((p) => p.camp_group_id));

            if (props.myGroupIds.some((id) => !recorded.has(id))) {
                return entry;
            }
        }
    }

    return null;
});

// --- Other dialogs ---
const versionsOpen = ref(false);

// --- Fill name days from the Slovak calendar ---
function fillNames() {
    router.post(fillNameDays(props.camp.id).url, {}, { preserveScroll: true });
}

// --- Duplicate camp ---
const duplicateOpen = ref(false);
const duplicateForm = useForm({
    name: '',
    year: props.camp.year + 1,
    start_date: '',
    end_date: '',
    copy_program: true,
});
function openDuplicate() {
    duplicateForm.clearErrors();
    duplicateForm.defaults({
        name: t('camps.show.copyName', { name: props.camp.name }),
        year: props.camp.year + 1,
        start_date: '',
        end_date: '',
        copy_program: true,
    });
    duplicateForm.reset();
    duplicateOpen.value = true;
}
function submitDuplicate() {
    duplicateForm.post(duplicateCamp(props.camp.id).url, {
        onSuccess: () => (duplicateOpen.value = false),
    });
}
</script>

<template>
    <Head :title="camp.name" />

    <div class="flex h-full flex-1 flex-col gap-3 p-2 sm:gap-4 sm:p-4">
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl"
                    :class="colorStyle(camp.color).chip"
                >
                    <component :is="campIcon(camp.icon)" class="size-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ camp.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ camp.year }} ·
                        {{ t('camps.index.days', { count: localDays.length }) }}
                        ·
                        {{
                            t('camps.show.leaderCount', { count: membersCount })
                        }}
                        <span v-if="camp.location">
                            · <MapPin class="inline size-3.5" />
                            {{ camp.location }}</span
                        >
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :title="t('camps.show.fillNameDaysHint')"
                    @click="fillNames"
                >
                    <CalendarHeart /> {{ t('camps.show.fillNameDays') }}
                </Button>
                <Button variant="outline" size="sm" @click="openDuplicate">
                    <Copy /> {{ t('common.duplicate') }}
                </Button>
                <Button
                    v-if="camp.is_owner"
                    variant="outline"
                    size="sm"
                    @click="versionsOpen = true"
                >
                    <History /> {{ t('versions.plan.title') }}
                </Button>
                <Button
                    v-if="camp.is_owner"
                    variant="outline"
                    size="sm"
                    :class="
                        camp.schedule_locked
                            ? 'border-amber-400 text-amber-700 dark:text-amber-300'
                            : ''
                    "
                    @click="toggleLock"
                >
                    <component :is="camp.schedule_locked ? LockOpen : Lock" />
                    {{
                        camp.schedule_locked
                            ? t('camps.show.unlock')
                            : t('camps.show.lock')
                    }}
                </Button>
            </div>
        </div>

        <!-- Review prompt -->
        <div
            v-if="dayToReview"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-950/30"
        >
            <Star class="size-5 fill-amber-400 text-amber-500" />
            <p class="text-sm">
                <strong
                    >{{ capitalize(dayToReview.weekday) }}
                    {{ dayToReview.label }}</strong
                >
                {{ t('camps.show.needsReview') }}
            </p>
            <Button size="sm" class="ml-auto" @click="onReview(dayToReview)">{{
                t('camps.show.reviewDay')
            }}</Button>
        </div>

        <!-- Missing points -->
        <div
            v-if="entryNeedingPoints"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-950/30"
        >
            <Trophy class="size-5 text-amber-600 dark:text-amber-400" />
            <p class="text-sm">
                <strong>{{
                    entryNeedingPoints.title ??
                    entryNeedingPoints.activity?.name
                }}</strong>
                {{ t('camps.show.needsPoints') }}
            </p>
            <Button as-child size="sm" class="ml-auto">
                <Link :href="leaderboard(camp.id)">{{
                    t('camps.show.recordPoints')
                }}</Link>
            </Button>
        </div>

        <!-- Frozen schedule -->
        <div
            v-if="camp.schedule_locked"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-950/30"
        >
            <Lock class="size-5 text-amber-600 dark:text-amber-400" />
            <p class="text-sm">
                <strong>{{ t('camps.locked.title') }}</strong>
                {{ t('camps.show.lockedBody') }}
                <span v-if="camp.is_owner">{{
                    t('camps.show.lockedOwner')
                }}</span>
                <span v-else>{{ t('camps.show.lockedLeader') }}</span>
                {{ t('camps.show.lockedStillWorks') }}
            </p>
        </div>

        <!-- Legend + touch edit toggle -->
        <div class="flex flex-wrap items-center gap-3">
            <p v-if="isCoarsePointer" class="text-xs text-muted-foreground">
                {{ t('camps.show.touchHint') }}
                <template v-if="!camp.schedule_locked">
                    {{ t('camps.show.touchEditHint') }}
                </template>
            </p>
            <p v-else class="hidden text-xs text-muted-foreground sm:block">
                {{ t('camps.show.desktopHint') }}
            </p>
            <Button
                v-if="isCoarsePointer && !camp.schedule_locked"
                :variant="mobileEdit ? 'default' : 'outline'"
                size="sm"
                class="ml-auto"
                @click="mobileEdit = !mobileEdit"
            >
                <Pencil />
                {{ mobileEdit ? t('camps.show.editingOn') : t('common.edit') }}
            </Button>
        </div>

        <!-- Timetable -->
        <TimetableTimeline
            v-if="slots.length && localDays.length"
            :days="localDays"
            :slots="slots"
            :editable="canEditSchedule"
            @edit="onEdit"
            @add="onAdd"
            @edit-day="onEditDay"
            @review="onReview"
            @set-status="onSetStatus"
            @commit="onCommit"
            @bulk-delete="onBulkDelete"
            @bulk-responsible="onBulkResponsible"
            @slot-override="onSlotOverride"
            @slot-override-reset="onSlotOverrideReset"
        />
        <div
            v-else
            class="rounded-xl border border-dashed p-12 text-center text-muted-foreground"
        >
            <p v-if="!slots.length">{{ t('camps.show.noSlots') }}</p>
            <p v-else>{{ t('camps.show.noDays') }}</p>
        </div>
    </div>

    <!-- Dialogs -->
    <EntryDialog
        v-model:open="entryOpen"
        :day="selectedDay"
        :entry="selectedEntry"
        :activities="activities"
        :categories="categories"
        :library="library"
        :leaders="leaders"
        :start-min="addStartMin"
        :editable="!camp.schedule_locked"
    />
    <DayDialog v-model:open="dayOpen" :day="dayForDialog" />
    <DayReviewDialog
        v-model:open="reviewOpen"
        :day="reviewDay"
        :questions="feedbackQuestions"
    />
    <PlanVersionsDialog
        v-model:open="versionsOpen"
        :camp-id="camp.id"
        :versions="planVersions"
        :is-owner="camp.is_owner"
        :schedule-locked="camp.schedule_locked"
    />

    <!-- Duplicate -->
    <Dialog v-model:open="duplicateOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ t('camps.show.duplicateTitle') }}</DialogTitle>
                <DialogDescription>
                    {{ t('camps.show.duplicateBody') }}
                </DialogDescription>
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submitDuplicate">
                <div class="grid gap-2">
                    <Label for="dup-name">{{ t('common.name') }}</Label>
                    <Input
                        id="dup-name"
                        v-model="duplicateForm.name"
                        required
                    />
                    <InputError :message="duplicateForm.errors.name" />
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="dup-year">{{
                            t('camps.field.year')
                        }}</Label>
                        <Input
                            id="dup-year"
                            v-model="duplicateForm.year"
                            type="number"
                        />
                        <InputError :message="duplicateForm.errors.year" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="dup-start">{{
                            t('camps.field.start')
                        }}</Label>
                        <Input
                            id="dup-start"
                            v-model="duplicateForm.start_date"
                            type="date"
                            required
                        />
                        <InputError
                            :message="duplicateForm.errors.start_date"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="dup-end">{{ t('camps.field.end') }}</Label>
                        <Input
                            id="dup-end"
                            v-model="duplicateForm.end_date"
                            type="date"
                            required
                        />
                        <InputError :message="duplicateForm.errors.end_date" />
                    </div>
                </div>
                <label class="flex items-center gap-3 rounded-lg border p-3">
                    <Checkbox
                        :model-value="duplicateForm.copy_program"
                        @update:model-value="
                            duplicateForm.copy_program = $event === true
                        "
                    />
                    <span>
                        <span class="font-medium">{{
                            t('camps.show.copyProgram')
                        }}</span>
                        <span class="block text-sm text-muted-foreground">{{
                            t('camps.show.copyProgramHint')
                        }}</span>
                    </span>
                </label>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="duplicateOpen = false"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="duplicateForm.processing">
                        {{ t('common.duplicate') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
