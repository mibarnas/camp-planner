<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { store as storeActivity, update as updateActivity } from '@/routes/activities';
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
import InputError from '@/components/InputError.vue';
import { CATEGORIES, categoryColor } from '@/lib/campColors';
import type { Activity } from '@/types/camp';

const props = defineProps<{
    open: boolean;
    activity?: Activity | null;
    libraryId?: number | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [];
}>();

const form = useForm({
    activity_library_id: null as number | null,
    name: '',
    category: 'game',
    description: '',
    default_duration: 60,
    color: 'emerald' as string | null,
    materials: '',
});

watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }
        const a = props.activity;
        form.clearErrors();
        form.defaults({
            activity_library_id: props.libraryId ?? null,
            name: a?.name ?? '',
            category: a?.category ?? 'game',
            description: a?.description ?? '',
            default_duration: a?.default_duration ?? 60,
            color: a?.color ?? categoryColor(a?.category ?? 'game'),
            materials: a?.materials ?? '',
        });
        form.reset();
    },
);

function onCategoryChange(value: string) {
    form.category = value;
    if (!props.activity) {
        form.color = categoryColor(value);
    }
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
        },
    };

    if (props.activity) {
        form.put(updateActivity(props.activity.id).url, options);
    } else {
        form.post(storeActivity().url, options);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ activity ? 'Upraviť aktivitu' : 'Nová aktivita' }}</DialogTitle>
                <DialogDescription>
                    Aktivity sú znovupoužiteľné naprieč tábormi. Vyberáš ich pri plánovaní programu.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="activity-name">Názov</Label>
                    <Input id="activity-name" v-model="form.name" required autofocus />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label>Kategória</Label>
                        <Select :model-value="form.category" @update:model-value="onCategoryChange($event as string)">
                            <SelectTrigger>
                                <SelectValue placeholder="Vyber kategóriu" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in CATEGORIES" :key="c.value" :value="c.value">
                                    {{ c.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="activity-duration">Dĺžka (min)</Label>
                        <Input
                            id="activity-duration"
                            v-model="form.default_duration"
                            type="number"
                            min="5"
                            max="1440"
                        />
                        <InputError :message="form.errors.default_duration" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="activity-description">Popis</Label>
                    <Textarea id="activity-description" v-model="form.description" class="min-h-24" />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="activity-materials">Potrebný materiál</Label>
                    <Textarea id="activity-materials" v-model="form.materials" class="min-h-16" />
                    <InputError :message="form.errors.materials" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Zrušiť</Button>
                    <Button type="submit" :disabled="form.processing">Uložiť</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
