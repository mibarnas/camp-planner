<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { ChevronDown, Medal, Trophy, UserRound } from '@lucide/vue';
import { computed, nextTick, reactive, ref, watch, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/i18n';
import { colorStyle } from '@/lib/campColors';
import { index as campsIndex, show } from '@/routes/camps';
import { update as updatePoints } from '@/routes/entries/points';
import type {
    LeaderboardGroup,
    ScoringDay,
    ScoringEntry,
    Standing,
} from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    camp: { id: number; name: string };
    groups: LeaderboardGroup[];
    standings: Standing[];
    days: ScoringDay[];
    myGroupIds: number[];
    today: string;
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.camps'), href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: t('nav.camp.leaderboard'), href: '#' },
        ],
    });
});

const groupById = computed(() => new Map(props.groups.map((g) => [g.id, g])));
const maxTotal = computed(() =>
    Math.max(...props.standings.map((s) => s.total), 1),
);

/**
 * Draft values per entry, so a whole activity is filled in and saved at once.
 * A number input hands back a number once it is typed in but a string while it
 * is empty, so every read goes through `draftValue()`.
 */
const drafts = reactive<Record<number, Record<number, string | number>>>({});
const saving = ref<number | null>(null);

function seedDrafts() {
    for (const day of props.days) {
        for (const entry of day.entries) {
            const row: Record<number, string | number> = {};

            for (const cell of entry.rows) {
                row[cell.group_id] = cell.value ?? '';
            }

            drafts[entry.id] = row;
        }
    }
}
seedDrafts();
watch(() => props.days, seedDrafts);

function draftValue(entryId: number, groupId: number): string {
    const value = drafts[entryId]?.[groupId];

    return value === null || value === undefined ? '' : String(value).trim();
}

function save(entry: ScoringEntry) {
    saving.value = entry.id;

    router.put(
        updatePoints(entry.id).url,
        {
            points: entry.rows.map((row) => {
                const value = draftValue(entry.id, row.group_id);

                return {
                    camp_group_id: row.group_id,
                    value: value === '' ? null : Number(value),
                };
            }),
        },
        { preserveScroll: true, onFinish: () => (saving.value = null) },
    );
}

/** A past activity still missing this user's group flags itself. */
function isMissing(
    day: ScoringDay,
    entry: ScoringEntry,
    groupId: number,
): boolean {
    if (day.date > props.today || !props.myGroupIds.includes(groupId)) {
        return false;
    }

    return draftValue(entry.id, groupId) === '';
}

function dayHasMissing(day: ScoringDay): boolean {
    return day.entries.some((entry) =>
        entry.rows.some((row) => isMissing(day, entry, row.group_id)),
    );
}

/**
 * Scoring activities that already happened but still have a blank result.
 * Anyone on the page can fill these in, so this is not limited to my groups.
 */
const pending = computed(() => {
    const out: { day: ScoringDay; entry: ScoringEntry }[] = [];

    for (const day of props.days) {
        if (day.date > props.today) {
            continue;
        }

        for (const entry of day.entries) {
            const incomplete = entry.rows.some(
                (row) => draftValue(entry.id, row.group_id) === '',
            );

            if (incomplete) {
                out.push({ day, entry });
            }
        }
    }

    return out;
});

function goToPending() {
    const first = pending.value[0];

    if (!first) {
        return;
    }

    setOpen(first.day.id, true);

    nextTick(() => {
        requestAnimationFrame(() =>
            document
                .getElementById(`entry-${first.entry.id}`)
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' }),
        );
    });
}

// Open the days that still want results; otherwise just the latest one that
// has already happened, so the page does not start as a wall of tables.
const openDays = ref<Set<number>>(new Set());
watch(
    () => props.days,
    () => {
        const next = new Set(
            props.days.filter(dayHasMissing).map((day) => day.id),
        );

        if (!next.size) {
            const past = props.days.filter((day) => day.date <= props.today);
            const fallback = past.at(-1) ?? props.days[0];

            if (fallback) {
                next.add(fallback.id);
            }
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

const medalColor = ['text-amber-400', 'text-slate-400', 'text-amber-700'];
</script>

<template>
    <Head :title="`${t('nav.camp.leaderboard')} — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('leaderboard.title')"
            :description="t('leaderboard.description')"
        />

        <p
            v-if="!groups.length"
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            {{ t('leaderboard.noGroups') }}
        </p>

        <template v-else>
            <!-- Scoring activities that already happened but have no result -->
            <div
                v-if="pending.length"
                class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-950/30"
            >
                <Trophy class="size-5 text-amber-600 dark:text-amber-400" />
                <p class="text-sm">
                    <strong>
                        {{
                            t('leaderboard.pendingCount', {
                                count: pending.length,
                            })
                        }}
                    </strong>
                    {{ t('leaderboard.pendingHint') }}
                </p>
                <Button size="sm" class="ml-auto" @click="goToPending">
                    {{ t('leaderboard.pendingAction') }}
                </Button>
            </div>

            <!-- Standings -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Trophy class="size-5 text-amber-500" />
                        {{ t('leaderboard.standings') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2">
                    <div
                        v-for="(standing, i) in standings"
                        :key="standing.group_id"
                        class="flex items-center gap-3 rounded-lg border p-3"
                        :class="[
                            myGroupIds.includes(standing.group_id)
                                ? 'border-primary/50 bg-primary/5'
                                : '',
                            i < 3 && !myGroupIds.includes(standing.group_id)
                                ? 'bg-muted/40'
                                : '',
                        ]"
                    >
                        <span
                            class="flex w-8 shrink-0 items-center gap-1 font-semibold tabular-nums"
                        >
                            <Medal
                                v-if="i < 3"
                                class="size-4"
                                :class="medalColor[i]"
                            />
                            {{ i + 1 }}.
                        </span>
                        <span
                            class="size-3 shrink-0 rounded-full"
                            :class="
                                colorStyle(
                                    groupById.get(standing.group_id)?.color,
                                ).dot
                            "
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="flex flex-wrap items-center gap-2 font-medium"
                            >
                                {{ groupById.get(standing.group_id)?.name }}
                                <Badge
                                    v-if="
                                        groupById.get(standing.group_id)
                                            ?.type_name
                                    "
                                    variant="outline"
                                >
                                    {{
                                        groupById.get(standing.group_id)
                                            ?.type_name
                                    }}
                                </Badge>
                            </p>
                            <p
                                v-if="
                                    groupById.get(standing.group_id)?.leaders
                                        .length
                                "
                                class="flex items-center gap-1 truncate text-xs text-muted-foreground"
                            >
                                <UserRound class="size-3 shrink-0" />
                                {{
                                    groupById
                                        .get(standing.group_id)
                                        ?.leaders.join(', ')
                                }}
                            </p>
                            <div
                                class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-primary transition-all"
                                    :style="{
                                        width: `${Math.max(0, (standing.total / maxTotal) * 100)}%`,
                                    }"
                                />
                            </div>
                        </div>
                        <span class="shrink-0 text-lg font-bold tabular-nums">
                            {{ standing.total }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- Scoring activities -->
            <div class="grid gap-3">
                <h2 class="text-lg font-semibold">
                    {{ t('leaderboard.scoringActivities') }}
                </h2>

                <p
                    v-if="!days.length"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('leaderboard.noScoring') }}
                </p>

                <Collapsible
                    v-for="day in days"
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
                            <span class="font-medium capitalize">
                                {{ day.weekday }} {{ day.label }}
                            </span>
                            <Badge variant="outline">
                                {{
                                    t('leaderboard.entryCount', {
                                        count: day.entries.length,
                                    })
                                }}
                            </Badge>
                            <Badge
                                v-if="dayHasMissing(day)"
                                variant="outline"
                                class="border-amber-400 text-amber-700 dark:text-amber-400"
                            >
                                {{ t('leaderboard.missingResults') }}
                            </Badge>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <div
                                class="grid gap-3 border-t p-4 xl:grid-cols-2 2xl:grid-cols-3"
                            >
                                <div
                                    v-for="entry in day.entries"
                                    :id="`entry-${entry.id}`"
                                    :key="entry.id"
                                    class="flex flex-col rounded-xl border"
                                >
                                    <div
                                        class="flex items-start gap-2 border-b p-3"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-medium">
                                                {{ entry.title }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground tabular-nums"
                                            >
                                                {{ entry.start_time }}
                                            </p>
                                        </div>
                                        <Badge
                                            variant="outline"
                                            class="shrink-0"
                                        >
                                            {{
                                                entry.points_mode ===
                                                'placement'
                                                    ? t('points.mode.placement')
                                                    : t('points.mode.raw')
                                            }}
                                        </Badge>
                                    </div>

                                    <!--
                                        Flex rows, not a table: the group name
                                        takes the free space so the field always
                                        sits flush with the card's right edge.
                                    -->
                                    <div class="flex-1 p-3 text-sm">
                                        <div
                                            v-for="row in entry.rows"
                                            :key="row.group_id"
                                            class="flex items-center gap-3 border-b py-1.5 last:border-0"
                                        >
                                            <span
                                                class="size-2.5 shrink-0 rounded-full"
                                                :class="
                                                    colorStyle(
                                                        groupById.get(
                                                            row.group_id,
                                                        )?.color,
                                                    ).dot
                                                "
                                            />
                                            <span
                                                class="min-w-0 flex-1 truncate"
                                            >
                                                {{
                                                    groupById.get(row.group_id)
                                                        ?.name
                                                }}
                                            </span>
                                            <span
                                                v-if="
                                                    entry.points_mode ===
                                                    'placement'
                                                "
                                                class="shrink-0 font-medium tabular-nums"
                                                :title="t('points.points')"
                                            >
                                                {{ row.awarded ?? '—' }}
                                            </span>
                                            <Input
                                                v-model="
                                                    drafts[entry.id][
                                                        row.group_id
                                                    ]
                                                "
                                                type="number"
                                                step="any"
                                                inputmode="decimal"
                                                class="h-8 w-24 shrink-0 [appearance:textfield] text-right tabular-nums [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                :class="
                                                    isMissing(
                                                        day,
                                                        entry,
                                                        row.group_id,
                                                    )
                                                        ? 'border-amber-400'
                                                        : ''
                                                "
                                                placeholder="—"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        class="flex justify-end border-t px-3 py-2"
                                    >
                                        <Button
                                            size="sm"
                                            :disabled="saving === entry.id"
                                            @click="save(entry)"
                                        >
                                            {{
                                                saving === entry.id
                                                    ? t('common.saving')
                                                    : t('points.save')
                                            }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Card>
                </Collapsible>
            </div>
        </template>
    </div>
</template>
