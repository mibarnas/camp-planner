<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useI18n } from '@/i18n';
import { home } from '@/routes';

const { t } = useI18n();

const props = defineProps<{
    document: 'privacy' | 'terms';
    html: string;
    updatedAt: string | null;
}>();

const title = computed(() =>
    props.document === 'privacy'
        ? t('legal.privacy.title')
        : t('legal.terms.title'),
);
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-svh flex-col bg-background">
        <header class="border-b border-border/60">
            <div
                class="mx-auto flex w-full max-w-3xl items-center justify-between gap-4 px-6 py-4"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-2 font-medium"
                >
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                    >
                        <AppLogoIcon class="size-5" />
                    </span>
                    <span class="text-sm font-semibold"
                        >Tábor<span class="text-primary">Planner</span></span
                    >
                </Link>

                <Link
                    :href="home()"
                    class="flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    {{ t('legal.back') }}
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-3xl flex-1 px-6 py-10">
            <!-- eslint-disable-next-line vue/no-v-html -- rendered server-side from our own markdown files, raw HTML escaped -->
            <article class="legal-prose" v-html="html" />
        </main>

        <AppFooter variant="bare" />
    </div>
</template>

<style scoped>
/*
 * The project does not use @tailwindcss/typography, so the rendered markdown
 * gets its own small prose sheet. Kept here rather than in app.css because
 * nothing else needs it.
 */
.legal-prose {
    color: var(--foreground);
    line-height: 1.7;
}

.legal-prose :deep(h1) {
    font-size: 1.875rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin-bottom: 1.5rem;
}

.legal-prose :deep(h2) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 2.5rem;
    margin-bottom: 0.75rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}

.legal-prose :deep(h3) {
    font-size: 1rem;
    font-weight: 600;
    margin-top: 1.75rem;
    margin-bottom: 0.5rem;
}

.legal-prose :deep(p) {
    margin-bottom: 1rem;
}

.legal-prose :deep(ul),
.legal-prose :deep(ol) {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
    list-style: disc;
}

.legal-prose :deep(ol) {
    list-style: decimal;
}

.legal-prose :deep(li) {
    margin-bottom: 0.375rem;
}

.legal-prose :deep(strong) {
    font-weight: 600;
}

.legal-prose :deep(code) {
    font-size: 0.875em;
    padding: 0.1em 0.35em;
    border-radius: 0.25rem;
    background-color: var(--muted);
}

.legal-prose :deep(a) {
    color: var(--primary);
    text-decoration: underline;
    text-underline-offset: 3px;
}

/* Tables carry the data/retention/legal-basis matrices; they must scroll on a phone. */
.legal-prose :deep(table) {
    display: block;
    width: 100%;
    overflow-x: auto;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}

.legal-prose :deep(th),
.legal-prose :deep(td) {
    border: 1px solid var(--border);
    padding: 0.5rem 0.75rem;
    text-align: left;
    vertical-align: top;
}

.legal-prose :deep(th) {
    background-color: var(--muted);
    font-weight: 600;
}
</style>
