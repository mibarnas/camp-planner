<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { Medal, Trophy, UserRound } from '@lucide/vue';
import { computed, reactive, ref, watch, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { colorStyle } from '@/lib/campColors';
import { index as campsIndex, show } from '@/routes/camps';
import { update as updatePoints } from '@/routes/entries/points';
import type {
    LeaderboardGroup,
    ScoringDay,
    ScoringEntry,
    Standing,
} from '@/types/camp';

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
            { title: 'Tábory', href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: 'Rebríček', href: '#' },
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

const medalColor = ['text-amber-400', 'text-slate-400', 'text-amber-700'];
</script>

<template>
    <Head :title="`Rebríček — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Rebríček skupín"
            description="Priebežné poradie súťažiacich skupín."
        />

        <p
            v-if="!groups.length"
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Žiadne súťažiace skupiny. Pridaj skupiny a zapni im „Súťaží v
            bodovaní".
        </p>

        <template v-else>
            <!-- Standings -->
            <Card class="max-w-3xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Trophy class="size-5 text-amber-500" /> Poradie
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2">
                    <div
                        v-for="(standing, i) in standings"
                        :key="standing.group_id"
                        class="flex items-center gap-3 rounded-lg border p-3"
                        :class="
                            myGroupIds.includes(standing.group_id)
                                ? 'border-primary/50 bg-primary/5'
                                : ''
                        "
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
                        <span class="shrink-0 text-lg font-bold tabular-nums">{{
                            standing.total
                        }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Scoring activities -->
            <div class="grid gap-3">
                <h2 class="text-lg font-semibold">Bodované aktivity</h2>

                <p
                    v-if="!days.length"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    Zatiaľ žiadna bodovaná aktivita. V pláne otvor aktivitu a
                    nastav jej „Bodovanie skupín".
                </p>

                <Card v-for="day in days" :key="day.id" class="gap-3">
                    <CardHeader>
                        <CardTitle class="text-base capitalize"
                            >{{ day.weekday }} {{ day.label }}</CardTitle
                        >
                    </CardHeader>
                    <CardContent
                        class="grid gap-6 xl:grid-cols-2 2xl:grid-cols-3"
                    >
                        <div
                            v-for="entry in day.entries"
                            :key="entry.id"
                            class="grid content-start gap-2"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium">{{ entry.title }}</p>
                                <span class="text-xs text-muted-foreground">{{
                                    entry.start_time
                                }}</span>
                                <Badge variant="outline">
                                    {{
                                        entry.points_mode === 'placement'
                                            ? 'Podľa poradia'
                                            : 'Priame body'
                                    }}
                                </Badge>
                            </div>

                            <div class="max-w-xl overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr
                                            class="text-left text-xs text-muted-foreground"
                                        >
                                            <th class="pb-1 font-medium">
                                                Skupina
                                            </th>
                                            <th class="pb-1 font-medium">
                                                {{
                                                    entry.points_mode ===
                                                    'placement'
                                                        ? 'Výsledok'
                                                        : 'Body'
                                                }}
                                            </th>
                                            <th
                                                v-if="
                                                    entry.points_mode ===
                                                    'placement'
                                                "
                                                class="pb-1 font-medium"
                                            >
                                                Body
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="row in entry.rows"
                                            :key="row.group_id"
                                        >
                                            <td class="py-1 pr-3">
                                                <span
                                                    class="flex items-center gap-2"
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
                                                    {{
                                                        groupById.get(
                                                            row.group_id,
                                                        )?.name
                                                    }}
                                                </span>
                                            </td>
                                            <td class="py-1 pr-3">
                                                <Input
                                                    v-model="
                                                        drafts[entry.id][
                                                            row.group_id
                                                        ]
                                                    "
                                                    type="number"
                                                    step="any"
                                                    inputmode="decimal"
                                                    class="h-8 w-28"
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
                                            </td>
                                            <td
                                                v-if="
                                                    entry.points_mode ===
                                                    'placement'
                                                "
                                                class="py-1 font-medium tabular-nums"
                                            >
                                                {{ row.awarded ?? '—' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex max-w-xl justify-end">
                                <Button
                                    size="sm"
                                    :disabled="saving === entry.id"
                                    @click="save(entry)"
                                >
                                    {{
                                        saving === entry.id
                                            ? 'Ukladám…'
                                            : 'Uložiť body'
                                    }}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>
    </div>
</template>
