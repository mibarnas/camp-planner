<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import {
    ChevronDown,
    MessageCircleQuestion,
    MessageSquare,
    Sparkles,
    Star,
    Users,
} from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import QuestionsPanel from '@/components/camp/QuestionsPanel.vue';
import SummaryPanel from '@/components/camp/SummaryPanel.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as campsIndex, show } from '@/routes/camps';
import type { AiSummary, FeedbackDay, FeedbackQuestion } from '@/types/camp';

const props = defineProps<{
    camp: { id: number; name: string };
    days: FeedbackDay[];
    questions: FeedbackQuestion[];
    aiSummaries: AiSummary[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tábory', href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: 'Spätná väzba', href: '#' },
        ],
    });
});

const reviewedDays = computed(() => props.days.filter((d) => d.reviewers > 0));
const totalReviews = computed(() =>
    props.days.reduce((sum, d) => sum + d.reviewers, 0),
);

// Days start collapsed except the most recently reviewed one.
const openDays = ref<Set<number>>(
    new Set(reviewedDays.value.slice(-1).map((d) => d.id)),
);
function toggle(dayId: number) {
    const next = new Set(openDays.value);

    if (next.has(dayId)) {
        next.delete(dayId);
    } else {
        next.add(dayId);
    }

    openDays.value = next;
}

const questionById = computed(
    () => new Map(props.questions.map((q) => [q.id, q])),
);

function capitalize(v: string): string {
    return v.charAt(0).toUpperCase() + v.slice(1);
}
</script>

<template>
    <Head :title="`Spätná väzba — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Spätná väzba"
            description="Zhodnotenia všetkých vedúcich a AI súhrny na jednom mieste."
        />

        <!-- Custom questions -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <MessageCircleQuestion class="size-5 text-primary" />
                    Vlastné otázky
                </CardTitle>
            </CardHeader>
            <CardContent>
                <QuestionsPanel :camp-id="camp.id" :questions="questions" />
            </CardContent>
        </Card>

        <!-- AI summaries -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Sparkles class="size-5 text-primary" /> AI súhrn zhodnotení
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

        <!-- All feedback -->
        <div class="grid gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-lg font-semibold">Zhodnotenia po dňoch</h2>
                <Badge v-if="totalReviews" variant="secondary" class="gap-1">
                    <Users class="size-3" /> {{ totalReviews }} zhodnotení
                </Badge>
            </div>

            <p
                v-if="!reviewedDays.length"
                class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
            >
                Zatiaľ nikto nezhodnotil žiadny deň. Vedúci zhodnotia deň
                hviezdičkou v pláne tábora.
            </p>

            <Card
                v-for="day in reviewedDays"
                :key="day.id"
                class="overflow-hidden py-0"
            >
                <button
                    type="button"
                    class="flex w-full flex-wrap items-center gap-3 p-4 text-left transition-colors hover:bg-muted/50"
                    @click="toggle(day.id)"
                >
                    <ChevronDown
                        class="size-4 shrink-0 text-muted-foreground transition-transform"
                        :class="openDays.has(day.id) ? '' : '-rotate-90'"
                    />
                    <span class="font-medium"
                        >{{ capitalize(day.weekday) }} {{ day.label }}</span
                    >
                    <Badge variant="outline" class="gap-1">
                        <Users class="size-3" /> {{ day.reviewers }}
                    </Badge>
                    <Badge v-if="day.avg" variant="secondary" class="gap-1">
                        <Star class="size-3 fill-amber-400 text-amber-400" />
                        {{ day.avg }}
                    </Badge>
                </button>

                <div
                    v-if="openDays.has(day.id)"
                    class="grid gap-3 border-t p-4 xl:grid-cols-2 2xl:grid-cols-3"
                >
                    <div
                        v-for="review in day.reviews"
                        :key="review.id"
                        class="grid content-start gap-2 rounded-lg border p-3"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">{{ review.user_name }}</p>
                            <Badge
                                v-if="review.camp_rating"
                                variant="secondary"
                                class="gap-1"
                            >
                                Tábor {{ review.camp_rating }}/5
                            </Badge>
                        </div>

                        <ul v-if="review.ratings.length" class="grid gap-1">
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
                                <span class="font-medium">{{
                                    rating.entry_title
                                }}</span>
                                <span
                                    v-if="rating.reason"
                                    class="text-muted-foreground"
                                >
                                    — {{ rating.reason }}
                                </span>
                            </li>
                        </ul>

                        <div v-if="review.answers.length" class="grid gap-1.5">
                            <div
                                v-for="answer in review.answers"
                                :key="answer.question_id"
                                class="text-sm"
                            >
                                <p class="text-muted-foreground">
                                    {{
                                        questionById.get(answer.question_id)
                                            ?.text
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
                            <MessageSquare class="mt-0.5 size-3.5 shrink-0" />
                            <span class="whitespace-pre-line">{{
                                review.notes
                            }}</span>
                        </p>
                        <p
                            v-if="review.camp_reason"
                            class="text-sm text-muted-foreground"
                        >
                            <span class="font-medium">K táboru:</span>
                            {{ review.camp_reason }}
                        </p>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>
