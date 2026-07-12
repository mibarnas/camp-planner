<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Sparkles, TriangleAlert } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { postJson } from '@/lib/http';
import { edit as editAi } from '@/routes/ai';
import { summary as campSummary } from '@/routes/camps';
import { summary as daySummary } from '@/routes/days';
import type { CampDay } from '@/types/camp';

const props = defineProps<{
    campId: number;
    days: CampDay[];
}>();

const open = defineModel<boolean>('open', { required: true });

// 'camp' or a day id as string, driving the scope <Select>.
const scope = ref<string>('camp');
const loading = ref(false);
const summary = ref('');
const error = ref('');
const needsKey = ref(false);

// Only offer days that actually have reviews.
const reviewedDays = computed(() => props.days.filter((d) => d.review_summary.reviewers > 0));

watch(open, (isOpen) => {
    if (isOpen) {
        summary.value = '';
        error.value = '';
        needsKey.value = false;
        scope.value = 'camp';
    }
});

async function generate() {
    loading.value = true;
    error.value = '';
    needsKey.value = false;
    summary.value = '';

    const url =
        scope.value === 'camp'
            ? campSummary.url(props.campId)
            : daySummary.url(Number(scope.value));

    try {
        const res = await postJson<{ summary: string }>(url);
        summary.value = res.summary;
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Nastala chyba.';
        // The backend flags a missing key so we can link to settings.
        needsKey.value = error.value.includes('Gemini API kľúč');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Sparkles class="size-5 text-primary" /> AI súhrn zhodnotení
                </DialogTitle>
                <DialogDescription>
                    Gemini zhrnie hviezdičkové hodnotenia a poznámky všetkých vedúcich.
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <Select v-model="scope">
                        <SelectTrigger class="sm:w-64">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="camp">Celý tábor</SelectItem>
                            <SelectItem
                                v-for="d in reviewedDays"
                                :key="d.id"
                                :value="String(d.id)"
                            >
                                {{ d.weekday }} {{ d.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button class="sm:ml-auto" :disabled="loading" @click="generate">
                        <Spinner v-if="loading" />
                        <Sparkles v-else />
                        {{ loading ? 'Generujem…' : 'Generovať súhrn' }}
                    </Button>
                </div>

                <!-- Error / needs-key -->
                <div
                    v-if="error"
                    class="flex items-start gap-2 rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive"
                >
                    <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                    <div class="space-y-2">
                        <p>{{ error }}</p>
                        <Button v-if="needsKey" as-child size="sm" variant="outline">
                            <Link :href="editAi()" @click="open = false">Pridať kľúč</Link>
                        </Button>
                    </div>
                </div>

                <!-- Result -->
                <div
                    v-if="summary"
                    class="max-h-[50vh] overflow-y-auto rounded-lg border bg-muted/30 p-4 text-sm whitespace-pre-line"
                >
                    {{ summary }}
                </div>

                <div
                    v-else-if="!error && !loading"
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    Vyber rozsah a vygeneruj súhrn.
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
