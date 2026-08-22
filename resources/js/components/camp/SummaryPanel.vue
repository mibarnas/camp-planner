<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Sparkles, TriangleAlert } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { HttpError, postJson } from '@/lib/http';
import { edit as editAi } from '@/routes/ai';
import { summary as campSummary } from '@/routes/camps';
import { summary as daySummary } from '@/routes/days';
import type { AiSummary, SummaryDay } from '@/types/camp';

const props = defineProps<{
    campId: number;
    days: SummaryDay[];
    summaries: AiSummary[];
}>();

// 'camp' or a day id as string, driving the scope <Select>.
const scope = ref<string>('camp');
const loading = ref(false);
const summaryHtml = ref('');
const savedAt = ref<string | null>(null);
const error = ref('');
const needsKey = ref(false);

// Kept locally so a fresh summary shows up without a full page reload.
const stored = ref<AiSummary[]>([...props.summaries]);
watch(
    () => props.summaries,
    (s) => (stored.value = [...s]),
);

// Only offer days that actually have reviews.
const reviewedDays = computed(() => props.days.filter((d) => d.reviewers > 0));

const storedForScope = computed(
    () =>
        stored.value.find((s) =>
            scope.value === 'camp'
                ? s.camp_day_id === null
                : s.camp_day_id === Number(scope.value),
        ) ?? null,
);

const shownHtml = computed(
    () => summaryHtml.value || storedForScope.value?.summary_html || '',
);
const shownSavedAt = computed(
    () => savedAt.value ?? storedForScope.value?.saved_at ?? null,
);

function savedAtLabel(iso: string): string {
    return new Date(iso).toLocaleString('sk-SK', {
        day: 'numeric',
        month: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Summarising the whole camp only makes sense once there is a whole camp to
 * summarise; mid-camp the useful default is the newest day that has reviews.
 */
function defaultScope(): string {
    const lastReviewed = reviewedDays.value.at(-1);

    if (!lastReviewed) {
        return 'camp';
    }

    const todayIso = new Date().toISOString().slice(0, 10);
    const lastDay = props.days.at(-1);
    const campFinished = !!lastDay && lastDay.date < todayIso;
    const withEntries = props.days.filter((d) => d.entries_count > 0);
    const allReviewed =
        withEntries.length > 0 && withEntries.every((d) => d.reviewers > 0);

    return campFinished || allReviewed ? 'camp' : String(lastReviewed.id);
}

onMounted(() => (scope.value = defaultScope()));

// Switching scope drops the just-generated result so the stored one for the new scope shows.
watch(scope, () => {
    summaryHtml.value = '';
    savedAt.value = null;
    error.value = '';
    needsKey.value = false;
});

async function generate() {
    loading.value = true;
    error.value = '';
    needsKey.value = false;
    summaryHtml.value = '';
    savedAt.value = null;

    const isCamp = scope.value === 'camp';
    const url = isCamp
        ? campSummary.url(props.campId)
        : daySummary.url(Number(scope.value));

    try {
        const res = await postJson<{
            summary: string;
            summary_html: string;
            saved_at: string | null;
        }>(url);
        summaryHtml.value = res.summary_html;
        savedAt.value = res.saved_at;

        const entry: AiSummary = {
            camp_day_id: isCamp ? null : Number(scope.value),
            summary: res.summary,
            summary_html: res.summary_html,
            author: null,
            saved_at: res.saved_at,
        };
        stored.value = [
            ...stored.value.filter((s) => s.camp_day_id !== entry.camp_day_id),
            entry,
        ];
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Nastala chyba.';
        // The backend flags a missing key so we can link to settings.
        needsKey.value = e instanceof HttpError && e.data.needs_key === true;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
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
                {{
                    loading
                        ? 'Generujem…'
                        : shownHtml
                          ? 'Generovať znova'
                          : 'Generovať súhrn'
                }}
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
                    <Link :href="editAi()">Pridať kľúč</Link>
                </Button>
            </div>
        </div>

        <!-- Result -->
        <div v-if="shownHtml" class="grid gap-1.5">
            <!-- eslint-disable-next-line vue/no-v-html -- sanitized server-side by App\Support\Markdown -->
            <div
                class="md-prose max-w-3xl rounded-lg border bg-muted/30 p-4"
                v-html="shownHtml"
            />
            <p v-if="shownSavedAt" class="text-xs text-muted-foreground">
                Uložené {{ savedAtLabel(shownSavedAt) }}
            </p>
        </div>

        <div
            v-else-if="!error && !loading"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Vyber rozsah a vygeneruj súhrn.
        </div>
    </div>
</template>
