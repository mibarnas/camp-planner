<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import ColorSelect from '@/components/ColorSelect.vue';
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
import { useI18n } from '@/i18n';
import { store as storeLeader, update as updateLeader } from '@/routes/leaders';
import type { CampPerson } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{ campId: number; editing: CampPerson | null }>();

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

    if (props.editing?.leader_id) {
        form.put(
            updateLeader({
                camp: props.campId,
                leader: props.editing.leader_id,
            }).url,
            options,
        );
    } else {
        form.post(storeLeader(props.campId).url, options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? t('leaders.edit') : t('leaders.add') }}
                </DialogTitle>
                <DialogDescription v-if="!editing">
                    {{ t('leaders.hint') }}
                </DialogDescription>
            </DialogHeader>

            <form id="person-form" class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="person-name">{{ t('auth.field.name') }}</Label>
                    <Input
                        id="person-name"
                        v-model="form.name"
                        :placeholder="t('leaders.namePlaceholder')"
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
                    form="person-form"
                    :disabled="form.processing"
                >
                    {{ editing ? t('common.save') : t('common.add') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
