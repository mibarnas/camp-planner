<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { UserPlus } from '@lucide/vue';
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
import { useI18n } from '@/i18n';
import { store as storeMember } from '@/routes/members';

const { t } = useI18n();

const props = defineProps<{ campId: number }>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({ email: '' });

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.reset();
    }
});

function submit() {
    form.post(storeMember(props.campId).url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('members.inviteByEmail') }}</DialogTitle>
                <DialogDescription>
                    {{ t('leaders.inviteMatchHint') }}
                </DialogDescription>
            </DialogHeader>

            <form id="invite-form" class="grid gap-2" @submit.prevent="submit">
                <Label for="invite-email">{{ t('auth.field.email') }}</Label>
                <Input
                    id="invite-email"
                    v-model="form.email"
                    type="email"
                    :placeholder="t('members.invitePlaceholder')"
                    required
                />
                <InputError :message="form.errors.email" />
            </form>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    {{ t('common.cancel') }}
                </Button>
                <Button
                    type="submit"
                    form="invite-form"
                    :disabled="form.processing"
                >
                    <UserPlus /> {{ t('members.invite') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
