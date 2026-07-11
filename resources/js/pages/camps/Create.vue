<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    Check,
    Clock3,
    MapPin,
    Plus,
    Tent,
    Trash2,
    UserPlus,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CampAppearanceFields from '@/components/camp/CampAppearanceFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
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
import InputError from '@/components/InputError.vue';
import { index as campsIndex, store as storeCamp } from '@/routes/camps';
import { colorStyle, COLOR_NAMES } from '@/lib/campColors';
import { campIcon } from '@/lib/campIcons';
import type { ActivityLibraryRef } from '@/types/camp';

type SlotDraft = {
    name: string;
    start_time: string;
    end_time: string;
    kind: 'fixed' | 'activity';
    color: string;
};

const props = defineProps<{
    libraries: ActivityLibraryRef[];
    defaultSlots: SlotDraft[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tábory', href: '/camps' },
            { title: 'Nový tábor', href: '/camps/create' },
        ],
    },
});

const currentYear = new Date().getFullYear();

const form = useForm({
    name: '',
    icon: 'tent',
    color: 'emerald',
    year: currentYear,
    description: '',
    location: '',
    start_date: '',
    end_date: '',
    activity_library_id: null as number | null,
    slots: props.defaultSlots.map((s) => ({ ...s })) as SlotDraft[],
    trip_dates: [] as string[],
    leader_emails: [''] as string[],
});

const steps = ['Základné údaje', 'Denné bloky', 'Výletné dni', 'Vedúci'] as const;
const step = ref(0);

// --- Day list derived from the chosen date range ---
const WEEKDAYS = ['pondelok', 'utorok', 'streda', 'štvrtok', 'piatok', 'sobota', 'nedeľa'];
function fmt(d: Date): string {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
const rangeDays = computed(() => {
    const out: { date: string; weekday: string; label: string; dow: number }[] = [];
    if (!form.start_date || !form.end_date) return out;
    const s = new Date(form.start_date + 'T00:00:00');
    const e = new Date(form.end_date + 'T00:00:00');
    if (isNaN(s.getTime()) || isNaN(e.getTime()) || e < s) return out;
    for (const d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
        const dow = d.getDay() === 0 ? 7 : d.getDay();
        out.push({ date: fmt(d), weekday: WEEKDAYS[dow - 1], label: `${d.getDate()}.${d.getMonth() + 1}.`, dow });
        if (out.length > 120) break;
    }
    return out;
});

// Pre-select Tue/Thu as trips once a range is first chosen.
const tripsSeeded = ref(false);
function seedTrips() {
    if (tripsSeeded.value) return;
    form.trip_dates = rangeDays.value.filter((d) => d.dow === 2 || d.dow === 4).map((d) => d.date);
    tripsSeeded.value = true;
}
function isTrip(date: string): boolean {
    return form.trip_dates.includes(date);
}
function toggleTrip(date: string) {
    form.trip_dates = isTrip(date)
        ? form.trip_dates.filter((d) => d !== date)
        : [...form.trip_dates, date];
}

// --- Slots editor ---
function addSlot() {
    const last = form.slots[form.slots.length - 1];
    form.slots.push({
        name: '',
        start_time: last?.end_time ?? '09:00',
        end_time: last?.end_time ?? '10:00',
        kind: 'activity',
        color: 'emerald',
    });
}
function removeSlot(i: number) {
    form.slots.splice(i, 1);
}

// --- Leaders ---
function addLeader() {
    form.leader_emails.push('');
}
function removeLeader(i: number) {
    form.leader_emails.splice(i, 1);
    if (form.leader_emails.length === 0) form.leader_emails.push('');
}

// --- Navigation ---
const step1Valid = computed(
    () =>
        form.name.trim().length > 0 &&
        !!form.start_date &&
        !!form.end_date &&
        new Date(form.end_date) >= new Date(form.start_date),
);
const canNext = computed(() => (step.value === 0 ? step1Valid.value : true));

function next() {
    if (step.value === 0) seedTrips();
    if (step.value < steps.length - 1) step.value++;
}
function back() {
    if (step.value > 0) step.value--;
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            leader_emails: data.leader_emails.map((e) => e.trim()).filter((e) => e.length > 0),
        }))
        .post(storeCamp().url);
}
</script>

<template>
    <Head title="Nový tábor" />

    <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg" :class="colorStyle(form.color).chip">
                <component :is="campIcon(form.icon)" class="size-5" />
            </div>
            <Heading title="Nový tábor" description="Sprievodca ťa prevedie nastavením tábora." />
        </div>

        <!-- Stepper -->
        <div class="flex items-center">
            <template v-for="(label, i) in steps" :key="label">
                <div class="flex flex-col items-center gap-1">
                    <div
                        class="flex size-8 items-center justify-center rounded-full border text-sm font-semibold transition-colors"
                        :class="
                            i < step
                                ? 'border-primary bg-primary text-primary-foreground'
                                : i === step
                                  ? 'border-primary text-primary'
                                  : 'border-border text-muted-foreground'
                        "
                    >
                        <Check v-if="i < step" class="size-4" />
                        <span v-else>{{ i + 1 }}</span>
                    </div>
                    <span class="text-[11px]" :class="i === step ? 'font-medium text-foreground' : 'text-muted-foreground'">
                        {{ label }}
                    </span>
                </div>
                <div v-if="i < steps.length - 1" class="mx-2 h-px flex-1" :class="i < step ? 'bg-primary' : 'bg-border'" />
            </template>
        </div>

        <div class="rounded-xl border bg-card p-5">
            <!-- Step 1: basics -->
            <div v-show="step === 0" class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="w-name">Názov tábora</Label>
                    <Input id="w-name" v-model="form.name" placeholder="Napr. Farský tábor – 1. turnus" autofocus />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="w-year">Rok</Label>
                    <Input id="w-year" v-model="form.year" type="number" min="2000" max="2100" class="w-32" />
                    <InputError :message="form.errors.year" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="w-start">Začiatok</Label>
                        <Input id="w-start" v-model="form.start_date" type="date" />
                        <InputError :message="form.errors.start_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="w-end">Koniec</Label>
                        <Input id="w-end" v-model="form.end_date" type="date" />
                        <InputError :message="form.errors.end_date" />
                    </div>
                </div>
                <p v-if="rangeDays.length" class="flex items-center gap-1.5 text-sm text-muted-foreground">
                    <CalendarDays class="size-4" /> Tábor trvá {{ rangeDays.length }} dní.
                </p>
                <div class="grid gap-2">
                    <Label for="w-desc">Popis (voliteľné)</Label>
                    <Textarea id="w-desc" v-model="form.description" />
                </div>

                <div class="border-t pt-4">
                    <CampAppearanceFields
                        v-model:icon="form.icon"
                        v-model:color="form.color"
                        v-model:location="form.location"
                    />
                </div>

                <div v-if="libraries.length" class="grid gap-2">
                    <Label>Databáza aktivít</Label>
                    <Select
                        :model-value="form.activity_library_id === null ? 'new' : String(form.activity_library_id)"
                        @update:model-value="form.activity_library_id = $event === 'new' ? null : Number($event)"
                    >
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="new">Vytvoriť novú databázu</SelectItem>
                            <SelectItem v-for="l in libraries" :key="l.id" :value="String(l.id)">{{ l.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">Vedúci tábora získajú prístup k zvolenej databáze.</p>
                </div>
            </div>

            <!-- Step 2: daily blocks -->
            <div v-show="step === 1" class="grid gap-3">
                <p class="text-sm text-muted-foreground">
                    Denná časová kostra — rovnaká pre každý deň. „Pevné" bloky (obed, oddych) sú len oddychové;
                    do „programových" dopĺňaš aktivity.
                </p>
                <div
                    v-for="(slot, i) in form.slots"
                    :key="i"
                    class="grid grid-cols-[1fr_auto_auto] items-end gap-2 rounded-lg border p-2.5 sm:grid-cols-[1fr_5rem_5rem_8rem_auto_auto]"
                >
                    <div class="grid gap-1">
                        <Label class="text-xs">Názov</Label>
                        <Input v-model="slot.name" placeholder="Napr. BLOK I." class="h-8" />
                    </div>
                    <div class="grid gap-1">
                        <Label class="text-xs">Od</Label>
                        <Input v-model="slot.start_time" type="time" class="h-8" />
                    </div>
                    <div class="grid gap-1">
                        <Label class="text-xs">Do</Label>
                        <Input v-model="slot.end_time" type="time" class="h-8" />
                    </div>
                    <div class="grid gap-1">
                        <Label class="text-xs">Typ</Label>
                        <Select :model-value="slot.kind" @update:model-value="slot.kind = $event as 'fixed' | 'activity'">
                            <SelectTrigger class="h-8"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="activity">Programový</SelectItem>
                                <SelectItem value="fixed">Pevný</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-1">
                        <Label class="text-xs">Farba</Label>
                        <div class="flex h-8 items-center">
                            <Select :model-value="slot.color" @update:model-value="slot.color = $event as string">
                                <SelectTrigger class="h-8 w-16">
                                    <span class="size-3 rounded-full" :class="colorStyle(slot.color).dot" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in COLOR_NAMES" :key="c" :value="c">
                                        <span class="flex items-center gap-2">
                                            <span class="size-3 rounded-full" :class="colorStyle(c).dot" /> {{ c }}
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <Button type="button" variant="ghost" size="icon-sm" class="self-center" @click="removeSlot(i)">
                        <Trash2 class="text-destructive" />
                    </Button>
                </div>
                <Button type="button" variant="outline" class="w-fit" @click="addSlot">
                    <Plus /> Pridať blok
                </Button>
                <InputError :message="form.errors.slots" />
            </div>

            <!-- Step 3: trip days -->
            <div v-show="step === 2" class="grid gap-3">
                <p class="text-sm text-muted-foreground">
                    Označ dni, ktoré sú celodenné výlety. (Predvyplnené: utorky a štvrtky.)
                </p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <button
                        v-for="d in rangeDays"
                        :key="d.date"
                        type="button"
                        class="flex items-center justify-between gap-2 rounded-lg border p-3 text-left transition-colors"
                        :class="isTrip(d.date) ? 'border-fuchsia-400 bg-fuchsia-50 dark:bg-fuchsia-950/30' : 'hover:bg-accent'"
                        @click="toggleTrip(d.date)"
                    >
                        <span>
                            <span class="font-medium capitalize">{{ d.weekday }}</span>
                            <span class="ml-1 text-sm text-muted-foreground">{{ d.label }}</span>
                        </span>
                        <span
                            class="flex items-center gap-1 text-xs font-medium"
                            :class="isTrip(d.date) ? 'text-fuchsia-700 dark:text-fuchsia-300' : 'text-muted-foreground'"
                        >
                            <MapPin v-if="isTrip(d.date)" class="size-3.5" />
                            {{ isTrip(d.date) ? 'Výlet' : 'Bežný deň' }}
                        </span>
                    </button>
                </div>
                <p v-if="!rangeDays.length" class="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
                    Najprv zvoľ dátumy tábora.
                </p>
            </div>

            <!-- Step 4: leaders -->
            <div v-show="step === 3" class="grid gap-3">
                <p class="text-sm text-muted-foreground">
                    Pozvi ďalších animátorov e-mailom (voliteľné). Kto má účet, pridá sa hneď; ostatní dostanú pozvánku.
                    Odkaz na pripojenie a ďalších vedúcich pridáš aj neskôr.
                </p>
                <div v-for="(_, i) in form.leader_emails" :key="i" class="flex items-center gap-2">
                    <Input
                        v-model="form.leader_emails[i]"
                        type="email"
                        placeholder="animator@farnost.sk"
                        class="flex-1"
                    />
                    <Button type="button" variant="ghost" size="icon-sm" @click="removeLeader(i)">
                        <X />
                    </Button>
                </div>
                <Button type="button" variant="outline" class="w-fit" @click="addLeader">
                    <UserPlus /> Ďalší vedúci
                </Button>
                <InputError :message="form.errors.leader_emails" />
            </div>
        </div>

        <!-- Footer nav -->
        <div class="flex items-center justify-between">
            <Button
                v-if="step > 0"
                type="button"
                variant="outline"
                @click="back"
            >
                <ArrowLeft /> Späť
            </Button>
            <Button v-else type="button" variant="ghost" @click="router.get(campsIndex().url)">Zrušiť</Button>

            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Clock3 class="size-4" /> Krok {{ step + 1 }} z {{ steps.length }}
            </div>

            <Button v-if="step < steps.length - 1" type="button" :disabled="!canNext" @click="next">
                Ďalej <ArrowRight />
            </Button>
            <Button v-else type="button" :disabled="form.processing || !step1Valid" @click="submit">
                <Tent /> Vytvoriť tábor
            </Button>
        </div>
    </div>
</template>
