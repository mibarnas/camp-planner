<script setup lang="ts">
import { ref, watch } from 'vue';
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

const open = defineModel<boolean>('open', { required: true });

const slides = [
    'planner',
    'blocks',
    'leaders',
    'feedback',
    'settings',
] as const;

const index = ref(0);

// Always start from the beginning, in case the modal is ever reopened.
watch(open, (value) => {
    if (value) {
        index.value = 0;
    }
});

// A five-point star of radius 10 centred on the origin; positioned per-star
// with a translate so the path itself stays a single constant.
const STAR =
    'M0,-10 L2.9,-3.5 L9.5,-3.1 L4.5,1.5 L5.9,8.1 L0,4.5 L-5.9,8.1 L-4.5,1.5 L-9.5,-3.1 L-2.9,-3.5 Z';
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ t(`camps.onboarding.${slides[index]}.title`) }}
                </DialogTitle>
                <DialogDescription>
                    {{ t(`camps.onboarding.${slides[index]}.body`) }}
                </DialogDescription>
            </DialogHeader>

            <!-- Stylized mini-mockups: token-coloured so they follow the theme,
                 and text-free so they never need translating. -->
            <div class="rounded-xl border bg-muted/30 p-4">
                <svg
                    v-if="slides[index] === 'planner'"
                    viewBox="0 0 320 150"
                    class="h-auto w-full"
                    aria-hidden="true"
                >
                    <g v-for="(x, i) in [10, 88, 166, 244]" :key="i">
                        <rect
                            :x="x"
                            y="8"
                            width="70"
                            height="12"
                            rx="4"
                            class="fill-muted"
                        />
                        <rect
                            :x="x"
                            y="28"
                            width="70"
                            height="114"
                            rx="6"
                            class="fill-card stroke-border"
                            stroke-width="1"
                        />
                    </g>
                    <rect
                        x="14"
                        y="34"
                        width="62"
                        height="24"
                        rx="4"
                        class="fill-chart-1/70"
                    />
                    <rect
                        x="14"
                        y="62"
                        width="62"
                        height="36"
                        rx="4"
                        class="fill-chart-2/70"
                    />
                    <rect
                        x="14"
                        y="102"
                        width="62"
                        height="20"
                        rx="4"
                        class="fill-chart-5/70"
                    />
                    <rect
                        x="92"
                        y="34"
                        width="62"
                        height="30"
                        rx="4"
                        class="fill-chart-4/70"
                    />
                    <rect
                        x="92"
                        y="68"
                        width="62"
                        height="26"
                        rx="4"
                        class="fill-chart-1/70"
                    />
                    <rect
                        x="92"
                        y="98"
                        width="62"
                        height="30"
                        rx="4"
                        class="fill-chart-3/70"
                    />
                    <rect
                        x="170"
                        y="34"
                        width="62"
                        height="24"
                        rx="4"
                        class="fill-chart-2/70"
                    />
                    <rect
                        x="170"
                        y="62"
                        width="62"
                        height="44"
                        rx="4"
                        class="fill-chart-5/70"
                    />
                    <rect
                        x="248"
                        y="34"
                        width="62"
                        height="20"
                        rx="4"
                        class="fill-chart-4/70"
                    />
                    <!-- an entry mid-drag -->
                    <rect
                        x="248"
                        y="62"
                        width="62"
                        height="34"
                        rx="4"
                        class="fill-primary/25 stroke-primary"
                        stroke-width="2"
                        stroke-dasharray="4 3"
                    />
                </svg>

                <svg
                    v-else-if="slides[index] === 'blocks'"
                    viewBox="0 0 320 150"
                    class="h-auto w-full"
                    aria-hidden="true"
                >
                    <g
                        v-for="(row, i) in [
                            { y: 8, h: 18, cls: 'fill-chart-2/70' },
                            { y: 30, h: 24, cls: 'fill-chart-1/70' },
                            { y: 58, h: 16, cls: 'fill-muted' },
                            { y: 78, h: 16, cls: 'fill-muted' },
                            { y: 98, h: 24, cls: 'fill-chart-4/70' },
                            { y: 126, h: 16, cls: 'fill-chart-5/70' },
                        ]"
                        :key="i"
                    >
                        <rect
                            x="10"
                            :y="row.y + row.h / 2 - 2"
                            width="22"
                            height="4"
                            rx="2"
                            class="fill-muted-foreground/40"
                        />
                        <rect
                            x="42"
                            :y="row.y"
                            width="268"
                            :height="row.h"
                            rx="5"
                            :class="row.cls"
                        />
                    </g>
                </svg>

                <svg
                    v-else-if="slides[index] === 'leaders'"
                    viewBox="0 0 320 150"
                    class="h-auto w-full"
                    aria-hidden="true"
                >
                    <g v-for="(cx, i) in [40, 90, 140, 190]" :key="i">
                        <circle :cx="cx" cy="26" r="10" class="fill-muted" />
                        <rect
                            :x="cx - 14"
                            y="40"
                            width="28"
                            height="12"
                            rx="6"
                            class="fill-muted/60"
                        />
                    </g>
                    <!-- invite -->
                    <circle
                        cx="243"
                        cy="26"
                        r="10"
                        class="fill-none stroke-primary"
                        stroke-width="1.5"
                        stroke-dasharray="3 2.5"
                    />
                    <path
                        d="M243,21 v10 M238,26 h10"
                        class="stroke-primary"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                    <g
                        v-for="(g, i) in [
                            { x: 20, cls: 'fill-chart-1' },
                            { x: 170, cls: 'fill-chart-3' },
                        ]"
                        :key="`g${i}`"
                    >
                        <rect
                            :x="g.x"
                            y="72"
                            width="130"
                            height="62"
                            rx="8"
                            class="fill-card stroke-border"
                            stroke-width="1"
                        />
                        <path
                            :d="`M${g.x + 18},86 v34 M${g.x + 18},86 l26,7 l-26,7 z`"
                            :class="`${g.cls} stroke-current`"
                            stroke-width="2"
                            stroke-linejoin="round"
                        />
                        <circle
                            v-for="(cx, j) in [70, 88, 106]"
                            :key="j"
                            :cx="g.x + cx"
                            cy="96"
                            r="7"
                            class="fill-muted"
                        />
                        <rect
                            :x="g.x + 60"
                            y="112"
                            width="56"
                            height="6"
                            rx="3"
                            class="fill-muted-foreground/25"
                        />
                    </g>
                </svg>

                <svg
                    v-else-if="slides[index] === 'feedback'"
                    viewBox="0 0 320 150"
                    class="h-auto w-full"
                    aria-hidden="true"
                >
                    <path
                        v-for="(x, i) in [72, 116, 160, 204, 248]"
                        :key="i"
                        :d="STAR"
                        :transform="`translate(${x}, 26)`"
                        :class="i < 4 ? 'fill-chart-4' : 'fill-muted'"
                    />
                    <!-- leaderboard podium -->
                    <circle cx="160" cy="64" r="9" class="fill-chart-4/60" />
                    <rect
                        x="140"
                        y="78"
                        width="40"
                        height="60"
                        rx="4"
                        class="fill-chart-1/70"
                    />
                    <rect
                        x="94"
                        y="96"
                        width="40"
                        height="42"
                        rx="4"
                        class="fill-chart-2/70"
                    />
                    <rect
                        x="186"
                        y="106"
                        width="40"
                        height="32"
                        rx="4"
                        class="fill-chart-3/70"
                    />
                </svg>

                <svg
                    v-else
                    viewBox="0 0 320 150"
                    class="h-auto w-full"
                    aria-hidden="true"
                >
                    <!-- locked schedule -->
                    <rect
                        x="24"
                        y="38"
                        width="100"
                        height="74"
                        rx="8"
                        class="fill-card stroke-border"
                        stroke-width="1"
                    />
                    <path
                        d="M67,72 v-8 a7,7 0 0 1 14,0 v8"
                        class="fill-none stroke-primary"
                        stroke-width="2"
                    />
                    <rect
                        x="60"
                        y="72"
                        width="28"
                        height="22"
                        rx="4"
                        class="fill-primary/25 stroke-primary"
                        stroke-width="2"
                    />
                    <!-- duplicated into next year -->
                    <path
                        d="M138,75 h34 M164,68 l8,7 l-8,7"
                        class="fill-none stroke-primary"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <rect
                        x="200"
                        y="28"
                        width="94"
                        height="70"
                        rx="8"
                        class="fill-muted stroke-border"
                        stroke-width="1"
                    />
                    <rect
                        x="186"
                        y="48"
                        width="94"
                        height="70"
                        rx="8"
                        class="fill-card stroke-border"
                        stroke-width="1"
                    />
                    <rect
                        v-for="(y, i) in [64, 78, 92]"
                        :key="i"
                        x="200"
                        :y="y"
                        :width="i === 2 ? 40 : 66"
                        height="6"
                        rx="3"
                        class="fill-muted-foreground/30"
                    />
                </svg>
            </div>

            <div class="flex justify-center gap-1.5">
                <button
                    v-for="(slide, i) in slides"
                    :key="slide"
                    type="button"
                    class="size-2 rounded-full transition-colors"
                    :class="
                        i === index ? 'bg-primary' : 'bg-muted-foreground/25'
                    "
                    :aria-label="
                        t('camps.onboarding.goToSlide', { step: i + 1 })
                    "
                    @click="index = i"
                />
            </div>

            <DialogFooter class="sm:justify-between">
                <Button variant="ghost" @click="open = false">
                    {{ t('camps.onboarding.skip') }}
                </Button>
                <div class="flex gap-2">
                    <Button v-if="index > 0" variant="outline" @click="index--">
                        {{ t('common.back') }}
                    </Button>
                    <Button v-if="index < slides.length - 1" @click="index++">
                        {{ t('common.next') }}
                    </Button>
                    <Button v-else @click="open = false">
                        {{ t('camps.onboarding.start') }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
