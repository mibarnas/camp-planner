<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import {
    CalendarCheck,
    ChevronDown,
    MessageCircleQuestion,
    MessageSquare,
    Sparkles,
    Star,
    Users,
} from '@lucide/vue';
import { computed, ref, watch, watchEffect } from 'vue';
import QuestionsDialog from '@/components/camp/QuestionsDialog.vue';
import SummaryPanel from '@/components/camp/SummaryPanel.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useI18n } from '@/i18n';
import { index as campsIndex, show } from '@/routes/camps';
import type { AiSummary, FeedbackDay, FeedbackQuestion } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    camp: { id: number; name: string };
    days: FeedbackDay[];
    questions: FeedbackQuestion[];
    aiSummaries: AiSummary[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.camps'), href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: t('nav.camp.feedback'), href: '#' },
        ],
    });
});

const reviewedDays = computed(() => props.days.filter((d) => d.reviewers > 0));
const totalReviews = computed(() =>
    props.days.reduce((sum, d) => sum + d.reviewers, 0),
);
const overallAvg = computed(() => {
    const rated = reviewedDays.value.filter(
        (d): d is FeedbackDay & { avg: number } => d.avg !== null,
    );

    if (!rated.length) {
        return null;
    }

    const sum = rated.reduce((total, d) => total + d.avg, 0);

    return Math.round((sum / rated.length) * 10) / 10;
});

// Days start collapsed except the most recently reviewed one. Re-seeded when
// new reviews arrive, keeping whatever the reader has already opened.
const openDays = ref<Set<number>>(new Set());
watch(
    () => props.days,
    () => {
        const known = new Set(props.days.map((d) => d.id));
        const next = new Set([...openDays.value].filter((id) => known.has(id)));

        if (!next.size) {
            reviewedDays.value.slice(-1).forEach((d) => next.add(d.id));
        }

        openDays.value = next;
    },
    { immediate: true },
);

function setOpen(dayId: number, isOpen: boolean) {
    const next = new Set(openDays.value);

    if (isOpen) {
        next.add(dayId);
    } else {
        next.delete(dayId);
    }

    openDays.value = next;
}

const questionById = computed(
    () => new Map(props.questions.map((q) => [q.id, q])),
);

const questionsDialog = ref(false);

function capitalize(v: string): string {
    return v.charAt(0).toUpperCase() + v.slice(1);
}
</script>

<template>
    <Head :title="`${t('nav.camp.feedback')} — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="t('nav.camp.feedback')"
                :description="t('feedback.description')"
            />
            <Button
                variant="outline"
                class="shrink-0 sm:w-fit"
                @click="questionsDialog = true"
            >
                <MessageCircleQuestion /> {{ t('feedback.manageQuestions') }}
            </Button>
        </div>

        <div v-if="totalReviews" class="grid gap-3 sm:grid-cols-3">
            <div class="flex items-center gap-3 rounded-xl border p-3">
                <Users class="size-5 shrink-0 text-muted-foreground" />
                <div>
                    <p class="text-xl font-semibold tabular-nums">
                        {{ totalReviews }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ t('feedback.stats.totalReviews') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border p-3">
                <CalendarCheck class="size-5 shrink-0 text-muted-foreground" />
                <div>
                    <p class="text-xl font-semibold tabular-nums">
                        {{ reviewedDays.length }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ t('feedback.stats.daysReviewed') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border p-3">
                <Star class="size-5 shrink-0 fill-amber-400 text-amber-400" />
                <div>
                    <p class="text-xl font-semibold tabular-nums">
                        {{ overallAvg ?? '—' }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ t('feedback.stats.avgRating') }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,26rem)]"
        >
            <!-- The reviews themselves: what the page is actually for. -->
            <div class="grid content-start gap-3">
                <h2 class="text-lg font-semibold">{{ t('feedback.byDay') }}</h2>

                <p
                    v-if="!reviewedDays.length"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('feedback.empty') }}
                </p>

                <Collapsible
                    v-for="day in reviewedDays"
                    :key="day.id"
                    :open="openDays.has(day.id)"
                    @update:open="setOpen(day.id, $event)"
                >
                    <Card class="gap-0 overflow-hidden py-0">
                        <CollapsibleTrigger
                            class="flex w-full flex-wrap items-center gap-3 p-4 text-left transition-colors hover:bg-muted/50"
                        >
                            <ChevronDown
                                class="size-4 shrink-0 text-muted-foreground transition-transform"
                                :class="
                                    openDays.has(day.id) ? '' : '-rotate-90'
                                "
                            />
                            <span class="font-medium">
                                {{ capitalize(day.weekday) }} {{ day.label }}
                            </span>
                            <Badge variant="outline" class="gap-1">
                                <Users class="size-3" /> {{ day.reviewers }}
                            </Badge>
                            <Badge
                                v-if="day.avg"
                                variant="secondary"
                                class="gap-1"
                            >
                                <Star
                                    class="size-3 fill-amber-400 text-amber-400"
                                />
                                {{ day.avg }}
                            </Badge>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <div
                                class="grid gap-3 border-t p-4 2xl:grid-cols-2"
                            >
                                <div
                                    v-for="review in day.reviews"
                                    :key="review.id"
                                    class="grid content-start gap-2 rounded-lg border p-3"
                                >
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p class="font-medium">
                                            {{ review.user_name }}
                                        </p>
                                        <Badge
                                            v-if="review.camp_rating"
                                            variant="secondary"
                                            class="gap-1"
                                        >
                                            {{
                                                t('feedback.campRating', {
                                                    rating: review.camp_rating,
                                                })
                                            }}
                                        </Badge>
                                    </div>

                                    <ul
                                        v-if="review.ratings.length"
                                        class="grid gap-1"
                                    >
                                        <li
                                            v-for="rating in review.ratings"
                                            :key="rating.entry_id"
                                            class="flex flex-wrap items-baseline gap-x-2 text-sm"
                                        >
                                            <span
                                                class="flex shrink-0 items-center gap-1 tabular-nums"
                                            >
                                                <Star
                                                    class="size-3.5 fill-amber-400 text-amber-400"
                                                />
                                                {{ rating.rating }}/5
                                            </span>
                                            <span class="font-medium">
                                                {{ rating.entry_title }}
                                            </span>
                                            <span
                                                v-if="rating.reason"
                                                class="text-muted-foreground"
                                            >
                                                — {{ rating.reason }}
                                            </span>
                                        </li>
                                    </ul>

                                    <div
                                        v-if="review.answers.length"
                                        class="grid gap-1.5"
                                    >
                                        <div
                                            v-for="answer in review.answers"
                                            :key="answer.question_id"
                                            class="text-sm"
                                        >
                                            <p class="text-muted-foreground">
                                                {{
                                                    questionById.get(
                                                        answer.question_id,
                                                    )?.text
                                                }}
                                            </p>
                                            <p class="whitespace-pre-line">
                                                {{ answer.answer }}
                                            </p>
                                        </div>
                                    </div>

                                    <p
                                        v-if="review.notes"
                                        class="flex gap-2 text-sm text-muted-foreground"
                                    >
                                        <MessageSquare
                                            class="mt-0.5 size-3.5 shrink-0"
                                        />
                                        <span class="whitespace-pre-line">
                                            {{ review.notes }}
                                        </span>
                                    </p>
                                    <p
                                        v-if="review.camp_reason"
                                        class="text-sm text-muted-foreground"
                                    >
                                        <span class="font-medium">
                                            {{ t('feedback.aboutCamp') }}
                                        </span>
                                        {{ review.camp_reason }}
                                    </p>
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Card>
                </Collapsible>
            </div>

            <Card class="self-start xl:sticky xl:top-4">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Sparkles class="size-5 text-primary" />
                        {{ t('feedback.aiSummary') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <SummaryPanel
                        :camp-id="camp.id"
                        :days="days"
                        :summaries="aiSummaries"
                    />
                </CardContent>
            </Card>
        </div>
    </div>

    <QuestionsDialog
        v-model:open="questionsDialog"
        :camp-id="camp.id"
        :questions="questions"
    />
</template>
