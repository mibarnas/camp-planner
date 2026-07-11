<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { CalendarHeart, CalendarPlus, Columns3, Copy, Settings, Trash2, Users } from '@lucide/vue';
import { ref, watch, watchEffect } from 'vue';
import DayDialog from '@/components/camp/DayDialog.vue';
import EntryDialog from '@/components/camp/EntryDialog.vue';
import MembersDialog from '@/components/camp/MembersDialog.vue';
import SlotsDialog from '@/components/camp/SlotsDialog.vue';
import TimetableTimeline from '@/components/camp/TimetableTimeline.vue';
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
import InputError from '@/components/InputError.vue';
import { index as campsIndex } from '@/routes/camps';
import { store as storeDay } from '@/routes/days';
import { destroy as destroyCamp, duplicate as duplicateCamp, fillNameDays, update as updateCamp } from '@/routes/camps';
import { bulkDestroy, bulkUpdate, toggle as toggleEntry } from '@/routes/entries';
import type {
    Activity,
    ActivityCategory,
    ActivityLibraryRef,
    Camp,
    CampDay,
    CampInvitation,
    CampMember,
    ProgramEntry,
    ShareLink,
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
function onToggleDone(entry: ProgramEntry) {
    entry.is_done = !entry.is_done; // optimistic
    router.put(toggleEntry(entry.id).url, {}, { preserveScroll: true });
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
        { preserveScroll: true },
    );
}
function onBulkDelete(ids: number[]) {
    router.post(bulkDestroy().url, { ids }, { preserveScroll: true });
}
function onBulkResponsible(ids: number[], responsible: string) {
    router.put(
        bulkUpdate().url,
        { entries: ids.map((id) => ({ id, responsible })) },
        { preserveScroll: true },
    );
}

// --- Day dialog ---
const dayOpen = ref(false);
const dayForDialog = ref<CampDay | null>(null);
function onEditDay(day: CampDay) {
    dayForDialog.value = day;
    dayOpen.value = true;
}

// --- Other dialogs ---
const slotsOpen = ref(false);
const membersOpen = ref(false);

// --- Add day ---
const addDayOpen = ref(false);
const addDayForm = useForm({ date: '' });
function submitAddDay() {
    addDayForm.post(storeDay(props.camp.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            addDayOpen.value = false;
            addDayForm.reset();
        },
    });
}

// --- Fill name days from the Slovak calendar ---
function fillNames() {
    router.post(fillNameDays(props.camp.id).url, {}, { preserveScroll: true });
}

// --- Settings / edit camp ---
const settingsOpen = ref(false);
const settingsForm = useForm({
    name: props.camp.name,
    year: props.camp.year,
    description: props.camp.description ?? '',
    start_date: props.camp.start_date,
    end_date: props.camp.end_date,
});
function openSettings() {
    settingsForm.clearErrors();
    settingsForm.defaults({
        name: props.camp.name,
        year: props.camp.year,
        description: props.camp.description ?? '',
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
    if (!confirm(`Naozaj zmazať tábor „${props.camp.name}"? Táto akcia je nezvratná.`)) return;
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
            <div>
                <h1 class="text-2xl font-bold tracking-tight">{{ camp.name }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ camp.year }} · {{ localDays.length }} dní · {{ members.length }} vedúcich
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" @click="slotsOpen = true">
                    <Columns3 /> Časové bloky
                </Button>
                <Button variant="outline" size="sm" @click="addDayOpen = true">
                    <CalendarPlus /> Pridať deň
                </Button>
                <Button variant="outline" size="sm" title="Doplniť meniny z kalendára" @click="fillNames">
                    <CalendarHeart /> Doplniť meniny
                </Button>
                <Button variant="outline" size="sm" @click="membersOpen = true">
                    <Users /> Vedúci
                </Button>
                <Button variant="outline" size="sm" @click="openDuplicate">
                    <Copy /> Duplikovať
                </Button>
                <Button v-if="camp.is_owner" variant="outline" size="sm" @click="openSettings">
                    <Settings /> Nastavenia
                </Button>
            </div>
        </div>

        <!-- Legend -->
        <p class="text-xs text-muted-foreground">
            Klikni do voľného miesta a pridaj aktivitu. Aktivitu <strong>potiahni</strong> pre presun,
            za pravý okraj pre zmenu dĺžky. <strong>Ctrl+klik</strong> označí viac aktivít (presúvajú sa
            spolu), <strong>pravý klik</strong> otvorí menu.
        </p>

        <!-- Timetable -->
        <TimetableTimeline
            v-if="slots.length && localDays.length"
            :days="localDays"
            :slots="slots"
            @edit="onEdit"
            @add="onAdd"
            @edit-day="onEditDay"
            @toggle-done="onToggleDone"
            @commit="onCommit"
            @bulk-delete="onBulkDelete"
            @bulk-responsible="onBulkResponsible"
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
    />
    <DayDialog v-model:open="dayOpen" :day="dayForDialog" />
    <SlotsDialog v-model:open="slotsOpen" :camp-id="camp.id" :slots="slots" />
    <MembersDialog
        v-model:open="membersOpen"
        :camp-id="camp.id"
        :members="members"
        :invitations="invitations"
        :share-link="shareLink"
        :is-owner="camp.is_owner"
    />

    <!-- Add day -->
    <Dialog v-model:open="addDayOpen">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Pridať deň</DialogTitle>
                <DialogDescription>Pridá nový deň do tábora.</DialogDescription>
            </DialogHeader>
            <form class="grid gap-2" @submit.prevent="submitAddDay">
                <Label for="add-day-date">Dátum</Label>
                <Input id="add-day-date" v-model="addDayForm.date" type="date" required />
                <InputError :message="addDayForm.errors.date" />
                <DialogFooter class="mt-2">
                    <Button type="button" variant="outline" @click="addDayOpen = false">Zrušiť</Button>
                    <Button type="submit" :disabled="addDayForm.processing">Pridať</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Settings -->
    <Dialog v-model:open="settingsOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Nastavenia tábora</DialogTitle>
                <DialogDescription>Uprav základné údaje alebo zmaž tábor.</DialogDescription>
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submitSettings">
                <div class="grid gap-2">
                    <Label for="set-name">Názov</Label>
                    <Input id="set-name" v-model="settingsForm.name" required />
                    <InputError :message="settingsForm.errors.name" />
                </div>
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
