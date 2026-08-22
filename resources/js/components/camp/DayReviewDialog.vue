<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, ChevronLeft, ChevronRight, MessageCircleQuestion, Sparkles, Star, Tent, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import StarRating from '@/components/camp/StarRating.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { colorStyle } from '@/lib/campColors';
import { durationLabel } from '@/lib/timeline';
import { store as storeReview } from '@/routes/days/review';
import type { CampDay, FeedbackQuestion, ProgramEntry } from '@/types/camp';

const props = withDefaults(
    defineProps<{ open: boolean; day: CampDay | null; questions?: FeedbackQuestion[] }>(),
    { questions: () => [] },
);
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

type RatingRow = { rating: number; reason: string };

const form = useForm({
    ratings: {} as Record<number, RatingRow>,
    notes: '',
    camp_rating: 0,
    camp_reason: '',
    answers: {} as Record<number, string>,
});

const dayQuestions = computed(() => props.questions.filter((q) => q.scope === 'day'));
// Whole-camp questions are asked once, at the end of the last day's review.
const campQuestions = computed(() =>
    props.day?.is_last ? props.questions.filter((q) => q.scope === 'camp') : [],
);

// Only real programme gets rated — nobody needs to score Raňajky out of five.
const items = computed<ProgramEntry[]>(
    () => props.day?.entries.filter((e) => e.kind !== 'simple') ?? [],
);

// The stories are: one screen per activity, then a notes screen, then (last day
// only) the whole-camp screen.
type Step =
    | { kind: 'activity'; entry: ProgramEntry }
    | { kind: 'question'; question: FeedbackQuestion }
    | { kind: 'notes' }
    | { kind: 'camp' };

const steps = computed<Step[]>(() => {
    const list: Step[] = items.value.map((entry) => ({ kind: 'activity', entry }));

    for (const question of dayQuestions.value) {
        list.push({ kind: 'question', question });
    }

    list.push({ kind: 'notes' });

    if (props.day?.is_last) {
        list.push({ kind: 'camp' });

        for (const question of campQuestions.value) {
            list.push({ kind: 'question', question });
        }
    }

    return list;
});

const index = ref(0);
const current = computed<Step | undefined>(() => steps.value[index.value]);
const isLastStep = computed(() => index.value === steps.value.length - 1);
// Direction drives the slide transition (1 = forward, -1 = back).
const dir = ref(1);

const ratedCount = computed(() => Object.values(form.ratings).filter((r) => r.rating > 0).length);

watch(
    () => props.open,
    (open) => {
        if (!open || !props.day) {
            return;
        }

        const existing = props.day.my_review;
        const rows: Record<number, RatingRow> = {};

        for (const e of items.value) {
            const prev = existing?.ratings?.[e.id];
            rows[e.id] = { rating: prev?.rating ?? 0, reason: prev?.reason ?? '' };
        }

        const answers: Record<number, string> = {};

        for (const question of [...dayQuestions.value, ...campQuestions.value]) {
            answers[question.id] = existing?.answers?.[question.id] ?? '';
        }

        form.clearErrors();
        form.defaults({
            ratings: rows,
            notes: existing?.notes ?? '',
            camp_rating: existing?.camp_rating ?? 0,
            camp_reason: existing?.camp_reason ?? '',
            answers,
        });
        form.reset();
        index.value = 0;
        dir.value = 1;
    },
);

function go(delta: number) {
    const next = index.value + delta;

    if (next < 0 || next >= steps.value.length) {
        return;
    }

    dir.value = delta;
    index.value = next;
}

// --- Swipe (mobile) ---
let touchX = 0;
function onTouchStart(e: TouchEvent) {
    touchX = e.changedTouches[0].clientX;
}
function onTouchEnd(e: TouchEvent) {
    const dx = e.changedTouches[0].clientX - touchX;

    if (Math.abs(dx) < 50) {
        return;
    }

    go(dx < 0 ? 1 : -1);
}

function close() {
    emit('update:open', false);
}

function submit() {
    form
        .transform((data) => ({
            notes: data.notes,
            camp_rating: data.camp_rating || null,
            camp_reason: data.camp_rating && data.camp_rating < 5 ? data.camp_reason : null,
            ratings: Object.entries(data.ratings)
                .filter(([, r]) => r.rating > 0)
                .map(([id, r]) => ({
                    program_entry_id: Number(id),
                    rating: r.rating,
                    reason: r.rating < 5 ? r.reason : null,
                })),
            // Blanks travel too — that is how an answer gets cleared.
            answers: Object.entries(data.answers).map(([id, answer]) => ({
                feedback_question_id: Number(id),
                answer,
            })),
        }))
        .post(storeReview(props.day!.id).url, {
            preserveScroll: true,
            onSuccess: () => close(),
        });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            v-if="day"
            :show-close-button="false"
            class="flex h-[100svh] max-h-[860px] w-full flex-col gap-0 overflow-hidden rounded-t-none rounded-b-none border-0 bg-neutral-950 p-0 text-white sm:h-[85svh] sm:max-w-md sm:rounded-t-3xl sm:rounded-b-3xl"
        >
            <!-- Progress segments -->
            <div class="flex shrink-0 items-center gap-1 px-3 pt-3">
                <div
                    v-for="(s, i) in steps"
                    :key="i"
                    class="h-1 flex-1 overflow-hidden rounded-full bg-white/25"
                >
                    <div
                        class="h-full rounded-full bg-white transition-all duration-300"
                        :class="i < index ? 'w-full' : i === index ? 'w-full' : 'w-0'"
                    />
                </div>
            </div>

            <!-- Header -->
            <div class="flex shrink-0 items-center justify-between px-4 pt-3 pb-1">
                <div class="flex items-center gap-2 text-sm font-medium">
                    <Star class="size-4 fill-amber-400 text-amber-400" />
                    <span class="capitalize">{{ day.weekday }} {{ day.label }}</span>
                </div>
                <button type="button" class="rounded-full p-1.5 text-white/70 hover:bg-white/10 hover:text-white" @click="close">
                    <X class="size-5" />
                </button>
            </div>

            <!-- Slide area -->
            <div
                class="relative flex-1 overflow-hidden"
                @touchstart.passive="onTouchStart"
                @touchend.passive="onTouchEnd"
            >
                <Transition :name="dir > 0 ? 'slide-next' : 'slide-prev'" mode="out-in">
                    <div :key="index" class="flex h-full flex-col overflow-y-auto px-6 py-4">
                        <!-- ACTIVITY -->
                        <template v-if="current?.kind === 'activity'">
                            <div class="flex flex-1 flex-col items-center justify-center text-center">
                                <div
                                    class="mb-5 flex size-20 items-center justify-center rounded-3xl text-3xl shadow-lg"
                                    :class="colorStyle(current.entry.activity?.color ?? 'slate').chip"
                                >
                                    <Sparkles class="size-9" />
                                </div>
                                <p class="text-xs font-medium tracking-wide text-white/50 uppercase">
                                    Aktivita {{ index + 1 }} / {{ items.length }}
                                </p>
                                <h2 class="mt-1 text-2xl leading-tight font-bold text-balance">
                                    {{ current.entry.title || current.entry.activity?.name || 'Aktivita' }}
                                </h2>
                                <p class="mt-1 text-sm text-white/60">
                                    {{ current.entry.start_time }} · {{ durationLabel(current.entry.duration) }}
                                </p>

                                <div class="mt-8 flex justify-center">
                                    <StarRating
                                        v-model="form.ratings[current.entry.id].rating"
                                        size="size-11"
                                        gap="gap-1.5"
                                    />
                                </div>
                                <p class="mt-2 h-4 text-xs text-white/40">
                                    {{ form.ratings[current.entry.id].rating ? `${form.ratings[current.entry.id].rating} / 5` : 'Ťukni na hviezdičky' }}
                                </p>

                                <Transition name="fade">
                                    <Textarea
                                        v-if="form.ratings[current.entry.id].rating > 0 && form.ratings[current.entry.id].rating < 5"
                                        v-model="form.ratings[current.entry.id].reason"
                                        class="mt-4 min-h-20 w-full border-white/15 bg-white/5 text-sm text-white placeholder:text-white/40"
                                        placeholder="Čo by sa dalo zlepšiť?"
                                    />
                                </Transition>
                            </div>
                        </template>

                        <!-- NOTES -->
                        <!-- CAMP'S OWN QUESTION -->
                        <template v-else-if="current?.kind === 'question'">
                            <div class="flex flex-1 flex-col justify-center">
                                <div class="mb-5 flex justify-center">
                                    <div class="flex size-16 items-center justify-center rounded-2xl bg-white/10">
                                        <MessageCircleQuestion class="size-8 text-sky-400" />
                                    </div>
                                </div>
                                <h2 class="text-center text-xl font-bold">{{ current.question.text }}</h2>
                                <p class="mt-1 mb-4 text-center text-sm text-white/60">
                                    {{ current.question.scope === 'camp' ? 'Otázka na celý tábor' : 'Voliteľné' }}
                                </p>
                                <Textarea
                                    v-model="form.answers[current.question.id]"
                                    class="min-h-40 border-white/15 bg-white/5 text-sm text-white placeholder:text-white/40"
                                    placeholder="Tvoja odpoveď…"
                                />
                            </div>
                        </template>

                        <template v-else-if="current?.kind === 'notes'">
                            <div class="flex flex-1 flex-col justify-center">
                                <div class="mb-5 flex justify-center">
                                    <div class="flex size-16 items-center justify-center rounded-2xl bg-white/10">
                                        <Star class="size-8 text-amber-400" />
                                    </div>
                                </div>
                                <h2 class="text-center text-xl font-bold">Poznámky k dňu</h2>
                                <p class="mt-1 mb-4 text-center text-sm text-white/60">Voliteľné — čo fungovalo, čo nie.</p>
                                <Textarea
                                    v-model="form.notes"
                                    class="min-h-40 border-white/15 bg-white/5 text-sm text-white placeholder:text-white/40"
                                    placeholder="Celkové postrehy z dňa…"
                                />
                            </div>
                        </template>

                        <!-- CAMP OVERALL (last day) -->
                        <template v-else-if="current?.kind === 'camp'">
                            <div class="flex flex-1 flex-col items-center justify-center text-center">
                                <div class="mb-5 flex size-20 items-center justify-center rounded-3xl bg-primary/20 text-primary shadow-lg">
                                    <Tent class="size-9" />
                                </div>
                                <p class="text-xs font-medium tracking-wide text-white/50 uppercase">Posledný deň</p>
                                <h2 class="mt-1 text-2xl font-bold">Ako celkovo hodnotíš tábor?</h2>
                                <div class="mt-8 flex justify-center">
                                    <StarRating v-model="form.camp_rating" size="size-11" gap="gap-1.5" />
                                </div>
                                <p class="mt-2 h-4 text-xs text-white/40">
                                    {{ form.camp_rating ? `${form.camp_rating} / 5` : 'Ťukni na hviezdičky' }}
                                </p>
                                <Transition name="fade">
                                    <Textarea
                                        v-if="form.camp_rating > 0 && form.camp_rating < 5"
                                        v-model="form.camp_reason"
                                        class="mt-4 min-h-20 w-full border-white/15 bg-white/5 text-sm text-white placeholder:text-white/40"
                                        placeholder="Čo by si na tábore zlepšil?"
                                    />
                                </Transition>
                            </div>
                        </template>
                    </div>
                </Transition>

                <!-- Tap zones for previous/next (desktop-friendly, sit behind content) -->
                <button
                    v-if="index > 0"
                    type="button"
                    class="absolute top-1/2 left-2 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white/80 backdrop-blur hover:bg-white/20"
                    @click="go(-1)"
                >
                    <ChevronLeft class="size-5" />
                </button>
                <button
                    v-if="!isLastStep"
                    type="button"
                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white/80 backdrop-blur hover:bg-white/20"
                    @click="go(1)"
                >
                    <ChevronRight class="size-5" />
                </button>
            </div>

            <!-- Footer -->
            <div class="flex shrink-0 items-center justify-between gap-3 border-t border-white/10 px-4 py-3">
                <span class="text-xs text-white/50">Ohodnotené {{ ratedCount }} / {{ items.length }}</span>
                <Button
                    v-if="isLastStep"
                    type="button"
                    class="bg-primary text-primary-foreground hover:bg-primary/90"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <Check /> Uložiť zhodnotenie
                </Button>
                <Button
                    v-else
                    type="button"
                    variant="secondary"
                    class="bg-white/10 text-white hover:bg-white/20"
                    @click="go(1)"
                >
                    Ďalej <ChevronRight />
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.slide-next-enter-active,
.slide-next-leave-active,
.slide-prev-enter-active,
.slide-prev-leave-active {
    transition:
        transform 0.25s ease,
        opacity 0.25s ease;
}
.slide-next-enter-from {
    transform: translateX(30px);
    opacity: 0;
}
.slide-next-leave-to {
    transform: translateX(-30px);
    opacity: 0;
}
.slide-prev-enter-from {
    transform: translateX(-30px);
    opacity: 0;
}
.slide-prev-leave-to {
    transform: translateX(30px);
    opacity: 0;
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
