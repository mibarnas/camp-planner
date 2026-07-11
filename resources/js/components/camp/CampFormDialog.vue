<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { store as storeCamp } from '@/routes/camps';
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
import type { ActivityLibraryRef } from '@/types/camp';

const props = defineProps<{ open: boolean; libraries?: ActivityLibraryRef[] }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const currentYear = new Date().getFullYear();

const form = useForm({
    name: '',
    year: currentYear,
    description: '',
    start_date: '',
    end_date: '',
    activity_library_id: null as number | null,
});

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.clearErrors();
            form.reset();
            form.year = currentYear;
        }
    },
);

function submit() {
    form.post(storeCamp().url, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Nový tábor</DialogTitle>
                <DialogDescription>
                    Vytvorí sa denný časový rozvrh a dni medzi zvolenými dátumami. Utorky a štvrtky sa
                    predvyplnia ako výlety.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="camp-name">Názov (napr. „Plachta – 1. turnus")</Label>
                    <Input id="camp-name" v-model="form.name" required autofocus />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="camp-year">Rok</Label>
                    <Input id="camp-year" v-model="form.year" type="number" min="2000" max="2100" required />
                    <InputError :message="form.errors.year" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="camp-start">Začiatok</Label>
                        <Input id="camp-start" v-model="form.start_date" type="date" required />
                        <InputError :message="form.errors.start_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="camp-end">Koniec</Label>
                        <Input id="camp-end" v-model="form.end_date" type="date" required />
                        <InputError :message="form.errors.end_date" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="camp-desc">Popis (voliteľné)</Label>
                    <Textarea id="camp-desc" v-model="form.description" />
                    <InputError :message="form.errors.description" />
                </div>

                <div v-if="libraries?.length" class="grid gap-2">
                    <Label>Databáza aktivít</Label>
                    <Select
                        :model-value="form.activity_library_id === null ? 'new' : String(form.activity_library_id)"
                        @update:model-value="form.activity_library_id = $event === 'new' ? null : Number($event)"
                    >
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="new">Vytvoriť novú databázu</SelectItem>
                            <SelectItem v-for="l in libraries" :key="l.id" :value="String(l.id)">
                                {{ l.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        Vedúci tábora sa automaticky stanú členmi zvolenej databázy aktivít.
                    </p>
                    <InputError :message="form.errors.activity_library_id" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Zrušiť</Button>
                    <Button type="submit" :disabled="form.processing">Vytvoriť tábor</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
