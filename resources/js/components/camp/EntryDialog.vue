<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    BookmarkPlus,
    Check,
    Clock,
    Coffee,
    PenLine,
    Search,
    Trash2,
} from '@lucide/vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useI18n } from '@/i18n';
import { categoryById, colorStyle } from '@/lib/campColors';
import { matchLeader } from '@/lib/leaders';
import { durationLabel, minToTime, timeToMin } from '@/lib/timeline';
import { store as storeActivity } from '@/routes/activities';
import {
    destroy as destroyEntry,
    store as storeEntry,
    update as updateEntry,
} from '@/routes/entries';
import type {
    Activity,
    ActivityCategory,
    ActivityLibraryRef,
    CampDay,
    CampLeader,
    EntryKind,
    EntryStatus,
    PointsMode,
    ProgramEntry,
} from '@/types/camp';

const { t } = useI18n();

const props = withDefaults(
    defineProps<{
        open: boolean;
        day: CampDay | null;
        entry: ProgramEntry | null;
        activities: Activity[];
        categories: ActivityCategory[];
        library: ActivityLibraryRef | null;
        leaders?: CampLeader[];
        startMin?: number;
        editable?: boolean;
    }>(),
    { editable: true, leaders: () => [] },
);

const POINTS_OPTIONS: { value: PointsMode; label: string; hint: string }[] = [
    { value: 'none', label: t('points.mode.none'), hint: '' },
    {
        value: 'raw',
        label: t('points.mode.raw'),
        hint: t('points.mode.rawHint'),
    },
    {
        value: 'placement',
        label: t('points.mode.placement'),
        hint: t('points.mode.placementHint'),
    },
];

const STATUS_OPTIONS: { value: EntryStatus; label: string }[] = [
    { value: 'todo', label: t('entry.status.todo') },
    { value: 'none', label: t('entry.status.none') },
    { value: 'done', label: t('entry.status.done') },
];

// One tap fills in the things that recur on almost every camp day.
const SIMPLE_PRESETS: { label: string; duration: number }[] = [
    { label: t('entry.preset.breakfast'), duration: 45 },
    { label: t('entry.preset.lunch'), duration: 60 },
    { label: t('entry.preset.snack'), duration: 30 },
    { label: t('entry.preset.dinner'), duration: 60 },
    { label: t('entry.preset.rest'), duration: 60 },
    { label: t('entry.preset.hygiene'), duration: 30 },
    { label: t('entry.preset.transfer'), duration: 60 },
    { label: t('entry.preset.packing'), duration: 30 },
    { label: t('entry.preset.free'), duration: 60 },
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
    points_mode: 'none' as PointsMode,
});

// Typing a name stays free — this only tells the user when what they typed
// names someone the camp already knows.
const matchedLeader = computed(() =>
    matchLeader(form.responsible, props.leaders),
);
const pointsHint = computed(
    () => POINTS_OPTIONS.find((o) => o.value === form.points_mode)?.hint ?? '',
);

// --- Picker state ---
type Mode = 'library' | 'custom' | 'simple';
const mode = ref<Mode>('library');
const isSimple = computed(() => mode.value === 'simple');
const search = ref('');
const categoryFilter = ref<number | 'all'>('all');

// Once an activity is picked the browsing UI collapses to a one-line summary — on a phone the
// list would otherwise bury the time and title fields the user still has to fill in.
const pickerExpanded = ref(true);
const showPicker = computed(() => pickerExpanded.value || !form.activity_id);
const selectedActivity = computed(
    () => props.activities.find((a) => a.id === form.activity_id) ?? null,
);

const presentCategories = computed(() =>
    props.categories.filter((c) =>
        props.activities.some((a) => a.category_id === c.id),
    ),
);

const filteredActivities = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.activities.filter(
        (a) =>
            (categoryFilter.value === 'all' ||
                a.category_id === categoryFilter.value) &&
            (!q ||
                a.name.toLowerCase().includes(q) ||
                (a.description ?? '').toLowerCase().includes(q)),
    );
});

const endTime = computed(() =>
    minToTime(
        timeToMin(form.start_time || '00:00') + Number(form.duration || 0),
    ),
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
            points_mode: e?.points_mode ?? 'none',
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
        pickerExpanded.value = !(mode.value === 'library' && form.activity_id);
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

    pickerExpanded.value = false;
}

function switchMode(next: Mode) {
    mode.value = next;

    if (next !== 'library') {
        form.activity_id = null;
    }

    form.kind = next === 'simple' ? 'simple' : 'detailed';
    pickerExpanded.value = !form.activity_id;
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
    () =>
        !!props.library &&
        !isSimple.value &&
        !form.activity_id &&
        form.title.trim().length > 0,
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
                    {{ entry ? t('activities.edit') : t('activities.new') }}
                    <span class="text-muted-foreground"
                        >· {{ day?.weekday }} {{ day?.label }}</span
                    >
                </DialogTitle>
                <DialogDescription
                    >{{ form.start_time }}–{{ endTime }}</DialogDescription
                >
            </DialogHeader>

            <div class="grid gap-4 px-1">
                <!-- Source tabs -->
                <div class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1">
                    <button
                        type="button"
                        class="truncate rounded-md px-2 py-1.5 text-xs font-medium transition-colors sm:px-3 sm:text-sm"
                        :class="
                            mode === 'library'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="switchMode('library')"
                    >
                        {{ t('entry.tab.library') }}
                    </button>
                    <button
                        type="button"
                        class="truncate rounded-md px-2 py-1.5 text-xs font-medium transition-colors sm:px-3 sm:text-sm"
                        :class="
                            mode === 'custom'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="switchMode('custom')"
                    >
                        <PenLine class="mr-1 inline size-3.5" />
                        <span class="sm:hidden">{{
                            t('entry.tab.customShort')
                        }}</span>
                        <span class="hidden sm:inline">{{
                            t('entry.tab.custom')
                        }}</span>
                    </button>
                    <button
                        type="button"
                        class="truncate rounded-md px-2 py-1.5 text-xs font-medium transition-colors sm:px-3 sm:text-sm"
                        :class="
                            mode === 'simple'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="switchMode('simple')"
                    >
                        <Coffee class="mr-1 inline size-3.5" />
                        <span class="sm:hidden">{{
                            t('entry.tab.simpleShort')
                        }}</span>
                        <span class="hidden sm:inline">{{
                            t('entry.tab.simple')
                        }}</span>
                    </button>
                </div>

                <!-- Simple block: a label on the timeline, nothing more -->
                <div v-if="isSimple" class="grid gap-2">
                    <p class="text-xs text-muted-foreground">
                        {{ t('entry.simpleHint') }}
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="preset in SIMPLE_PRESETS"
                            :key="preset.label"
                            type="button"
                            class="rounded-full border px-2.5 py-0.5 text-xs transition-colors"
                            :class="
                                form.title === preset.label
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-accent'
                            "
                            @click="applyPreset(preset)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <!-- Library picker -->
                <div v-if="mode === 'library'" class="grid gap-3">
                    <!-- Picked: collapse to one line so the fields below stay in view -->
                    <div
                        v-if="!showPicker"
                        class="flex items-center gap-2 rounded-lg border border-primary/40 bg-primary/5 p-2.5"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :class="
                                colorStyle(selectedActivity?.color ?? 'emerald')
                                    .dot
                            "
                        />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{
                                selectedActivity?.name ?? form.title
                            }}</span>
                            <span
                                class="flex items-center gap-2 text-[11px] text-muted-foreground"
                            >
                                <Badge
                                    v-if="
                                        selectedActivity &&
                                        categoryById(
                                            categories,
                                            selectedActivity.category_id,
                                        )
                                    "
                                    variant="secondary"
                                    class="px-1.5 py-0 text-[10px]"
                                    :class="
                                        colorStyle(
                                            categoryById(
                                                categories,
                                                selectedActivity.category_id,
                                            )!.color,
                                        ).chip
                                    "
                                >
                                    {{
                                        categoryById(
                                            categories,
                                            selectedActivity.category_id,
                                        )!.name
                                    }}
                                </Badge>
                                <span class="flex items-center gap-0.5">
                                    <Clock class="size-3" />
                                    {{
                                        durationLabel(
                                            Number(form.duration) || 0,
                                        )
                                    }}
                                </span>
                            </span>
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="pickerExpanded = true"
                            >{{ t('common.change') }}</Button
                        >
                    </div>

                    <div v-else class="relative">
                        <Search
                            class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            :placeholder="t('entry.searchPlaceholder')"
                            class="pl-8"
                        />
                    </div>
                    <div v-if="showPicker" class="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            class="rounded-full border px-2.5 py-0.5 text-xs transition-colors"
                            :class="
                                categoryFilter === 'all'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-accent'
                            "
                            @click="categoryFilter = 'all'"
                        >
                            {{ t('common.all') }}
                        </button>
                        <button
                            v-for="c in presentCategories"
                            :key="c.id"
                            type="button"
                            class="flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs transition-colors"
                            :class="
                                categoryFilter === c.id
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-accent'
                            "
                            @click="categoryFilter = c.id"
                        >
                            <span
                                class="size-2 rounded-full"
                                :class="colorStyle(c.color).dot"
                            />
                            {{ c.name }}
                        </button>
                    </div>

                    <div
                        v-if="showPicker && filteredActivities.length"
                        class="grid max-h-56 grid-cols-1 gap-2 overflow-y-auto pr-1 sm:grid-cols-2"
                    >
                        <button
                            v-for="a in filteredActivities"
                            :key="a.id"
                            type="button"
                            class="relative flex flex-col gap-1 rounded-lg border p-2.5 text-left transition-all hover:border-primary/50"
                            :class="
                                form.activity_id === a.id
                                    ? 'border-primary ring-2 ring-primary/30'
                                    : ''
                            "
                            @click="pickActivity(a)"
                        >
                            <span
                                v-if="form.activity_id === a.id"
                                class="absolute top-2 right-2 flex size-4 items-center justify-center rounded-full bg-primary text-primary-foreground"
                            >
                                <Check class="size-3" />
                            </span>
                            <span class="flex items-center gap-1.5 pr-5">
                                <span
                                    class="size-2.5 shrink-0 rounded-full"
                                    :class="colorStyle(a.color).dot"
                                />
                                <span class="truncate text-sm font-medium">{{
                                    a.name
                                }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <Badge
                                    v-if="
                                        categoryById(categories, a.category_id)
                                    "
                                    variant="secondary"
                                    class="px-1.5 py-0 text-[10px]"
                                    :class="
                                        colorStyle(
                                            categoryById(
                                                categories,
                                                a.category_id,
                                            )!.color,
                                        ).chip
                                    "
                                >
                                    {{
                                        categoryById(categories, a.category_id)!
                                            .name
                                    }}
                                </Badge>
                                <span
                                    class="flex items-center gap-0.5 text-[11px] text-muted-foreground"
                                >
                                    <Clock class="size-3" />
                                    {{ durationLabel(a.default_duration) }}
                                </span>
                            </span>
                            <span
                                v-if="a.description"
                                class="line-clamp-2 text-xs text-muted-foreground"
                            >
                                {{ a.description }}
                            </span>
                        </button>
                    </div>
                    <p
                        v-else-if="showPicker"
                        class="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground"
                    >
                        {{ t('entry.noActivities') }}
                    </p>
                </div>

                <!-- Shared fields -->
                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="entry-start">{{
                                t('camps.field.start')
                            }}</Label>
                            <Input
                                id="entry-start"
                                v-model="form.start_time"
                                type="time"
                                step="300"
                                required
                            />
                            <InputError :message="form.errors.start_time" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="entry-duration">{{
                                t('entry.durationLabel', { end: endTime })
                            }}</Label>
                            <Input
                                id="entry-duration"
                                v-model="form.duration"
                                type="number"
                                min="5"
                                max="1440"
                                step="5"
                                required
                            />
                            <InputError :message="form.errors.duration" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="entry-title">{{ t('common.name') }}</Label>
                        <Input
                            id="entry-title"
                            v-model="form.title"
                            :placeholder="
                                isSimple
                                    ? t('entry.titlePlaceholderSimple')
                                    : t('entry.titlePlaceholder')
                            "
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div v-if="!isSimple" class="grid gap-2">
                        <Label for="entry-desc">{{ t('entry.script') }}</Label>
                        <Textarea
                            id="entry-desc"
                            v-model="form.description"
                            class="min-h-24"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div v-if="!isSimple" class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="entry-resp">{{
                                t('entry.responsible')
                            }}</Label>
                            <div class="relative">
                                <Input
                                    id="entry-resp"
                                    v-model="form.responsible"
                                    list="entry-resp-leaders"
                                    :placeholder="
                                        t('entry.responsiblePlaceholder')
                                    "
                                    :class="matchedLeader ? 'pr-8' : ''"
                                />
                                <span
                                    v-if="matchedLeader"
                                    class="absolute inset-y-0 right-2 flex items-center"
                                    :title="t('camps.leaders.title')"
                                >
                                    <span
                                        v-if="matchedLeader.color"
                                        class="mr-1 size-2 rounded-full"
                                        :class="
                                            colorStyle(matchedLeader.color).dot
                                        "
                                    />
                                    <Check
                                        class="size-4 text-emerald-600 dark:text-emerald-400"
                                    />
                                </span>
                            </div>
                            <datalist id="entry-resp-leaders">
                                <option
                                    v-for="leader in leaders"
                                    :key="leader.id"
                                    :value="leader.name"
                                />
                            </datalist>
                            <InputError :message="form.errors.responsible" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="entry-mat">{{
                                t('entry.materials')
                            }}</Label>
                            <Input id="entry-mat" v-model="form.materials" />
                            <InputError :message="form.errors.materials" />
                        </div>
                    </div>

                    <div v-if="!isSimple" class="grid gap-2">
                        <Label>{{ t('entry.groupScoring') }}</Label>
                        <Select
                            :model-value="form.points_mode"
                            @update:model-value="
                                form.points_mode = $event as PointsMode
                            "
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in POINTS_OPTIONS"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="pointsHint"
                            class="text-xs text-muted-foreground"
                        >
                            {{ pointsHint }}
                        </p>
                        <InputError :message="form.errors.points_mode" />
                    </div>

                    <div v-if="entry" class="grid gap-2">
                        <Label>Stav</Label>
                        <div
                            class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1"
                        >
                            <button
                                v-for="option in STATUS_OPTIONS"
                                :key="option.value"
                                type="button"
                                class="rounded-md px-2 py-1.5 text-sm font-medium transition-colors"
                                :class="
                                    form.status === option.value
                                        ? 'bg-background shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                @click="form.status = option.value"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="entry-notes">{{ t('entry.notes') }}</Label>
                        <Textarea
                            id="entry-notes"
                            v-model="form.notes"
                            class="min-h-16"
                            :placeholder="t('entry.notesPlaceholder')"
                        />
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
                        <component
                            :is="savedToLibrary ? Check : BookmarkPlus"
                        />
                        {{
                            savedToLibrary
                                ? t('entry.savedToLibrary')
                                : t('entry.saveToLibrary')
                        }}
                    </Button>
                </form>
            </div>

            <p
                v-if="!editable"
                class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-200"
            >
                {{ t('entry.lockedHint') }}
            </p>

            <DialogFooter class="sm:justify-between">
                <Button
                    v-if="entry && editable"
                    type="button"
                    variant="ghost"
                    class="text-destructive"
                    @click="remove"
                >
                    <Trash2 /> {{ t('common.remove') }}
                </Button>
                <span v-else />
                <div class="flex gap-2 *:flex-1 sm:*:flex-initial">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        {{ t('common.close') }}
                    </Button>
                    <Button
                        v-if="editable"
                        type="button"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        {{ t('common.save') }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
