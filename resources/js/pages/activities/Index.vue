<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, Clock, Copy, Crown, Download, LayoutList, Library, Link2, Package, Pencil, Plus, Settings2, Tag, Trash2, Upload, Users, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import ActivityDetailDialog from '@/components/camp/ActivityDetailDialog.vue';
import ActivityFormDialog from '@/components/camp/ActivityFormDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { categoryById, colorStyle, COLOR_NAMES } from '@/lib/campColors';
import { destroy as destroyActivity, duplicate as duplicateActivity, index as activitiesIndex } from '@/routes/activities';
import { store as storeCategory, update as updateCategory, destroy as destroyCategory } from '@/routes/categories';
import {
    store as storeLibrary,
    update as updateLibrary,
    destroy as destroyLibrary,
    exportMethod as exportLibrary,
    importMethod as importLibrary,
} from '@/routes/libraries';
import { store as storeLibraryMember, destroy as destroyLibraryMember } from '@/routes/libraries/members';
import { store as storeShareLink, destroy as destroyShareLink } from '@/routes/libraries/shareLink';
import type { Activity, ActivityCategory, ActivityLibrary, LibraryMember } from '@/types/camp';

const props = defineProps<{
    libraries: ActivityLibrary[];
    selectedLibraryId: number | null;
    shareLink: string | null;
    activities: Activity[];
    members: LibraryMember[];
    categories: ActivityCategory[];
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
const activeCategory = ref<number | 'all' | 'none'>('all');

const filtered = computed(() => {
    if (activeCategory.value === 'all') {
return props.activities;
}

    if (activeCategory.value === 'none') {
return props.activities.filter((a) => a.category_id === null);
}

    return props.activities.filter((a) => a.category_id === activeCategory.value);
});

const usedCategories = computed(() =>
    props.categories.filter((c) => props.activities.some((a) => a.category_id === c.id)),
);
const hasUncategorised = computed(() => props.activities.some((a) => a.category_id === null));

function openNew() {
    editing.value = null;
    dialogOpen.value = true;
}
function openEdit(activity: Activity) {
    editing.value = activity;
    dialogOpen.value = true;
}
// --- Detail modal ---
const detailOpen = ref(false);
const detailActivity = ref<Activity | null>(null);
function openDetail(activity: Activity) {
    detailActivity.value = activity;
    detailOpen.value = true;
}
function onDetailEdit(activity: Activity) {
    detailOpen.value = false;
    openEdit(activity);
}
function onDetailDuplicate(activity: Activity) {
    router.post(duplicateActivity(activity.id).url, {}, { preserveScroll: true });
    detailOpen.value = false;
}
function onDetailRemove(activity: Activity) {
    if (!confirm(`Odstrániť aktivitu „${activity.name}“?`)) {
return;
}

    router.delete(destroyActivity(activity.id).url, {
        preserveScroll: true,
        onSuccess: () => (detailOpen.value = false),
    });
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

// --- Library settings (rename, members, categories, delete) ---
const settingsOpen = ref(false);
const renameForm = useForm({ name: '' });
const memberForm = useForm({ email: '' });
const categoryForm = useForm({ name: '', color: 'emerald' as string });
const editingCategoryId = ref<number | null>(null);

// --- Share link ---
const shareCopied = ref(false);
function createShareLink() {
    if (selectedLibrary.value) {
router.post(storeShareLink(selectedLibrary.value.id).url, {}, { preserveScroll: true });
}
}
function revokeShareLink() {
    if (!selectedLibrary.value) {
return;
}

    if (!confirm('Zrušiť zdieľateľný odkaz?')) {
return;
}

    router.delete(destroyShareLink(selectedLibrary.value.id).url, { preserveScroll: true });
}
async function copyShareLink() {
    if (!props.shareLink) {
return;
}

    try {
        await navigator.clipboard.writeText(props.shareLink);
        shareCopied.value = true;
        setTimeout(() => (shareCopied.value = false), 1500);
    } catch {
        window.prompt('Skopíruj odkaz:', props.shareLink);
    }
}

// --- Export / import ---
const importForm = useForm<{ file: File | null }>({ file: null });
const importInput = ref<HTMLInputElement | null>(null);
function exportJson() {
    if (selectedLibrary.value) {
window.location.href = exportLibrary(selectedLibrary.value.id).url;
}
}
function onImportFile(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (!file || !selectedLibrary.value) {
return;
}

    importForm.file = file;
    importForm.post(importLibrary(selectedLibrary.value.id).url, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            importForm.reset();

            if (importInput.value) {
importInput.value.value = '';
}
        },
    });
}

function openSettings() {
    if (!selectedLibrary.value) {
return;
}

    renameForm.clearErrors();
    renameForm.name = selectedLibrary.value.name;
    memberForm.clearErrors();
    memberForm.reset();
    resetCategoryForm();
    settingsOpen.value = true;
}
function submitRename() {
    if (!selectedLibrary.value) {
return;
}

    renameForm.put(updateLibrary(selectedLibrary.value.id).url, { preserveScroll: true });
}
function submitMember() {
    if (!selectedLibrary.value) {
return;
}

    memberForm.post(storeLibraryMember(selectedLibrary.value.id).url, {
        preserveScroll: true,
        onSuccess: () => memberForm.reset(),
    });
}
function removeMember(member: LibraryMember) {
    if (!selectedLibrary.value) {
return;
}

    if (!confirm(`Odobrať ${member.name}?`)) {
return;
}

    router.delete(
        destroyLibraryMember({ library: selectedLibrary.value.id, user: member.id }).url,
        { preserveScroll: true },
    );
}
function deleteLibrary() {
    if (!selectedLibrary.value) {
return;
}

    if (!confirm(`Zmazať databázu „${selectedLibrary.value.name}“ aj so všetkými aktivitami? Táto akcia je nezvratná.`)) {
return;
}

    router.delete(destroyLibrary(selectedLibrary.value.id).url);
}

// --- Category management ---
function resetCategoryForm() {
    editingCategoryId.value = null;
    categoryForm.clearErrors();
    categoryForm.defaults({ name: '', color: 'emerald' });
    categoryForm.reset();
}
function startEditCategory(cat: ActivityCategory) {
    editingCategoryId.value = cat.id;
    categoryForm.clearErrors();
    categoryForm.defaults({ name: cat.name, color: cat.color ?? 'slate' });
    categoryForm.reset();
}
function submitCategory() {
    if (!selectedLibrary.value) {
return;
}

    const opts = { preserveScroll: true, onSuccess: () => resetCategoryForm() };

    if (editingCategoryId.value) {
        categoryForm.put(updateCategory(editingCategoryId.value).url, opts);
    } else {
        categoryForm.post(storeCategory(selectedLibrary.value.id).url, opts);
    }
}
function removeCategory(cat: ActivityCategory) {
    if (!confirm(`Zmazať kategóriu „${cat.name}“? Aktivity zostanú, len bez kategórie.`)) {
return;
}

    router.delete(destroyCategory(cat.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingCategoryId.value === cat.id) {
resetCategoryForm();
}
        },
    });
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
                    :key="c.id"
                    class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors"
                    :class="activeCategory === c.id ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                    @click="activeCategory = c.id"
                >
                    <span class="size-2.5 rounded-full" :class="colorStyle(c.color).dot" />
                    {{ c.name }}
                </button>
                <button
                    v-if="hasUncategorised"
                    class="rounded-full border px-3 py-1 text-sm transition-colors"
                    :class="activeCategory === 'none' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                    @click="activeCategory = 'none'"
                >
                    Bez kategórie
                </button>
                <span class="ml-auto flex items-center gap-1 text-sm text-muted-foreground">
                    <Users class="size-4" /> {{ members.length }} členov
                </span>
            </div>

            <div v-if="filtered.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="activity in filtered"
                    :key="activity.id"
                    role="button"
                    tabindex="0"
                    class="group cursor-pointer overflow-hidden border-l-4 py-0 transition-all hover:-translate-y-0.5 hover:shadow-md"
                    :class="colorStyle(activity.color).cell"
                    @click="openDetail(activity)"
                    @keydown.enter="openDetail(activity)"
                >
                    <CardContent class="flex h-full flex-col gap-2 bg-card/60 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="size-3 shrink-0 rounded-full" :class="colorStyle(activity.color).dot" />
                                <h3 class="leading-tight font-semibold">{{ activity.name }}</h3>
                            </div>
                            <div class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                <Button variant="ghost" size="icon-sm" @click.stop="openEdit(activity)">
                                    <Pencil />
                                </Button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge
                                v-if="categoryById(categories, activity.category_id)"
                                variant="secondary"
                                :class="colorStyle(categoryById(categories, activity.category_id)!.color).chip"
                            >
                                {{ categoryById(categories, activity.category_id)!.name }}
                            </Badge>
                            <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                <Clock class="size-3" /> {{ activity.default_duration }} min
                            </span>
                        </div>
                        <p v-if="activity.description" class="line-clamp-3 text-sm text-muted-foreground">
                            {{ activity.description }}
                        </p>
                        <div class="mt-auto flex items-center gap-3 pt-1 text-[11px] text-muted-foreground">
                            <span v-if="activity.materials" class="flex items-center gap-1">
                                <Package class="size-3" /> materiál
                            </span>
                            <span v-if="(activity.usage_count ?? 0) > 0" class="flex items-center gap-1">
                                <LayoutList class="size-3" /> {{ activity.usage_count }}× v programe
                            </span>
                        </div>
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

    <ActivityFormDialog
        v-model:open="dialogOpen"
        :activity="editing"
        :library-id="selectedLibraryId"
        :categories="categories"
    />

    <ActivityDetailDialog
        v-model:open="detailOpen"
        :activity="detailActivity"
        :categories="categories"
        :can-manage="!!selectedLibrary"
        @edit="onDetailEdit"
        @duplicate="onDetailDuplicate"
        @remove="onDetailRemove"
    />

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
                <DialogDescription>Kategórie, členovia a nastavenia databázy aktivít.</DialogDescription>
            </DialogHeader>

            <div class="grid max-h-[65vh] gap-5 overflow-y-auto px-1">
                <form v-if="selectedLibrary?.is_owner" class="flex items-end gap-2" @submit.prevent="submitRename">
                    <div class="grid flex-1 gap-2">
                        <Label for="lib-rename">Názov</Label>
                        <Input id="lib-rename" v-model="renameForm.name" />
                        <InputError :message="renameForm.errors.name" />
                    </div>
                    <Button type="submit" variant="outline" :disabled="renameForm.processing">Premenovať</Button>
                </form>

                <!-- Categories (tags) -->
                <div class="grid gap-2">
                    <p class="flex items-center gap-1.5 text-sm font-medium">
                        <Tag class="size-4" /> Kategórie ({{ categories.length }})
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="c in categories"
                            :key="c.id"
                            class="group/cat flex items-center gap-1.5 rounded-full border py-1 pr-1 pl-2.5 text-sm"
                            :class="editingCategoryId === c.id ? 'border-primary' : ''"
                        >
                            <span class="size-2.5 rounded-full" :class="colorStyle(c.color).dot" />
                            {{ c.name }}
                            <button class="rounded-full p-0.5 hover:bg-accent" title="Upraviť" @click="startEditCategory(c)">
                                <Pencil class="size-3" />
                            </button>
                            <button class="rounded-full p-0.5 hover:bg-accent" title="Zmazať" @click="removeCategory(c)">
                                <X class="size-3 text-destructive" />
                            </button>
                        </span>
                        <span v-if="!categories.length" class="text-sm text-muted-foreground">Zatiaľ žiadne kategórie.</span>
                    </div>

                    <form class="mt-1 flex flex-wrap items-end gap-2 rounded-lg border bg-muted/30 p-2.5" @submit.prevent="submitCategory">
                        <div class="grid flex-1 gap-1">
                            <Label for="cat-name" class="text-xs">{{ editingCategoryId ? 'Upraviť kategóriu' : 'Nová kategória' }}</Label>
                            <Input id="cat-name" v-model="categoryForm.name" placeholder="Napr. Hra" class="h-8" required />
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <button
                                v-for="col in COLOR_NAMES"
                                :key="col"
                                type="button"
                                class="size-5 rounded-full border-2 transition"
                                :class="[colorStyle(col).dot, categoryForm.color === col ? 'border-foreground' : 'border-transparent']"
                                :title="col"
                                @click="categoryForm.color = col"
                            />
                        </div>
                        <div class="flex gap-1">
                            <Button type="submit" size="sm" :disabled="categoryForm.processing">
                                {{ editingCategoryId ? 'Uložiť' : 'Pridať' }}
                            </Button>
                            <Button v-if="editingCategoryId" type="button" size="sm" variant="outline" @click="resetCategoryForm">
                                Nová
                            </Button>
                        </div>
                        <InputError class="w-full" :message="categoryForm.errors.name" />
                    </form>
                </div>

                <!-- Shareable link -->
                <div class="grid gap-2 rounded-lg border p-3">
                    <p class="flex items-center gap-1.5 text-sm font-medium">
                        <Link2 class="size-4" /> Zdieľateľný odkaz
                    </p>
                    <template v-if="shareLink">
                        <div class="flex items-center gap-1">
                            <Input :model-value="shareLink" readonly class="h-8 flex-1 text-xs" @focus="($event.target as HTMLInputElement).select()" />
                            <Button variant="outline" size="sm" @click="copyShareLink">
                                <component :is="shareCopied ? Check : Copy" />
                                {{ shareCopied ? 'Skopírované' : 'Kopírovať' }}
                            </Button>
                            <Button v-if="selectedLibrary?.is_owner" variant="ghost" size="icon-sm" title="Zrušiť" @click="revokeShareLink">
                                <Trash2 class="text-destructive" />
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground">Ktokoľvek s odkazom sa môže pridať k databáze aktivít.</p>
                    </template>
                    <template v-else-if="selectedLibrary?.is_owner">
                        <Button variant="outline" size="sm" class="w-fit" @click="createShareLink">
                            <Link2 /> Vytvoriť odkaz
                        </Button>
                    </template>
                </div>

                <!-- Export / import -->
                <div class="grid gap-2 rounded-lg border p-3">
                    <p class="text-sm font-medium">Zálohovanie (JSON)</p>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="exportJson">
                            <Download /> Exportovať
                        </Button>
                        <Button variant="outline" size="sm" :disabled="importForm.processing" @click="importInput?.click()">
                            <Upload /> Importovať
                        </Button>
                        <input ref="importInput" type="file" accept="application/json,.json" class="hidden" @change="onImportFile" />
                    </div>
                    <InputError :message="importForm.errors.file" />
                    <p class="text-xs text-muted-foreground">Prenes aktivity medzi databázami cez JSON súbor.</p>
                </div>

                <!-- Members -->
                <div class="grid gap-2">
                    <p class="flex items-center gap-1.5 text-sm font-medium">
                        <Users class="size-4" /> Členovia ({{ members.length }})
                    </p>
                    <form v-if="selectedLibrary?.is_owner" class="flex items-end gap-2" @submit.prevent="submitMember">
                        <div class="grid flex-1 gap-1">
                            <Input v-model="memberForm.email" type="email" placeholder="animator@farnost.sk" class="h-8" />
                            <InputError :message="memberForm.errors.email" />
                        </div>
                        <Button type="submit" size="sm" :disabled="memberForm.processing">Pridať</Button>
                    </form>
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
                    <p class="text-xs text-muted-foreground">Vedúci prepojených táborov sa pridávajú automaticky.</p>
                </div>
            </div>

            <DialogFooter v-if="selectedLibrary?.is_owner" class="sm:justify-start">
                <Button type="button" variant="ghost" class="text-destructive" @click="deleteLibrary">
                    <Trash2 /> Zmazať databázu
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
