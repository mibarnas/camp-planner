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
import {
    destroy as destroyVersion,
    restore as restoreVersion,
    store as storeVersion,
} from '@/routes/versions';
import type { PlanVersion } from '@/types/camp';

const props = defineProps<{
    open: boolean;
    campId: number;
    versions: PlanVersion[];
    isOwner: boolean;
    scheduleLocked: boolean;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const today = new Date();
const form = useForm({ name: `Verzia ${today.getDate()}.${today.getMonth() + 1}.` });

function save() {
    form.post(storeVersion(props.campId).url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function restore(version: PlanVersion) {
    if (
        !confirm(
            `Obnoviť verziu „${version.name}"? Aktuálny program sa najprv uloží ako záloha.`,
        )
    ) {
        return;
    }

    router.post(
        restoreVersion({ camp: props.campId, planVersion: version.id }).url,
        {},
        { preserveScroll: true },
    );
}

function remove(version: PlanVersion) {
    if (!confirm(`Zmazať verziu „${version.name}"?`)) {
        return;
    }

    router.delete(destroyVersion({ camp: props.campId, planVersion: version.id }).url, {
        preserveScroll: true,
    });
}

function formatDate(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleString('sk-SK', {
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
                    <History class="size-5" /> Verzie plánu
                </DialogTitle>
                <DialogDescription>
                    Ulož si stav programu pred väčšou zmenou a kedykoľvek sa k nemu vráť.
                    Verzia obsahuje celý rozvrh — aktivity, časové bloky aj ich denné úpravy.
                </DialogDescription>
            </DialogHeader>

            <form v-if="isOwner" class="flex items-end gap-2" @submit.prevent="save">
                <div class="grid flex-1 gap-2">
                    <Label for="version-name">Názov verzie</Label>
                    <Input id="version-name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>
                <Button type="submit" :disabled="form.processing">
                    <Save /> Uložiť aktuálny stav
                </Button>
            </form>

            <p
                v-if="isOwner && scheduleLocked"
                class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-200"
            >
                Program je uzamknutý — pred obnovením verzie ho najprv odomkni.
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
                            <span v-if="version.author"> · {{ version.author }}</span>
                        </p>
                    </div>
                    <div v-if="isOwner" class="flex shrink-0 items-center gap-1">
                        <Button variant="outline" size="sm" @click="restore(version)">
                            <RotateCcw /> Obnoviť
                        </Button>
                        <Button variant="ghost" size="icon-sm" title="Zmazať verziu" @click="remove(version)">
                            <Trash2 class="text-destructive" />
                        </Button>
                    </div>
                </div>
                <p
                    v-if="!versions.length"
                    class="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground"
                >
                    Zatiaľ žiadne uložené verzie.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
