<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Tag, Trophy, UserRound, X } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { COLOR_NAMES, colorStyle } from '@/lib/campColors';
import { index as campsIndex, show } from '@/routes/camps';
import {
    destroy as destroyGroup,
    store as storeGroup,
    update as updateGroup,
} from '@/routes/groups';
import {
    destroy as destroyType,
    store as storeType,
    update as updateType,
} from '@/routes/groupTypes';
import type { CampGroup, CampLeader, GroupType } from '@/types/camp';

const props = defineProps<{
    camp: { id: number; name: string };
    groups: CampGroup[];
    groupTypes: GroupType[];
    leaders: CampLeader[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tábory', href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: 'Skupiny', href: '#' },
        ],
    });
});

const typeById = computed(
    () => new Map(props.groupTypes.map((t) => [t.id, t])),
);
const leaderById = computed(() => new Map(props.leaders.map((l) => [l.id, l])));

// --- Groups ---
const editingId = ref<number | null>(null);
const form = useForm({
    name: '',
    group_type_id: null as number | null,
    competes: true,
    color: 'emerald' as string,
    leader_ids: [] as number[],
});

function startNew() {
    editingId.value = null;
    form.clearErrors();
    form.defaults({
        name: '',
        group_type_id: null,
        competes: true,
        color: 'emerald',
        leader_ids: [],
    });
    form.reset();
}

function startEdit(group: CampGroup) {
    editingId.value = group.id;
    form.clearErrors();
    form.defaults({
        name: group.name,
        group_type_id: group.group_type_id,
        competes: group.competes,
        color: group.color ?? 'emerald',
        leader_ids: [...group.leader_ids],
    });
    form.reset();
}

function toggleLeader(leaderId: number, checked: boolean) {
    form.leader_ids = checked
        ? [...form.leader_ids, leaderId]
        : form.leader_ids.filter((id) => id !== leaderId);
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => startNew() };

    if (editingId.value) {
        form.put(
            updateGroup({ camp: props.camp.id, group: editingId.value }).url,
            options,
        );
    } else {
        form.post(storeGroup(props.camp.id).url, options);
    }
}

function removeGroup(group: CampGroup) {
    if (
        !confirm(
            `Odstrániť skupinu „${group.name}"? Zmažú sa aj jej zapísané body.`,
        )
    ) {
        return;
    }

    router.delete(destroyGroup({ camp: props.camp.id, group: group.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId.value === group.id) {
                startNew();
            }
        },
    });
}

// --- Group types ---
const typeForm = useForm({ name: '', color: 'slate' as string });
const editingTypeId = ref<number | null>(null);

function startNewType() {
    editingTypeId.value = null;
    typeForm.clearErrors();
    typeForm.defaults({ name: '', color: 'slate' });
    typeForm.reset();
}

function startEditType(type: GroupType) {
    editingTypeId.value = type.id;
    typeForm.clearErrors();
    typeForm.defaults({ name: type.name, color: type.color ?? 'slate' });
    typeForm.reset();
}

function submitType() {
    const options = { preserveScroll: true, onSuccess: () => startNewType() };

    if (editingTypeId.value) {
        typeForm.put(
            updateType({ camp: props.camp.id, groupType: editingTypeId.value })
                .url,
            options,
        );
    } else {
        typeForm.post(storeType(props.camp.id).url, options);
    }
}

function removeType(type: GroupType) {
    if (
        !confirm(
            `Odstrániť typ „${type.name}"? Skupiny ostanú, len stratia označenie.`,
        )
    ) {
        return;
    }

    router.delete(
        destroyType({ camp: props.camp.id, groupType: type.id }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                if (editingTypeId.value === type.id) {
                    startNewType();
                }
            },
        },
    );
}
</script>

<template>
    <Head :title="`Skupiny — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Skupiny"
            description="Rozdelenie tábora — oddiely, program, fotografi. Súťažiace skupiny zbierajú body."
        />

        <!-- Group types -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Tag class="size-4" /> Typy skupín
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        v-for="type in groupTypes"
                        :key="type.id"
                        class="flex items-center gap-1.5 rounded-full border py-1 pr-1 pl-3 text-sm"
                        :class="
                            editingTypeId === type.id ? 'border-primary' : ''
                        "
                    >
                        <span
                            class="size-2.5 rounded-full"
                            :class="colorStyle(type.color).dot"
                        />
                        {{ type.name }}
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            title="Premenovať"
                            @click="startEditType(type)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            title="Odstrániť"
                            @click="removeType(type)"
                        >
                            <X class="text-destructive" />
                        </Button>
                    </span>
                    <span
                        v-if="!groupTypes.length"
                        class="text-sm text-muted-foreground"
                    >
                        Zatiaľ žiadne typy — pridaj napr. „Detské skupiny" alebo
                        „Fotografi".
                    </span>
                </div>

                <form
                    class="flex flex-wrap items-end gap-2"
                    @submit.prevent="submitType"
                >
                    <div class="grid gap-2">
                        <Label for="type-name">{{
                            editingTypeId ? 'Upraviť typ' : 'Nový typ'
                        }}</Label>
                        <Input
                            id="type-name"
                            v-model="typeForm.name"
                            placeholder="Napr. Detské skupiny"
                            required
                        />
                        <InputError :message="typeForm.errors.name" />
                    </div>
                    <Select
                        :model-value="typeForm.color"
                        @update:model-value="typeForm.color = $event as string"
                    >
                        <SelectTrigger class="w-36"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="c in COLOR_NAMES"
                                :key="c"
                                :value="c"
                            >
                                <span class="flex items-center gap-2">
                                    <span
                                        class="size-3 rounded-full"
                                        :class="colorStyle(c).dot"
                                    />
                                    {{ c }}
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" :disabled="typeForm.processing">
                        <Plus v-if="!editingTypeId" />
                        {{ editingTypeId ? 'Uložiť' : 'Pridať' }}
                    </Button>
                    <Button
                        v-if="editingTypeId"
                        type="button"
                        variant="outline"
                        @click="startNewType"
                    >
                        Zrušiť
                    </Button>
                </form>
            </CardContent>
        </Card>

        <!-- Groups -->
        <div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
            <div
                class="grid content-start gap-2 sm:grid-cols-2 2xl:grid-cols-3"
            >
                <div
                    v-for="group in groups"
                    :key="group.id"
                    class="grid content-start gap-2 rounded-xl border p-3"
                    :class="editingId === group.id ? 'border-primary' : ''"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="size-3 shrink-0 rounded-full"
                            :class="colorStyle(group.color).dot"
                        />
                        <p class="font-medium">{{ group.name }}</p>
                        <Badge v-if="group.group_type_id" variant="outline">
                            {{ typeById.get(group.group_type_id)?.name }}
                        </Badge>
                        <Badge
                            v-if="group.competes"
                            variant="secondary"
                            class="gap-1"
                        >
                            <Trophy class="size-3" /> súťaží
                        </Badge>
                        <div class="ml-auto flex gap-1">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Upraviť"
                                @click="startEdit(group)"
                            >
                                <Pencil />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Odstrániť"
                                @click="removeGroup(group)"
                            >
                                <X class="text-destructive" />
                            </Button>
                        </div>
                    </div>
                    <div
                        v-if="group.leader_ids.length"
                        class="flex flex-wrap gap-1.5"
                    >
                        <Badge
                            v-for="leaderId in group.leader_ids"
                            :key="leaderId"
                            variant="outline"
                            class="gap-1 font-normal"
                        >
                            <UserRound class="size-3" />
                            {{ leaderById.get(leaderId)?.name }}
                        </Badge>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">
                        Bez priradených vedúcich.
                    </p>
                </div>
                <p
                    v-if="!groups.length"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground sm:col-span-2 2xl:col-span-3"
                >
                    Zatiaľ žiadne skupiny.
                </p>
            </div>

            <form
                class="grid content-start gap-3 rounded-xl border bg-muted/30 p-3"
                @submit.prevent="submit"
            >
                <p class="text-sm font-medium">
                    {{ editingId ? 'Upraviť skupinu' : 'Pridať skupinu' }}
                </p>
                <div class="grid gap-2">
                    <Label for="group-name">Názov</Label>
                    <Input
                        id="group-name"
                        v-model="form.name"
                        placeholder="Napr. Levíčatá"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Typ</Label>
                        <Select
                            :model-value="
                                form.group_type_id === null
                                    ? 'none'
                                    : String(form.group_type_id)
                            "
                            @update:model-value="
                                form.group_type_id =
                                    $event === 'none' ? null : Number($event)
                            "
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Bez typu</SelectItem>
                                <SelectItem
                                    v-for="t in groupTypes"
                                    :key="t.id"
                                    :value="String(t.id)"
                                >
                                    {{ t.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Farba</Label>
                        <Select
                            :model-value="form.color"
                            @update:model-value="form.color = $event as string"
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="c in COLOR_NAMES"
                                    :key="c"
                                    :value="c"
                                >
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-3 rounded-full"
                                            :class="colorStyle(c).dot"
                                        />
                                        {{ c }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <label
                    class="flex items-center gap-3 rounded-lg border bg-background p-3"
                >
                    <Checkbox
                        :model-value="form.competes"
                        @update:model-value="form.competes = $event === true"
                    />
                    <span>
                        <span class="font-medium">Súťaží v bodovaní</span>
                        <span class="block text-xs text-muted-foreground">
                            Zbiera body a je v rebríčku.
                        </span>
                    </span>
                </label>

                <div class="grid gap-2">
                    <Label>Vedúci skupiny</Label>
                    <div
                        class="grid max-h-64 gap-1 overflow-y-auto rounded-lg border bg-background p-2"
                    >
                        <label
                            v-for="leader in leaders"
                            :key="leader.id"
                            class="flex items-center gap-2 rounded px-1.5 py-1 text-sm hover:bg-muted"
                        >
                            <Checkbox
                                :model-value="
                                    form.leader_ids.includes(leader.id)
                                "
                                @update:model-value="
                                    toggleLeader(leader.id, $event === true)
                                "
                            />
                            {{ leader.name }}
                            <span
                                v-if="!leader.user_id"
                                class="text-xs text-muted-foreground"
                                >(bez účtu)</span
                            >
                        </label>
                        <p
                            v-if="!leaders.length"
                            class="p-2 text-center text-xs text-muted-foreground"
                        >
                            Najprv pridaj vedúcich.
                        </p>
                    </div>
                </div>

                <div class="flex gap-2 pt-1">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1"
                    >
                        <Plus v-if="!editingId" />
                        {{ editingId ? 'Uložiť' : 'Pridať' }}
                    </Button>
                    <Button
                        v-if="editingId"
                        type="button"
                        variant="outline"
                        @click="startNew"
                        >Nová</Button
                    >
                </div>
            </form>
        </div>
    </div>
</template>
