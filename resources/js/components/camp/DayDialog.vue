<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Textarea } from '@/components/ui/textarea';
import { useI18n } from '@/i18n';
import { update as updateDay } from '@/routes/days';
import type { CampDay } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{ open: boolean; day: CampDay | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const form = useForm({
    is_trip: false,
    trip_name: '',
    name_days: '',
    birthdays: '',
    materials: '',
    notes: '',
});

watch(
    () => props.open,
    (open) => {
        if (!open || !props.day) {
            return;
        }

        const d = props.day;
        form.clearErrors();
        form.defaults({
            is_trip: d.is_trip,
            trip_name: d.trip_name ?? '',
            name_days: d.name_days ?? '',
            birthdays: d.birthdays ?? '',
            materials: d.materials ?? '',
            notes: d.notes ?? '',
        });
        form.reset();
    },
);

function submit() {
    if (!props.day) {
        return;
    }

    form.put(updateDay(props.day.id).url, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ day?.weekday }} · {{ day?.label }}</DialogTitle>
                <DialogDescription>{{
                    t('day.dialog.description')
                }}</DialogDescription>
            </DialogHeader>

            <form class="grid gap-4 px-1" @submit.prevent="submit">
                <label class="flex items-center gap-3 rounded-lg border p-3">
                    <Checkbox
                        :model-value="form.is_trip"
                        @update:model-value="form.is_trip = $event === true"
                    />
                    <span>
                        <span class="font-medium">{{ t('day.trip') }}</span>
                        <span class="block text-sm text-muted-foreground">{{
                            t('day.tripHint')
                        }}</span>
                    </span>
                </label>

                <div v-if="form.is_trip" class="grid gap-2">
                    <Label for="day-trip">{{ t('day.tripName') }}</Label>
                    <Input
                        id="day-trip"
                        v-model="form.trip_name"
                        :placeholder="t('day.tripPlaceholder')"
                    />
                    <InputError :message="form.errors.trip_name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="day-namedays">{{
                            t('day.nameDays')
                        }}</Label>
                        <Input id="day-namedays" v-model="form.name_days" />
                        <InputError :message="form.errors.name_days" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="day-birthdays">{{
                            t('day.birthdays')
                        }}</Label>
                        <Input id="day-birthdays" v-model="form.birthdays" />
                        <InputError :message="form.errors.birthdays" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="day-materials">{{ t('day.materials') }}</Label>
                    <Textarea
                        id="day-materials"
                        v-model="form.materials"
                        class="min-h-20"
                    />
                    <InputError :message="form.errors.materials" />
                </div>

                <div class="grid gap-2">
                    <Label for="day-notes">{{ t('day.notes') }}</Label>
                    <Textarea
                        id="day-notes"
                        v-model="form.notes"
                        class="min-h-20"
                    />
                    <InputError :message="form.errors.notes" />
                </div>
            </form>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    {{ t('common.cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ t('common.save') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
