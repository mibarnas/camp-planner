<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Clock, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { COLOR_NAMES, colorStyle } from '@/lib/campColors';
import { store as storeSlot, update as updateSlot, destroy as destroySlot } from '@/routes/slots';
import type { TimeSlot } from '@/types/camp';

const props = defineProps<{ open: boolean; campId: number; slots: TimeSlot[] }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    start_time: '09:00',
    end_time: '10:00',
    kind: 'activity' as 'fixed' | 'activity',
    color: 'emerald' as string,
});

function startNew() {
    editingId.value = null;
    form.clearErrors();
    form.defaults({ name: '', start_time: '09:00', end_time: '10:00', kind: 'activity', color: 'emerald' });
    form.reset();
}

function startEdit(slot: TimeSlot) {
    editingId.value = slot.id;
    form.clearErrors();
    form.defaults({
        name: slot.name,
        start_time: slot.start_time,
        end_time: slot.end_time,
        kind: slot.kind,
        color: slot.color ?? 'emerald',
    });
    form.reset();
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => startNew() };

    if (editingId.value) {
        form.put(updateSlot(editingId.value).url, options);
    } else {
        form.post(storeSlot(props.campId).url, options);
    }
}

function remove(slot: TimeSlot) {
    if (!confirm(`Odstrániť blok „${slot.name}"? Zmažú sa aj jeho záznamy vo všetkých dňoch.`)) {
        return;
    }

    router.delete(destroySlot(slot.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId.value === slot.id) {
                startNew();
            }
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Časové bloky (stĺpce)</DialogTitle>
                <DialogDescription>
                    Denná časová kostra. „Pevné" bloky (obed, oddych) sú každý deň rovnaké; do „programových"
                    blokov dopĺňaš obsah po dňoch.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 sm:grid-cols-[1fr_1.1fr]">
                <div class="grid max-h-[55vh] content-start gap-2 overflow-y-auto pr-1">
                    <div
                        v-for="slot in slots"
                        :key="slot.id"
                        class="flex items-center justify-between gap-2 rounded-lg border p-2.5"
                        :class="editingId === slot.id ? 'border-primary' : ''"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="size-3 shrink-0 rounded-full" :class="colorStyle(slot.color).dot" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ slot.name }}</p>
                                <p class="flex items-center gap-1 text-xs text-muted-foreground">
                                    <Clock class="size-3" />
                                    {{ slot.start_time }}–{{ slot.end_time }}
                                    <span v-if="slot.kind === 'fixed'" class="ml-1 rounded bg-muted px-1">pevný</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <Button variant="ghost" size="icon-sm" @click="startEdit(slot)"><Pencil /></Button>
                            <Button variant="ghost" size="icon-sm" @click="remove(slot)">
                                <Trash2 class="text-destructive" />
                            </Button>
                        </div>
                    </div>
                    <p v-if="!slots.length" class="p-4 text-center text-sm text-muted-foreground">Žiadne bloky.</p>
                </div>

                <form class="grid content-start gap-3 rounded-lg border bg-muted/30 p-3" @submit.prevent="submit">
                    <p class="text-sm font-medium">{{ editingId ? 'Upraviť blok' : 'Pridať blok' }}</p>
                    <div class="grid gap-2">
                        <Label for="slot-name">Názov</Label>
                        <Input id="slot-name" v-model="form.name" placeholder="Napr. BLOK I." required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="grid gap-2">
                            <Label for="slot-start">Od</Label>
                            <Input id="slot-start" v-model="form.start_time" type="time" required />
                            <InputError :message="form.errors.start_time" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="slot-end">Do</Label>
                            <Input id="slot-end" v-model="form.end_time" type="time" required />
                            <InputError :message="form.errors.end_time" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="grid gap-2">
                            <Label>Typ</Label>
                            <Select :model-value="form.kind" @update:model-value="form.kind = $event as 'fixed' | 'activity'">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="activity">Programový</SelectItem>
                                    <SelectItem value="fixed">Pevný (denný)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label>Farba</Label>
                            <Select :model-value="form.color" @update:model-value="form.color = $event as string">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in COLOR_NAMES" :key="c" :value="c">
                                        <span class="flex items-center gap-2">
                                            <span class="size-3 rounded-full" :class="colorStyle(c).dot" />
                                            {{ c }}
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <Button type="submit" :disabled="form.processing" class="flex-1">
                            <Plus v-if="!editingId" />
                            {{ editingId ? 'Uložiť' : 'Pridať' }}
                        </Button>
                        <Button v-if="editingId" type="button" variant="outline" @click="startNew">Nový</Button>
                    </div>
                </form>
            </div>
        </DialogContent>
    </Dialog>
</template>
