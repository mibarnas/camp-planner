<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useI18n } from '@/i18n';

const { t } = useI18n();

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        confirmLabel?: string;
        destructive?: boolean;
    }>(),
    { destructive: true },
);

const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{ confirm: [] }>();

function confirm() {
    emit('confirm');
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    {{ t('common.cancel') }}
                </Button>
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    @click="confirm"
                >
                    {{ confirmLabel ?? t('common.remove') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
