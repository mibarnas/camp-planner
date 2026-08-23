<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { History, RotateCcw, Save, Trash2 } from '@lucide/vue';
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
import { useI18n } from '@/i18n';
import { dateLocale } from '@/lib/datetime';
import {
    destroy as destroyVersion,
    restore as restoreVersion,
    store as storeVersion,
} from '@/routes/versions';
import type { PlanVersion } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    open: boolean;
    campId: number;
    versions: PlanVersion[];
    isOwner: boolean;
    scheduleLocked: boolean;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const today = new Date();
const form = useForm({
    name: `Verzia ${today.getDate()}.${today.getMonth() + 1}.`,
});

function save() {
    form.post(storeVersion(props.campId).url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function restore(version: PlanVersion) {
    if (!confirm(t('versions.plan.confirmRestore', { name: version.name }))) {
        return;
    }

    router.post(
        restoreVersion({ camp: props.campId, planVersion: version.id }).url,
        {},
        { preserveScroll: true },
    );
}

function remove(version: PlanVersion) {
    if (!confirm(t('versions.confirmDelete', { name: version.name }))) {
        return;
    }

    router.delete(
        destroyVersion({ camp: props.campId, planVersion: version.id }).url,
        {
            preserveScroll: true,
        },
    );
}

function formatDate(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleString(dateLocale(), {
        day: 'numeric',
        month: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <History class="size-5" /> {{ t('versions.plan.title') }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('versions.plan.description') }}
                </DialogDescription>
            </DialogHeader>

            <form
                v-if="isOwner"
                class="flex flex-col gap-2 sm:flex-row sm:items-end"
                @submit.prevent="save"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="version-name">{{ t('versions.name') }}</Label>
                    <Input id="version-name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>
                <Button type="submit" :disabled="form.processing">
                    <Save /> {{ t('versions.saveCurrent') }}
                </Button>
            </form>

            <p
                v-if="isOwner && scheduleLocked"
                class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-200"
            >
                {{ t('versions.plan.locked') }}
            </p>

            <div class="grid max-h-80 gap-2 overflow-y-auto pr-1">
                <div
                    v-for="version in versions"
                    :key="version.id"
                    class="flex items-center justify-between gap-2 rounded-lg border p-3"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ version.name }}</p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ formatDate(version.created_at) }}
                            <span v-if="version.author">
                                · {{ version.author }}</span
                            >
                        </p>
                    </div>
                    <div
                        v-if="isOwner"
                        class="flex shrink-0 items-center gap-1"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            @click="restore(version)"
                        >
                            <RotateCcw /> {{ t('versions.restore') }}
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="t('versions.delete')"
                            @click="remove(version)"
                        >
                            <Trash2 class="text-destructive" />
                        </Button>
                    </div>
                </div>
                <p
                    v-if="!versions.length"
                    class="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground"
                >
                    {{ t('versions.empty') }}
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
