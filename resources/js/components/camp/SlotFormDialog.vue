<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import ColorSelect from '@/components/ColorSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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
import { useI18n } from '@/i18n';
import { store as storeSlot, update as updateSlot } from '@/routes/slots';
import type { SlotKind, TimeSlot } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{ campId: number; editing: TimeSlot | null }>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    name: '',
    start_time: '09:00',
    end_time: '10:00',
    kind: 'activity' as SlotKind,
    color: 'emerald' as string,
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults({
        name: props.editing?.name ?? '',
        start_time: props.editing?.start_time ?? '09:00',
        end_time: props.editing?.end_time ?? '10:00',
        kind: props.editing?.kind ?? 'activity',
        color: props.editing?.color ?? 'emerald',
    });
    form.reset();
});

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    if (props.editing) {
        form.put(updateSlot(props.editing.id).url, options);
    } else {
        form.post(storeSlot(props.campId).url, options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? t('slots.edit') : t('slots.add') }}
                </DialogTitle>
            </DialogHeader>

            <form id="slot-form" class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="slot-name">{{ t('common.name') }}</Label>
                    <Input
                        id="slot-name"
                        v-model="form.name"
                        :placeholder="t('slots.namePlaceholder')"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="slot-start">{{ t('common.from') }}</Label>
                        <Input
                            id="slot-start"
                            v-model="form.start_time"
                            type="time"
                            required
                        />
                        <InputError :message="form.errors.start_time" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="slot-end">{{ t('common.to') }}</Label>
                        <Input
                            id="slot-end"
                            v-model="form.end_time"
                            type="time"
                            required
                        />
                        <InputError :message="form.errors.end_time" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>{{ t('common.type') }}</Label>
                        <Select
                            :model-value="form.kind"
                            @update:model-value="form.kind = $event as SlotKind"
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="activity">
                                    {{ t('slots.kind.activity') }}
                                </SelectItem>
                                <SelectItem value="fixed">
                                    {{ t('slots.kind.fixed') }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <ColorSelect
                        v-model="form.color"
                        :label="t('camps.appearance.color')"
                    />
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    {{ t('common.cancel') }}
                </Button>
                <Button
                    type="submit"
                    form="slot-form"
                    :disabled="form.processing"
                >
                    {{ editing ? t('common.save') : t('common.add') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
