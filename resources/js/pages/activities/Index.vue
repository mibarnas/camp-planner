<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Crown, Library, Pencil, Plus, Settings2, Trash2, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import ActivityFormDialog from '@/components/camp/ActivityFormDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import InputError from '@/components/InputError.vue';
import { destroy as destroyActivity, index as activitiesIndex } from '@/routes/activities';
import { store as storeLibrary, update as updateLibrary, destroy as destroyLibrary } from '@/routes/libraries';
import { store as storeLibraryMember, destroy as destroyLibraryMember } from '@/routes/libraries/members';
import { CATEGORIES, categoryColor, categoryLabel, colorStyle } from '@/lib/campColors';
import type { Activity, ActivityLibrary, LibraryMember } from '@/types/camp';

const props = defineProps<{
    libraries: ActivityLibrary[];
    selectedLibraryId: number | null;
    activities: Activity[];
    members: LibraryMember[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Aktivity', href: '/activities' }],
    },
});

const selectedLibrary = computed(
    () => props.libraries.find((l) => l.id === props.selectedLibraryId) ?? null,
);

function switchLibrary(id: string) {
    router.get(activitiesIndex().url, { library: id }, { preserveState: false });
}

// --- Activity CRUD ---
const dialogOpen = ref(false);
const editing = ref<Activity | null>(null);
const activeCategory = ref<string>('all');

const filtered = computed(() => {
    if (activeCategory.value === 'all') return props.activities;
    return props.activities.filter((a) => a.category === activeCategory.value);
});

const usedCategories = computed(() =>
    CATEGORIES.filter((c) => props.activities.some((a) => a.category === c.value)),
);

function openNew() {
    editing.value = null;
    dialogOpen.value = true;
}
function openEdit(activity: Activity) {
    editing.value = activity;
    dialogOpen.value = true;
}
function remove(activity: Activity) {
    if (!confirm(`Odstrániť aktivitu „${activity.name}“?`)) return;
    router.delete(destroyActivity(activity.id).url, { preserveScroll: true });
}

// --- New library ---
const newLibraryOpen = ref(false);
const newLibraryForm = useForm({ name: '' });
function submitNewLibrary() {
    newLibraryForm.post(storeLibrary().url, {
        onSuccess: () => {
            newLibraryOpen.value = false;
            newLibraryForm.reset();
        },
    });
}

// --- Library settings (rename, members, delete) ---
const settingsOpen = ref(false);
const renameForm = useForm({ name: '' });
const memberForm = useForm({ email: '' });

function openSettings() {
    if (!selectedLibrary.value) return;
    renameForm.clearErrors();
    renameForm.name = selectedLibrary.value.name;
    memberForm.clearErrors();
    memberForm.reset();
    settingsOpen.value = true;
}
function submitRename() {
    if (!selectedLibrary.value) return;
    renameForm.put(updateLibrary(selectedLibrary.value.id).url, { preserveScroll: true });
}
function submitMember() {
    if (!selectedLibrary.value) return;
    memberForm.post(storeLibraryMember(selectedLibrary.value.id).url, {
        preserveScroll: true,
        onSuccess: () => memberForm.reset(),
    });
}
function removeMember(member: LibraryMember) {
    if (!selectedLibrary.value) return;
    if (!confirm(`Odobrať ${member.name}?`)) return;
    router.delete(
        destroyLibraryMember({ library: selectedLibrary.value.id, user: member.id }).url,
        { preserveScroll: true },
    );
}
function deleteLibrary() {
    if (!selectedLibrary.value) return;
    if (!confirm(`Zmazať databázu „${selectedLibrary.value.name}“ aj so všetkými aktivitami? Táto akcia je nezvratná.`)) return;
    router.delete(destroyLibrary(selectedLibrary.value.id).url);
}
</script>

<template>
    <Head title="Aktivity" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Databázy aktivít"
                description="Znovupoužiteľné aktivity zdieľané medzi vedúcimi. Vedúci tábora majú automaticky prístup k databáze svojho tábora."
            />
            <div class="flex flex-wrap items-center gap-2">
                <Select
                    v-if="libraries.length"
                    :model-value="selectedLibraryId === null ? '' : String(selectedLibraryId)"
                    @update:model-value="switchLibrary($event as string)"
                >
                    <SelectTrigger class="w-56">
                        <Library class="size-4 shrink-0 text-muted-foreground" />
                        <SelectValue placeholder="Vyber databázu" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="l in libraries" :key="l.id" :value="String(l.id)">
                            {{ l.name }} ({{ l.activities_count }})
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="outline" size="icon" title="Nová databáza" @click="newLibraryOpen = true">
                    <Plus />
                </Button>
                <Button v-if="selectedLibrary" variant="outline" @click="openSettings">
                    <Settings2 /> Databáza
                </Button>
                <Button v-if="selectedLibrary" @click="openNew">
                    <Plus /> Nová aktivita
                </Button>
            </div>
        </div>

        <template v-if="selectedLibrary">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    class="rounded-full border px-3 py-1 text-sm transition-colors"
                    :class="activeCategory === 'all' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                    @click="activeCategory = 'all'"
                >
                    Všetky ({{ activities.length }})
                </button>
                <button
                    v-for="c in usedCategories"
                    :key="c.value"
                    class="rounded-full border px-3 py-1 text-sm transition-colors"
                    :class="activeCategory === c.value ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                    @click="activeCategory = c.value"
                >
                    {{ c.label }}
                </button>
                <span class="ml-auto flex items-center gap-1 text-sm text-muted-foreground">
                    <Users class="size-4" /> {{ members.length }} členov
                </span>
            </div>

            <div v-if="filtered.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="activity in filtered" :key="activity.id" class="group py-0">
                    <CardContent class="flex flex-col gap-2 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="size-3 rounded-full" :class="colorStyle(activity.color ?? categoryColor(activity.category)).dot" />
                                <h3 class="leading-tight font-semibold">{{ activity.name }}</h3>
                            </div>
                            <div class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                <Button variant="ghost" size="icon-sm" @click="openEdit(activity)">
                                    <Pencil />
                                </Button>
                                <Button variant="ghost" size="icon-sm" @click="remove(activity)">
                                    <Trash2 class="text-destructive" />
                                </Button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" :class="colorStyle(activity.color ?? categoryColor(activity.category)).chip">
                                {{ categoryLabel(activity.category) }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">{{ activity.default_duration }} min</span>
                        </div>
                        <p v-if="activity.description" class="line-clamp-3 text-sm text-muted-foreground">
                            {{ activity.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="rounded-xl border border-dashed p-12 text-center text-muted-foreground">
                <p>Zatiaľ žiadne aktivity v tejto kategórii.</p>
                <Button variant="outline" class="mt-4" @click="openNew">
                    <Plus /> Pridať prvú aktivitu
                </Button>
            </div>
        </template>

        <div v-else class="rounded-xl border border-dashed p-12 text-center">
            <Library class="mx-auto size-10 text-muted-foreground" />
            <h3 class="mt-4 font-medium">Žiadna databáza aktivít</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                Vytvor databázu alebo sa pridaj do tábora — jeho databáza sa ti sprístupní automaticky.
            </p>
            <Button class="mt-4" @click="newLibraryOpen = true">
                <Plus /> Nová databáza
            </Button>
        </div>
    </div>

    <ActivityFormDialog v-model:open="dialogOpen" :activity="editing" :library-id="selectedLibraryId" />

    <!-- New library -->
    <Dialog v-model:open="newLibraryOpen">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Nová databáza aktivít</DialogTitle>
                <DialogDescription>Samostatná zbierka aktivít, ktorú môžeš zdieľať a prepojiť s tábormi.</DialogDescription>
            </DialogHeader>
            <form class="grid gap-2" @submit.prevent="submitNewLibrary">
                <Label for="lib-name">Názov</Label>
                <Input id="lib-name" v-model="newLibraryForm.name" required autofocus />
                <InputError :message="newLibraryForm.errors.name" />
                <DialogFooter class="mt-2">
                    <Button type="button" variant="outline" @click="newLibraryOpen = false">Zrušiť</Button>
                    <Button type="submit" :disabled="newLibraryForm.processing">Vytvoriť</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Library settings -->
    <Dialog v-model:open="settingsOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ selectedLibrary?.name }}</DialogTitle>
                <DialogDescription>Členovia a nastavenia databázy aktivít.</DialogDescription>
            </DialogHeader>

            <form v-if="selectedLibrary?.is_owner" class="flex items-end gap-2" @submit.prevent="submitRename">
                <div class="grid flex-1 gap-2">
                    <Label for="lib-rename">Názov</Label>
                    <Input id="lib-rename" v-model="renameForm.name" />
                    <InputError :message="renameForm.errors.name" />
                </div>
                <Button type="submit" variant="outline" :disabled="renameForm.processing">Premenovať</Button>
            </form>

            <form v-if="selectedLibrary?.is_owner" class="flex items-end gap-2" @submit.prevent="submitMember">
                <div class="grid flex-1 gap-2">
                    <Label for="lib-member-email">Pridať člena (e-mail existujúceho účtu)</Label>
                    <Input id="lib-member-email" v-model="memberForm.email" type="email" placeholder="animator@farnost.sk" />
                    <InputError :message="memberForm.errors.email" />
                </div>
                <Button type="submit" :disabled="memberForm.processing">Pridať</Button>
            </form>

            <div class="grid gap-2">
                <p class="text-sm font-medium text-muted-foreground">Členovia ({{ members.length }})</p>
                <div
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center justify-between gap-2 rounded-lg border p-2.5"
                >
                    <div class="min-w-0">
                        <p class="flex items-center gap-1.5 truncate text-sm font-medium">
                            {{ member.name }}
                            <Crown v-if="member.role === 'owner'" class="size-3.5 text-amber-500" />
                        </p>
                        <p class="truncate text-xs text-muted-foreground">{{ member.email }}</p>
                    </div>
                    <Button
                        v-if="selectedLibrary?.is_owner && member.role !== 'owner'"
                        variant="ghost"
                        size="icon-sm"
                        @click="removeMember(member)"
                    >
                        <Trash2 class="text-destructive" />
                    </Button>
                </div>
                <p class="text-xs text-muted-foreground">
                    Vedúci prepojených táborov sa pridávajú automaticky.
                </p>
            </div>

            <DialogFooter v-if="selectedLibrary?.is_owner" class="sm:justify-start">
                <Button type="button" variant="ghost" class="text-destructive" @click="deleteLibrary">
                    <Trash2 /> Zmazať databázu
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
