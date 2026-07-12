<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock, Package, Sparkles } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Badge } from '@/components/ui/badge';
import { colorStyle } from '@/lib/campColors';
import { durationLabel } from '@/lib/timeline';
import { home } from '@/routes';

defineProps<{
    valid: boolean;
    activity?: {
        name: string;
        category: { name: string; color: string | null } | null;
        description: string | null;
        default_duration: number;
        color: string | null;
        materials: string | null;
    };
}>();
</script>

<template>
    <Head :title="activity?.name ?? 'Aktivita'" />

    <div class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-background px-6 py-12">
        <div class="pointer-events-none absolute -top-40 left-1/2 h-[28rem] w-[28rem] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl" />

        <div class="relative z-10 w-full max-w-md">
            <Link :href="home().url" class="mb-6 flex items-center justify-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                    <AppLogoIcon class="size-5" />
                </span>
                <span class="font-semibold">Tábor<span class="text-primary">Planner</span></span>
            </Link>

            <div v-if="!valid" class="rounded-xl border bg-card p-6 text-center text-sm text-muted-foreground">
                Táto aktivita neexistuje alebo bola odstránená.
            </div>

            <div v-else-if="activity" class="overflow-hidden rounded-2xl border-l-4 border bg-card shadow-sm" :class="colorStyle(activity.color).cell">
                <div class="bg-card/70 p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl" :class="colorStyle(activity.color).chip">
                            <Sparkles class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-xl leading-tight font-bold">{{ activity.name }}</h1>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                <Badge v-if="activity.category" variant="secondary" :class="colorStyle(activity.category.color).chip">
                                    {{ activity.category.name }}
                                </Badge>
                                <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                    <Clock class="size-3.5" /> {{ durationLabel(activity.default_duration) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="activity.description" class="mt-5">
                        <p class="mb-1 text-xs font-medium text-muted-foreground">Popis / scenár</p>
                        <p class="text-sm whitespace-pre-line">{{ activity.description }}</p>
                    </div>
                    <p v-else class="mt-5 text-sm text-muted-foreground italic">Bez popisu.</p>

                    <div v-if="activity.materials" class="mt-4">
                        <p class="mb-1 flex items-center gap-1 text-xs font-medium text-muted-foreground">
                            <Package class="size-3.5" /> Potrebný materiál
                        </p>
                        <p class="text-sm whitespace-pre-line">{{ activity.materials }}</p>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-muted-foreground">
                Aktivita zdieľaná cez <Link :href="home().url" class="underline">TáborPlanner</Link>
            </p>
        </div>
    </div>
</template>
