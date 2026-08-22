<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Check, Pencil, Plus, UserRound, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import { COLOR_NAMES, colorStyle } from '@/lib/campColors';
import {
    destroy as destroyLeader,
    store as storeLeader,
    update as updateLeader,
} from '@/routes/leaders';
import type { CampLeader } from '@/types/camp';

const props = defineProps<{ campId: number; leaders: CampLeader[] }>();

const editingId = ref<number | null>(null);

const form = useForm({ name: '', color: 'slate' as string });

function startNew() {
    editingId.value = null;
    form.clearErrors();
    form.defaults({ name: '', color: 'slate' });
    form.reset();
}

function startEdit(leader: CampLeader) {
    editingId.value = leader.id;
    form.clearErrors();
    form.defaults({ name: leader.name, color: leader.color ?? 'slate' });
    form.reset();
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => startNew() };

    if (editingId.value) {
        form.put(
            updateLeader({ camp: props.campId, leader: editingId.value }).url,
            options,
        );
    } else {
        form.post(storeLeader(props.campId).url, options);
    }
}

function remove(leader: CampLeader) {
    if (!confirm(`Odstrániť vedúceho „${leader.name}"?`)) {
        return;
    }

    router.delete(
        destroyLeader({ camp: props.campId, leader: leader.id }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                if (editingId.value === leader.id) {
                    startNew();
                }
            },
        },
    );
}
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-[1fr_20rem]">
        <div class="grid content-start gap-2 sm:grid-cols-2 2xl:grid-cols-3">
            <div
                v-for="leader in leaders"
                :key="leader.id"
                class="flex items-center justify-between gap-2 rounded-lg border p-3"
                :class="editingId === leader.id ? 'border-primary' : ''"
            >
                <div class="flex min-w-0 items-center gap-2.5">
                    <span
                        class="flex size-7 shrink-0 items-center justify-center rounded-full"
                        :class="colorStyle(leader.color).chip"
                    >
                        <UserRound class="size-3.5" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ leader.name }}</p>
                        <p
                            v-if="leader.email"
                            class="truncate text-xs text-muted-foreground"
                        >
                            {{ leader.email }}
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <Badge
                        v-if="leader.user_id"
                        variant="secondary"
                        class="gap-1"
                    >
                        <Check class="size-3" /> účet
                    </Badge>
                    <Badge v-else variant="outline">bez účtu</Badge>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        title="Premenovať"
                        @click="startEdit(leader)"
                    >
                        <Pencil />
                    </Button>
                    <Button
                        v-if="!leader.user_id"
                        variant="ghost"
                        size="icon-sm"
                        title="Odstrániť"
                        @click="remove(leader)"
                    >
                        <X class="text-destructive" />
                    </Button>
                </div>
            </div>
            <p
                v-if="!leaders.length"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground sm:col-span-2 2xl:col-span-3"
            >
                Zatiaľ žiadni vedúci.
            </p>
        </div>

        <form
            class="grid content-start gap-3 rounded-lg border bg-muted/30 p-3"
            @submit.prevent="submit"
        >
            <p class="text-sm font-medium">
                {{ editingId ? 'Upraviť vedúceho' : 'Pridať vedúceho' }}
            </p>
            <div class="grid gap-2">
                <Label for="leader-name">Meno</Label>
                <Input
                    id="leader-name"
                    v-model="form.name"
                    placeholder="Napr. Katka M."
                    required
                />
                <InputError :message="form.errors.name" />
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
                    >Nový</Button
                >
            </div>
            <p class="text-xs text-muted-foreground">
                Vedúci bez účtu je len meno — dá sa ním označiť zodpovedný za
                aktivitu. Keď sa neskôr pripojí s rovnakým menom, jeho účet sa
                naviaže naň.
            </p>
        </form>
    </div>
</template>
