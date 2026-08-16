<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { BookmarkPlus, Check, Clock, Coffee, PenLine, Search, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { categoryById, colorStyle } from '@/lib/campColors';
import { durationLabel, minToTime, timeToMin } from '@/lib/timeline';
import { store as storeActivity } from '@/routes/activities';
import { destroy as destroyEntry, store as storeEntry, update as updateEntry } from '@/routes/entries';
import type {
    Activity,
    ActivityCategory,
    ActivityLibraryRef,
    CampDay,
    EntryKind,
    EntryStatus,
    ProgramEntry,
} from '@/types/camp';

const props = withDefaults(
    defineProps<{
        open: boolean;
        day: CampDay | null;
        entry: ProgramEntry | null;
        activities: Activity[];
        categories: ActivityCategory[];
        library: ActivityLibraryRef | null;
        startMin?: number;
        editable?: boolean;
    }>(),
    { editable: true },
);

const STATUS_OPTIONS: { value: EntryStatus; label: string }[] = [
    { value: 'todo', label: 'Treba doriešiť' },
    { value: 'none', label: 'Rozpracované' },
    { value: 'done', label: 'Hotové' },
];

// One tap fills in the things that recur on almost every camp day.
const SIMPLE_PRESETS: { label: string; duration: number }[] = [
    { label: 'Raňajky', duration: 45 },
    { label: 'Obed', duration: 60 },
    { label: 'Olovrant', duration: 30 },
    { label: 'Večera', duration: 60 },
    { label: 'Odpočinok', duration: 60 },
    { label: 'Hygiena', duration: 30 },
    { label: 'Presun', duration: 60 },
    { label: 'Balenie', duration: 30 },
    { label: 'Voľno', duration: 60 },
];

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const form = useForm({
    camp_day_id: 0,
    activity_id: null as number | null,
    start_time: '09:00',
    duration: 60,
    title: '',
    description: '',
    responsible: '',
    materials: '',
    notes: '',
    status: 'none' as EntryStatus,
    kind: 'detailed' as EntryKind,
});

// --- Picker state ---
type Mode = 'library' | 'custom' | 'simple';
const mode = ref<Mode>('library');
const isSimple = computed(() => mode.value === 'simple');
const search = ref('');
const categoryFilter = ref<number | 'all'>('all');

const presentCategories = computed(() =>
    props.categories.filter((c) => props.activities.some((a) => a.category_id === c.id)),
);

const filteredActivities = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.activities.filter(
        (a) =>
            (categoryFilter.value === 'all' || a.category_id === categoryFilter.value) &&
            (!q || a.name.toLowerCase().includes(q) || (a.description ?? '').toLowerCase().includes(q)),
    );
});

const endTime = computed(() =>
    minToTime(timeToMin(form.start_time || '00:00') + Number(form.duration || 0)),
);

watch(
    () => props.open,
    (open) => {
        if (!open || !props.day) {
return;
}

        const e = props.entry;
        form.clearErrors();
        form.defaults({
            camp_day_id: props.day.id,
            activity_id: e?.activity_id ?? null,
            start_time: e?.start_time ?? minToTime(props.startMin ?? 9 * 60),
            duration: e?.duration ?? 60,
            title: e?.title ?? '',
            description: e?.description ?? '',
            responsible: e?.responsible ?? '',
            materials: e?.materials ?? '',
            notes: e?.notes ?? '',
            status: e?.status ?? 'none',
            kind: e?.kind ?? 'detailed',
        });
        form.reset();
        mode.value =
            e?.kind === 'simple'
                ? 'simple'
                : e && e.activity_id === null && (e.title || e.description)
                  ? 'custom'
                  : 'library';
        search.value = '';
        categoryFilter.value = 'all';
    },
);

function pickActivity(activity: Activity) {
    if (form.activity_id === activity.id) {
        // Second click unselects -> back to a blank slate.
        form.activity_id = null;

        return;
    }

    form.activity_id = activity.id;
    form.title = activity.name;
    form.description = activity.description ?? '';
    form.materials = activity.materials ?? '';

    if (!props.entry) {
form.duration = activity.default_duration;
}
}

function switchMode(next: Mode) {
    mode.value = next;

    if (next !== 'library') {
form.activity_id = null;
}

    form.kind = next === 'simple' ? 'simple' : 'detailed';
}

function applyPreset(preset: { label: string; duration: number }) {
    form.title = preset.label;

    // Only presume the length for a new block; an existing one keeps its slot.
    if (!props.entry) {
        form.duration = preset.duration;
    }
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    };

    if (props.entry) {
        form.put(updateEntry(props.entry.id).url, options);
    } else {
        form.post(storeEntry().url, options);
    }
}

function remove() {
    if (!props.entry) {
        emit('update:open', false);

        return;
    }

    router.delete(destroyEntry(props.entry.id).url, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}

// Save this program cell as a reusable activity in the camp's database.
const savingToLibrary = ref(false);
const savedToLibrary = ref(false);
const canSaveToLibrary = computed(
    () => !!props.library && !isSimple.value && !form.activity_id && form.title.trim().length > 0,
);
function saveToLibrary() {
    if (!props.library || !canSaveToLibrary.value) {
return;
}

    router.post(
        storeActivity().url,
        {
            activity_library_id: props.library.id,
            activity_category_id: null,
            name: form.title,
            description: form.description,
            default_duration: form.duration,
            color: 'emerald',
            materials: form.materials,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (savingToLibrary.value = true),
            onSuccess: () => (savedToLibrary.value = true),
            onFinish: () => (savingToLibrary.value = false),
        },
    );
}

// Reset the "saved" flag whenever the dialog reopens.
watch(
    () => props.open,
    (open) => {
        if (open) {
savedToLibrary.value = false;
}
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ entry ? 'Upraviť aktivitu' : 'Nová aktivita' }}
                    <span class="text-muted-foreground">· {{ day?.weekday }} {{ day?.label }}</span>
                </DialogTitle>
                <DialogDescription>{{ form.start_time }}–{{ endTime }}</DialogDescription>
            </DialogHeader>

            <div class="grid max-h-[65vh] gap-4 overflow-y-auto px-1">
                <!-- Source tabs -->
                <div class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="mode === 'library' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        @click="switchMode('library')"
                    >
                        Z knižnice
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="mode === 'custom' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        @click="switchMode('custom')"
                    >
                        <PenLine class="mr-1 inline size-3.5" /> Vlastná aktivita
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="mode === 'simple' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        @click="switchMode('simple')"
                    >
                        <Coffee class="mr-1 inline size-3.5" /> Jednoduchý blok
                    </button>
                </div>

                <!-- Simple block: a label on the timeline, nothing more -->
                <div v-if="isSimple" class="grid gap-2">
                    <p class="text-xs text-muted-foreground">
                        Pre veci mimo programu — jedlo, presun, odpočinok. Nemá scenár ani materiál
                        a nezaraďuje sa do hodnotenia dňa.
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="preset in SIMPLE_PRESETS"
                            :key="preset.label"
                            type="button"
                            class="rounded-full border px-2.5 py-0.5 text-xs transition-colors"
                            :class="form.title === preset.label ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                            @click="applyPreset(preset)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <!-- Library picker -->
                <div v-if="mode === 'library'" class="grid gap-3">
                    <div class="relative">
                        <Search class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Hľadať aktivitu…" class="pl-8" />
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            class="rounded-full border px-2.5 py-0.5 text-xs transition-colors"
                            :class="categoryFilter === 'all' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                            @click="categoryFilter = 'all'"
                        >
                            Všetky
                        </button>
                        <button
                            v-for="c in presentCategories"
                            :key="c.id"
                            type="button"
                            class="flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs transition-colors"
                            :class="categoryFilter === c.id ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                            @click="categoryFilter = c.id"
                        >
                            <span class="size-2 rounded-full" :class="colorStyle(c.color).dot" />
                            {{ c.name }}
                        </button>
                    </div>

                    <div v-if="filteredActivities.length" class="grid max-h-56 grid-cols-1 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                        <button
                            v-for="a in filteredActivities"
                            :key="a.id"
                            type="button"
                            class="relative flex flex-col gap-1 rounded-lg border p-2.5 text-left transition-all hover:border-primary/50"
                            :class="form.activity_id === a.id ? 'border-primary ring-2 ring-primary/30' : ''"
                            @click="pickActivity(a)"
                        >
                            <span
                                v-if="form.activity_id === a.id"
                                class="absolute top-2 right-2 flex size-4 items-center justify-center rounded-full bg-primary text-primary-foreground"
                            >
                                <Check class="size-3" />
                            </span>
                            <span class="flex items-center gap-1.5 pr-5">
                                <span class="size-2.5 shrink-0 rounded-full" :class="colorStyle(a.color).dot" />
                                <span class="truncate text-sm font-medium">{{ a.name }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <Badge
                                    v-if="categoryById(categories, a.category_id)"
                                    variant="secondary"
                                    class="px-1.5 py-0 text-[10px]"
                                    :class="colorStyle(categoryById(categories, a.category_id)!.color).chip"
                                >
                                    {{ categoryById(categories, a.category_id)!.name }}
                                </Badge>
                                <span class="flex items-center gap-0.5 text-[11px] text-muted-foreground">
                                    <Clock class="size-3" /> {{ durationLabel(a.default_duration) }}
                                </span>
                            </span>
                            <span v-if="a.description" class="line-clamp-2 text-xs text-muted-foreground">
                                {{ a.description }}
                            </span>
                        </button>
                    </div>
                    <p v-else class="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
                        Žiadne aktivity. Skús iné hľadanie alebo vytvor vlastnú aktivitu.
                    </p>
                </div>

                <!-- Shared fields -->
                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="entry-start">Začiatok</Label>
                            <Input id="entry-start" v-model="form.start_time" type="time" step="300" required />
                            <InputError :message="form.errors.start_time" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="entry-duration">Dĺžka (min) — koniec {{ endTime }}</Label>
                            <Input id="entry-duration" v-model="form.duration" type="number" min="5" max="1440" step="5" required />
                            <InputError :message="form.errors.duration" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="entry-title">Názov</Label>
                        <Input
                            id="entry-title"
                            v-model="form.title"
                            :placeholder="isSimple ? 'Napr. Presun do Tatier' : 'Napr. Zoznamovačky'"
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div v-if="!isSimple" class="grid gap-2">
                        <Label for="entry-desc">Program / scenár</Label>
                        <Textarea id="entry-desc" v-model="form.description" class="min-h-24" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div v-if="!isSimple" class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="entry-resp">Zodpovedný</Label>
                            <Input id="entry-resp" v-model="form.responsible" placeholder="Meno animátora" />
                            <InputError :message="form.errors.responsible" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="entry-mat">Materiál</Label>
                            <Input id="entry-mat" v-model="form.materials" />
                            <InputError :message="form.errors.materials" />
                        </div>
                    </div>

                    <div v-if="entry" class="grid gap-2">
                        <Label>Stav</Label>
                        <div class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1">
                            <button
                                v-for="option in STATUS_OPTIONS"
                                :key="option.value"
                                type="button"
                                class="rounded-md px-2 py-1.5 text-sm font-medium transition-colors"
                                :class="form.status === option.value ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="form.status = option.value"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="entry-notes">Poznámky</Label>
                        <Textarea id="entry-notes" v-model="form.notes" class="min-h-16" placeholder="Interné poznámky k tejto aktivite v programe…" />
                        <InputError :message="form.errors.notes" />
                    </div>

                    <!-- Save this cell as a reusable library activity -->
                    <Button
                        v-if="canSaveToLibrary"
                        type="button"
                        variant="outline"
                        class="w-fit"
                        :disabled="savingToLibrary || savedToLibrary"
                        @click="saveToLibrary"
                    >
                        <component :is="savedToLibrary ? Check : BookmarkPlus" />
                        {{ savedToLibrary ? 'Uložené do databázy' : 'Uložiť do databázy aktivít' }}
                    </Button>
                </form>
            </div>

            <p v-if="!editable" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-200">
                Program je uzamknutý — zmeny sa nedajú uložiť, kým ho vlastník neodomkne.
            </p>

            <DialogFooter class="sm:justify-between">
                <Button v-if="entry && editable" type="button" variant="ghost" class="text-destructive" @click="remove">
                    <Trash2 /> Odstrániť
                </Button>
                <span v-else />
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Zavrieť</Button>
                    <Button v-if="editable" type="button" :disabled="form.processing" @click="submit">Uložiť</Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
