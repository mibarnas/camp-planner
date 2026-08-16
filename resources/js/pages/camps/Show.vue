<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    CalendarHeart,
    Columns3,
    Copy,
    History,
    Lock,
    LockOpen,
    MapPin,
    Pencil,
    Settings,
    Sparkles,
    Star,
    Trash2,
    Users,
} from '@lucide/vue';
import { computed, ref, watch, watchEffect } from 'vue';
import CampAppearanceFields from '@/components/camp/CampAppearanceFields.vue';
import DayDialog from '@/components/camp/DayDialog.vue';
import DayReviewDialog from '@/components/camp/DayReviewDialog.vue';
import EntryDialog from '@/components/camp/EntryDialog.vue';
import MembersDialog from '@/components/camp/MembersDialog.vue';
import PlanVersionsDialog from '@/components/camp/PlanVersionsDialog.vue';
import SlotsDialog from '@/components/camp/SlotsDialog.vue';
import SummaryDialog from '@/components/camp/SummaryDialog.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { colorStyle } from '@/lib/campColors';
import { campIcon } from '@/lib/campIcons';
import { index as campsIndex } from '@/routes/camps';
import { destroy as destroyCamp, duplicate as duplicateCamp, fillNameDays, lock as lockCamp, unlock as unlockCamp, update as updateCamp } from '@/routes/camps';
import { bulkDestroy, bulkUpdate, setStatus as setEntryStatus } from '@/routes/entries';
import { destroy as destroyOverride, upsert as upsertOverride } from '@/routes/slotOverrides';
import type {
    Activity,
    ActivityCategory,
    ActivityLibraryRef,
    Camp,
    CampDay,
    CampInvitation,
    CampMember,
    EffectiveSlot,
    EntryStatus,
    PlanVersion,
    ProgramEntry,
    ShareLink,
    SlotOverridePatch,
    TimeSlot,
} from '@/types/camp';

const props = defineProps<{
    camp: Camp;
    slots: TimeSlot[];
    days: CampDay[];
    members: CampMember[];
    invitations: CampInvitation[];
    shareLink: ShareLink | null;
    activities: Activity[];
    categories: ActivityCategory[];
    library: ActivityLibraryRef | null;
    planVersions: PlanVersion[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tábory', href: campsIndex().url },
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
function onSlotOverride(day: CampDay, slot: EffectiveSlot, patch: SlotOverridePatch) {
    router.put(upsertOverride({ day: day.id, slot: slot.id }).url, patch, scheduleWrite);
}
function onSlotOverrideReset(day: CampDay, slot: EffectiveSlot) {
    router.delete(destroyOverride({ day: day.id, slot: slot.id }).url, scheduleWrite);
}

// --- Editing on touch devices is opt-in, and the owner can freeze it for all ---
const isCoarsePointer =
    typeof window !== 'undefined' && window.matchMedia('(pointer: coarse)').matches;
const mobileEdit = ref(false);
const canEditSchedule = computed(
    () => !props.camp.schedule_locked && (!isCoarsePointer || mobileEdit.value),
);

function toggleLock() {
    if (props.camp.schedule_locked) {
        router.delete(unlockCamp(props.camp.id).url, { preserveScroll: true });

        return;
    }

    if (!confirm('Zamknúť program? Nikto (ani ty) ho nebude môcť presúvať, kým ho neodomkneš.')) {
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
    [...localDays.value].reverse().find((d) => d.date <= todayIso && d.entries.length > 0 && !d.my_review),
);
function capitalize(v: string): string {
    return v.charAt(0).toUpperCase() + v.slice(1);
}

// --- Other dialogs ---
const slotsOpen = ref(false);
const membersOpen = ref(false);
const summaryOpen = ref(false);
const versionsOpen = ref(false);

// --- Fill name days from the Slovak calendar ---
function fillNames() {
    router.post(fillNameDays(props.camp.id).url, {}, { preserveScroll: true });
}

// --- Settings / edit camp ---
const settingsOpen = ref(false);
const settingsForm = useForm({
    name: props.camp.name,
    icon: props.camp.icon,
    color: props.camp.color,
    year: props.camp.year,
    description: props.camp.description ?? '',
    location: props.camp.location ?? '',
    start_date: props.camp.start_date,
    end_date: props.camp.end_date,
});
function openSettings() {
    settingsForm.clearErrors();
    settingsForm.defaults({
        name: props.camp.name,
        icon: props.camp.icon,
        color: props.camp.color,
        year: props.camp.year,
        description: props.camp.description ?? '',
        location: props.camp.location ?? '',
        start_date: props.camp.start_date,
        end_date: props.camp.end_date,
    });
    settingsForm.reset();
    settingsOpen.value = true;
}
function submitSettings() {
    settingsForm.put(updateCamp(props.camp.id).url, {
        preserveScroll: true,
        onSuccess: () => (settingsOpen.value = false),
    });
}
function deleteCamp() {
    if (!confirm(`Naozaj zmazať tábor „${props.camp.name}"? Táto akcia je nezvratná.`)) {
return;
}

    router.delete(destroyCamp(props.camp.id).url);
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
        name: `${props.camp.name} (kópia)`,
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

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl" :class="colorStyle(camp.color).chip">
                    <component :is="campIcon(camp.icon)" class="size-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ camp.name }}</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ camp.year }} · {{ localDays.length }} dní · {{ members.length }} vedúcich
                        <span v-if="camp.location"> · <MapPin class="inline size-3.5" /> {{ camp.location }}</span>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" @click="slotsOpen = true">
                    <Columns3 /> Časové bloky
                </Button>
                <Button variant="outline" size="sm" title="Doplniť meniny z kalendára" @click="fillNames">
                    <CalendarHeart /> Doplniť meniny
                </Button>
                <Button variant="outline" size="sm" @click="membersOpen = true">
                    <Users /> Vedúci
                </Button>
                <Button variant="outline" size="sm" @click="summaryOpen = true">
                    <Sparkles /> AI súhrn
                </Button>
                <Button variant="outline" size="sm" @click="openDuplicate">
                    <Copy /> Duplikovať
                </Button>
                <Button v-if="camp.is_owner" variant="outline" size="sm" @click="versionsOpen = true">
                    <History /> Verzie plánu
                </Button>
                <Button
                    v-if="camp.is_owner"
                    variant="outline"
                    size="sm"
                    :class="camp.schedule_locked ? 'border-amber-400 text-amber-700 dark:text-amber-300' : ''"
                    @click="toggleLock"
                >
                    <component :is="camp.schedule_locked ? LockOpen : Lock" />
                    {{ camp.schedule_locked ? 'Odomknúť program' : 'Zamknúť program' }}
                </Button>
                <Button v-if="camp.is_owner" variant="outline" size="sm" @click="openSettings">
                    <Settings /> Nastavenia
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
                <strong>{{ capitalize(dayToReview.weekday) }} {{ dayToReview.label }}</strong> ešte nemá tvoje zhodnotenie.
            </p>
            <Button size="sm" class="ml-auto" @click="onReview(dayToReview)">Zhodnotiť deň</Button>
        </div>

        <!-- Frozen schedule -->
        <div
            v-if="camp.schedule_locked"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-950/30"
        >
            <Lock class="size-5 text-amber-600 dark:text-amber-400" />
            <p class="text-sm">
                <strong>Program je uzamknutý</strong> — úpravy rozvrhu sú vypnuté pre všetkých.
                <span v-if="camp.is_owner">Odomkneš ho tlačidlom hore.</span>
                <span v-else>Odomknúť ho môže vlastník tábora.</span>
                Poznámky k dňom a hodnotenia fungujú ďalej.
            </p>
        </div>

        <!-- Legend + touch edit toggle -->
        <div class="flex flex-wrap items-center gap-3">
            <p v-if="isCoarsePointer" class="text-xs text-muted-foreground">
                Ťukni na aktivitu pre detail.
                <template v-if="!camp.schedule_locked">
                    Presúvanie zapneš tlačidlom <strong>Upravovať</strong>.
                </template>
            </p>
            <p v-else class="text-xs text-muted-foreground">
                Klikni do voľného miesta a pridaj aktivitu. Aktivitu <strong>potiahni</strong> pre presun,
                za pravý okraj pre zmenu dĺžky. <strong>Ctrl+klik</strong> označí viac aktivít (presúvajú sa
                spolu), <strong>pravý klik</strong> otvorí menu — aj na časovom bloku, ktorý sa dá presunúť
                alebo skryť len pre jeden deň. <strong>⭐</strong> pri dni = zhodnoť ho.
            </p>
            <Button
                v-if="isCoarsePointer && !camp.schedule_locked"
                :variant="mobileEdit ? 'default' : 'outline'"
                size="sm"
                class="ml-auto"
                @click="mobileEdit = !mobileEdit"
            >
                <Pencil /> {{ mobileEdit ? 'Upravovanie zapnuté' : 'Upravovať' }}
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
        <div v-else class="rounded-xl border border-dashed p-12 text-center text-muted-foreground">
            <p v-if="!slots.length">Najprv pridaj časové bloky.</p>
            <p v-else>Žiadne dni. Pridaj deň alebo uprav dátumy tábora.</p>
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
        :start-min="addStartMin"
        :editable="!camp.schedule_locked"
    />
    <DayDialog v-model:open="dayOpen" :day="dayForDialog" />
    <DayReviewDialog v-model:open="reviewOpen" :day="reviewDay" />
    <SlotsDialog v-model:open="slotsOpen" :camp-id="camp.id" :slots="slots" />
    <SummaryDialog v-model:open="summaryOpen" :camp-id="camp.id" :days="localDays" />
    <MembersDialog
        v-model:open="membersOpen"
        :camp-id="camp.id"
        :members="members"
        :invitations="invitations"
        :share-link="shareLink"
        :is-owner="camp.is_owner"
    />
    <PlanVersionsDialog
        v-model:open="versionsOpen"
        :camp-id="camp.id"
        :versions="planVersions"
        :is-owner="camp.is_owner"
        :schedule-locked="camp.schedule_locked"
    />

    <!-- Settings -->
    <Dialog v-model:open="settingsOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Nastavenia tábora</DialogTitle>
                <DialogDescription>Uprav základné údaje alebo zmaž tábor.</DialogDescription>
            </DialogHeader>
            <form class="grid max-h-[70vh] gap-4 overflow-y-auto px-1" @submit.prevent="submitSettings">
                <div class="grid gap-2">
                    <Label for="set-name">Názov</Label>
                    <Input id="set-name" v-model="settingsForm.name" required />
                    <InputError :message="settingsForm.errors.name" />
                </div>

                <CampAppearanceFields
                    v-model:icon="settingsForm.icon"
                    v-model:color="settingsForm.color"
                    v-model:location="settingsForm.location"
                />

                <div class="grid grid-cols-3 gap-4">
                    <div class="grid gap-2">
                        <Label for="set-year">Rok</Label>
                        <Input id="set-year" v-model="settingsForm.year" type="number" />
                        <InputError :message="settingsForm.errors.year" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="set-start">Začiatok</Label>
                        <Input id="set-start" v-model="settingsForm.start_date" type="date" />
                        <InputError :message="settingsForm.errors.start_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="set-end">Koniec</Label>
                        <Input id="set-end" v-model="settingsForm.end_date" type="date" />
                        <InputError :message="settingsForm.errors.end_date" />
                    </div>
                </div>
                <p class="-mt-2 text-xs text-muted-foreground">
                    Dni tábora sú dané dátumami. Zmenou termínu sa posunú (program ostáva); skrátením/predĺžením
                    sa dni odoberú alebo pridajú.
                </p>
                <div class="grid gap-2">
                    <Label for="set-desc">Popis</Label>
                    <Textarea id="set-desc" v-model="settingsForm.description" />
                </div>
                <DialogFooter class="sm:justify-between">
                    <Button type="button" variant="ghost" class="text-destructive" @click="deleteCamp">
                        <Trash2 /> Zmazať tábor
                    </Button>
                    <div class="flex gap-2">
                        <Button type="button" variant="outline" @click="settingsOpen = false">Zrušiť</Button>
                        <Button type="submit" :disabled="settingsForm.processing">Uložiť</Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Duplicate -->
    <Dialog v-model:open="duplicateOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Duplikovať tábor</DialogTitle>
                <DialogDescription>
                    Vytvorí nový turnus s rovnakou časovou kostrou. Program môžeš skopírovať alebo začať načisto.
                </DialogDescription>
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submitDuplicate">
                <div class="grid gap-2">
                    <Label for="dup-name">Názov</Label>
                    <Input id="dup-name" v-model="duplicateForm.name" required />
                    <InputError :message="duplicateForm.errors.name" />
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="grid gap-2">
                        <Label for="dup-year">Rok</Label>
                        <Input id="dup-year" v-model="duplicateForm.year" type="number" />
                        <InputError :message="duplicateForm.errors.year" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="dup-start">Začiatok</Label>
                        <Input id="dup-start" v-model="duplicateForm.start_date" type="date" required />
                        <InputError :message="duplicateForm.errors.start_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="dup-end">Koniec</Label>
                        <Input id="dup-end" v-model="duplicateForm.end_date" type="date" required />
                        <InputError :message="duplicateForm.errors.end_date" />
                    </div>
                </div>
                <label class="flex items-center gap-3 rounded-lg border p-3">
                    <Checkbox
                        :model-value="duplicateForm.copy_program"
                        @update:model-value="duplicateForm.copy_program = $event === true"
                    />
                    <span>
                        <span class="font-medium">Skopírovať program</span>
                        <span class="block text-sm text-muted-foreground">Prenesie aktivity na zodpovedajúce dni.</span>
                    </span>
                </label>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="duplicateOpen = false">Zrušiť</Button>
                    <Button type="submit" :disabled="duplicateForm.processing">Duplikovať</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
