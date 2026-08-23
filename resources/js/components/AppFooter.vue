<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useI18n } from '@/i18n';
import { privacy, terms } from '@/routes/legal';

const { t } = useI18n();

withDefaults(
    defineProps<{
        /** `contained` adds the page padding the app pages already use. */
        variant?: 'contained' | 'bare';
    }>(),
    { variant: 'contained' },
);

const year = new Date().getFullYear();
</script>

<template>
    <footer
        class="mt-auto w-full border-t border-border/60 text-xs text-muted-foreground"
        :class="variant === 'contained' ? 'px-4 py-4 md:px-6' : 'px-6 py-6'"
    >
        <div
            class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-2 sm:flex-row"
        >
            <p class="text-center sm:text-left">
                © {{ year }} TáborPlanner ·
                <span>
                    Vibe-coded by
                    <a
                        href="https://barnas.net"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-foreground underline decoration-border underline-offset-4 transition-colors hover:decoration-current"
                    >
                        Michal Barnas
                    </a>
                </span>
            </p>

            <nav class="flex items-center gap-4">
                <a
                    href="https://github.com/mibarnas/camp-planner"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="transition-colors hover:text-foreground"
                >
                    {{ t('footer.source') }}
                </a>
                <Link
                    :href="privacy()"
                    class="transition-colors hover:text-foreground"
                >
                    {{ t('legal.privacy.short') }}
                </Link>
                <Link
                    :href="terms()"
                    class="transition-colors hover:text-foreground"
                >
                    {{ t('legal.terms.short') }}
                </Link>
                <LanguageSwitcher variant="inline" />
            </nav>
        </div>
    </footer>
</template>
