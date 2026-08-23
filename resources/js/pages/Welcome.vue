<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { login, register } from '@/routes';
import { index as campsIndex } from '@/routes/camps';

const { t } = useI18n();

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
</script>

<template>
    <Head title="TáborPlanner" />

    <div class="relative flex min-h-svh flex-col overflow-hidden bg-background">
        <!-- soft green glow -->
        <div
            class="pointer-events-none absolute -top-40 left-1/2 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-primary/15 blur-3xl"
        />

        <div
            class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 py-12"
        >
            <div class="flex max-w-xl flex-col items-center text-center">
                <div
                    class="flex size-16 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg shadow-primary/25"
                >
                    <AppLogoIcon class="size-9" />
                </div>

                <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">
                    Tábor<span class="text-primary">Planner</span>
                </h1>

                <p class="mt-4 text-lg text-balance text-muted-foreground">
                    {{ t('welcome.tagline') }}
                </p>

                <div
                    class="mt-8 flex flex-wrap items-center justify-center gap-3"
                >
                    <template v-if="user">
                        <Button as-child size="lg">
                            <Link :href="campsIndex().url">
                                {{ t('welcome.openCamps') }} <ArrowRight />
                            </Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button as-child size="lg">
                            <Link :href="register().url">
                                {{ t('welcome.start') }} <ArrowRight />
                            </Link>
                        </Button>
                        <Button as-child size="lg" variant="outline">
                            <Link :href="login().url">{{
                                t('welcome.login')
                            }}</Link>
                        </Button>
                    </template>
                </div>

                <div
                    class="mt-12 grid grid-cols-1 gap-3 text-left sm:grid-cols-3"
                >
                    <div class="rounded-xl border bg-card/60 p-4">
                        <p class="text-sm font-semibold">
                            {{ t('welcome.feature.schedule.title') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('welcome.feature.schedule.body') }}
                        </p>
                    </div>
                    <div class="rounded-xl border bg-card/60 p-4">
                        <p class="text-sm font-semibold">
                            {{ t('welcome.feature.library.title') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('welcome.feature.library.body') }}
                        </p>
                    </div>
                    <div class="rounded-xl border bg-card/60 p-4">
                        <p class="text-sm font-semibold">
                            {{ t('welcome.feature.team.title') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('welcome.feature.team.body') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <AppFooter variant="bare" class="relative z-10" />
    </div>
</template>
