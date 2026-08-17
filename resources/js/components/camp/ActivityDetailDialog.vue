<script setup lang="ts">
import { Check, Clock, Copy, LayoutList, Link2, Package, Pencil, Trash2, User as UserIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { categoryById, colorStyle } from '@/lib/campColors';
import { campIcon } from '@/lib/campIcons';
import { durationLabel } from '@/lib/timeline';
import type { Activity, ActivityCategory } from '@/types/camp';

const props = defineProps<{
    open: boolean;
    activity: Activity | null;
    categories: ActivityCategory[];
    canManage: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    edit: [activity: Activity];
    duplicate: [activity: Activity];
    remove: [activity: Activity];
}>();

const category = computed(() =>
    props.activity ? categoryById(props.categories, props.activity.category_id) : null,
);
const accent = computed(() => props.activity?.color ?? category.value?.color ?? 'slate');
const usage = computed(() => props.activity?.usage_count ?? 0);

const copied = ref(false);
async function copyShareUrl() {
    const url = props.activity?.share_url;

    if (!url) {
return;
}

    try {
        await navigator.clipboard.writeText(url);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch {
        window.prompt('Skopíruj odkaz na aktivitu:', url);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent v-if="activity" class="sm:max-w-lg">
            <DialogHeader>
                <div class="flex items-start gap-3">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl" :class="colorStyle(accent).chip">
                        <component :is="campIcon('sparkles')" class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle class="text-lg leading-tight">{{ activity.name }}</DialogTitle>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <Badge v-if="category" variant="secondary" :class="colorStyle(category.color).chip">
                                {{ category.name }}
                            </Badge>
                            <Badge v-else variant="outline">Bez kategórie</Badge>
                        </div>
                    </div>
                </div>
            </DialogHeader>

            <!-- Quick facts -->
            <div class="grid gap-2 sm:grid-cols-2">
                <div class="flex items-center gap-2 rounded-lg border p-2.5">
                    <Clock class="size-4 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-medium">{{ durationLabel(activity.default_duration) }}</p>
                        <p class="text-[11px] text-muted-foreground">Odporúčaná dĺžka</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-lg border p-2.5">
                    <LayoutList class="size-4 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-medium">
                            {{ usage === 0 ? 'Zatiaľ nepoužité' : `${usage}× v programe` }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">Použitie</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 px-0.5">
                <div v-if="activity.description">
                    <p class="mb-1 text-xs font-medium text-muted-foreground">Popis / scenár</p>
                    <p class="text-sm whitespace-pre-line">{{ activity.description }}</p>
                </div>
                <p v-else class="text-sm text-muted-foreground italic">Bez popisu.</p>

                <div v-if="activity.materials">
                    <p class="mb-1 flex items-center gap-1 text-xs font-medium text-muted-foreground">
                        <Package class="size-3.5" /> Potrebný materiál
                    </p>
                    <p class="text-sm whitespace-pre-line">{{ activity.materials }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <p v-if="activity.creator || activity.created_at" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <UserIcon class="size-3.5" />
                    <span v-if="activity.creator">Vytvoril {{ activity.creator.name }}</span>
                    <span v-if="activity.created_at">· {{ activity.created_at }}</span>
                </p>
                <Button v-if="activity.share_url" type="button" variant="ghost" size="sm" @click="copyShareUrl">
                    <component :is="copied ? Check : Link2" />
                    {{ copied ? 'Skopírované' : 'Kopírovať odkaz' }}
                </Button>
            </div>

            <DialogFooter v-if="canManage" class="sm:justify-between">
                <Button type="button" variant="ghost" class="text-destructive" @click="emit('remove', activity)">
                    <Trash2 /> Zmazať
                </Button>
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="emit('duplicate', activity)">
                        <Copy /> Duplikovať
                    </Button>
                    <Button type="button" @click="emit('edit', activity)">
                        <Pencil /> Upraviť
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
