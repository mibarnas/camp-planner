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
import { useI18n } from '@/i18n';
import { store as storeType, update as updateType } from '@/routes/groupTypes';
import type { GroupType } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{ campId: number; editing: GroupType | null }>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({ name: '', color: 'slate' as string });

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults({
        name: props.editing?.name ?? '',
        color: props.editing?.color ?? 'slate',
    });
    form.reset();
});

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    if (props.editing) {
        form.put(
            updateType({ camp: props.campId, groupType: props.editing.id }).url,
            options,
        );
    } else {
        form.post(storeType(props.campId).url, options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? t('groups.editType') : t('groups.newType') }}
                </DialogTitle>
            </DialogHeader>

            <form
                id="group-type-form"
                class="grid gap-4"
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="type-name">{{ t('common.name') }}</Label>
                    <Input
                        id="type-name"
                        v-model="form.name"
                        :placeholder="t('groups.typePlaceholder')"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <ColorSelect
                    v-model="form.color"
                    :label="t('camps.appearance.color')"
                />
            </form>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    {{ t('common.cancel') }}
                </Button>
                <Button
                    type="submit"
                    form="group-type-form"
                    :disabled="form.processing"
                >
                    {{ editing ? t('common.save') : t('common.add') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
