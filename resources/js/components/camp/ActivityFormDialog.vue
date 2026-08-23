<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { useI18n } from '@/i18n';
import { categoryById, colorStyle, COLOR_NAMES } from '@/lib/campColors';
import {
    store as storeActivity,
    update as updateActivity,
} from '@/routes/activities';
import type { Activity, ActivityCategory } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    open: boolean;
    activity?: Activity | null;
    libraryId?: number | null;
    categories: ActivityCategory[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [];
}>();

const form = useForm({
    activity_library_id: null as number | null,
    activity_category_id: null as number | null,
    name: '',
    description: '',
    default_duration: 60,
    color: 'emerald' as string,
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
            activity_category_id: a?.category_id ?? null,
            name: a?.name ?? '',
            description: a?.description ?? '',
            default_duration: a?.default_duration ?? 60,
            color: a?.color ?? 'emerald',
            materials: a?.materials ?? '',
        });
        form.reset();
    },
);

function onCategoryChange(value: string) {
    const id = value === 'none' ? null : Number(value);
    form.activity_category_id = id;

    // Adopt the category's colour when creating a fresh activity for convenience.
    if (!props.activity) {
        const cat = categoryById(props.categories, id);

        if (cat?.color) {
            form.color = cat.color;
        }
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
                <DialogTitle>{{
                    activity ? t('activities.edit') : t('activities.new')
                }}</DialogTitle>
                <DialogDescription>
                    {{ t('activities.formDescription') }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="activity-name">{{ t('common.name') }}</Label>
                    <Input
                        id="activity-name"
                        v-model="form.name"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>{{ t('activities.category') }}</Label>
                        <Select
                            :model-value="
                                form.activity_category_id === null
                                    ? 'none'
                                    : String(form.activity_category_id)
                            "
                            @update:model-value="
                                onCategoryChange($event as string)
                            "
                        >
                            <SelectTrigger>
                                <SelectValue
                                    :placeholder="t('activities.noCategory')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">{{
                                    t('activities.noCategory')
                                }}</SelectItem>
                                <SelectItem
                                    v-for="c in categories"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-2.5 rounded-full"
                                            :class="colorStyle(c.color).dot"
                                        />
                                        {{ c.name }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="form.errors.activity_category_id"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="activity-duration">{{
                            t('activities.duration')
                        }}</Label>
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
                    <Label>{{ t('activities.timelineColor') }}</Label>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="c in COLOR_NAMES"
                            :key="c"
                            type="button"
                            class="size-6 rounded-full border-2 transition"
                            :class="[
                                colorStyle(c).dot,
                                form.color === c
                                    ? 'border-foreground ring-2 ring-ring/40'
                                    : 'border-transparent',
                            ]"
                            :title="c"
                            @click="form.color = c"
                        />
                    </div>
                    <InputError :message="form.errors.color" />
                </div>

                <div class="grid gap-2">
                    <Label for="activity-description">{{
                        t('activities.descriptionLabel')
                    }}</Label>
                    <Textarea
                        id="activity-description"
                        v-model="form.description"
                        class="min-h-24"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="activity-materials">{{
                        t('activities.field.materials')
                    }}</Label>
                    <Textarea
                        id="activity-materials"
                        v-model="form.materials"
                        class="min-h-16"
                    />
                    <InputError :message="form.errors.materials" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ t('common.save') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
