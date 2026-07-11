<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';
import { index as campsIndex } from '@/routes/camps';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
</script>

<template>
    <Head title="TáborPlanner" />

    <div class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-background px-6 py-12">
        <!-- soft green glow -->
        <div
            class="pointer-events-none absolute -top-40 left-1/2 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-primary/15 blur-3xl"
        />

        <div class="relative z-10 flex max-w-xl flex-col items-center text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg shadow-primary/25">
                <AppLogoIcon class="size-9" />
            </div>

            <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">
                Tábor<span class="text-primary">Planner</span>
            </h1>

            <p class="mt-4 text-balance text-lg text-muted-foreground">
                Plánuj denné tábory pre svoju farnosť — program, aktivity a tímy prehľadne na jednom mieste.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <template v-if="user">
                    <Button as-child size="lg">
                        <Link :href="campsIndex().url">
                            Otvoriť tábory <ArrowRight />
                        </Link>
                    </Button>
                </template>
                <template v-else>
                    <Button as-child size="lg">
                        <Link :href="register().url">
                            Začať plánovať <ArrowRight />
                        </Link>
                    </Button>
                    <Button as-child size="lg" variant="outline">
                        <Link :href="login().url">Prihlásiť sa</Link>
                    </Button>
                </template>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-3 text-left sm:grid-cols-3">
                <div class="rounded-xl border bg-card/60 p-4">
                    <p class="text-sm font-semibold">Denný rozvrh</p>
                    <p class="mt-1 text-xs text-muted-foreground">Časová os s blokmi, presúvaním a priblížením.</p>
                </div>
                <div class="rounded-xl border bg-card/60 p-4">
                    <p class="text-sm font-semibold">Knižnica aktivít</p>
                    <p class="mt-1 text-xs text-muted-foreground">Znovupoužiteľné aktivity s vlastnými kategóriami.</p>
                </div>
                <div class="rounded-xl border bg-card/60 p-4">
                    <p class="text-sm font-semibold">Tím vedúcich</p>
                    <p class="mt-1 text-xs text-muted-foreground">Pozvi animátorov cez odkaz a plánujte spolu.</p>
                </div>
            </div>
        </div>

        <p class="relative z-10 mt-12 text-xs text-muted-foreground">TáborPlanner</p>
    </div>
</template>
