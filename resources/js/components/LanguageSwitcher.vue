<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Languages } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { currentLocale, LOCALE_LABELS, LOCALES } from '@/i18n';
import type { Locale } from '@/i18n';
import { update } from '@/routes/locale';

withDefaults(
    defineProps<{
        /** `icon` for tight spots (the header); `inline` for the footer. */
        variant?: 'icon' | 'inline';
    }>(),
    { variant: 'icon' },
);

const active = ref<Locale>(currentLocale());
const switching = ref(false);

/**
 * The catalogs are read once when the bundle is evaluated, which is what lets
 * `t()` work outside `setup()`. Switching therefore reloads the page rather
 * than trying to re-translate a live component tree.
 */
const choose = (locale: Locale) => {
    if (locale === active.value || switching.value) {
        return;
    }

    switching.value = true;

    router.put(
        update.url(),
        { locale },
        {
            preserveScroll: true,
            onSuccess: () => window.location.reload(),
            onError: () => (switching.value = false),
        },
    );
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                :variant="variant === 'icon' ? 'ghost' : 'link'"
                :size="variant === 'icon' ? 'icon' : 'sm'"
                :disabled="switching"
                :class="
                    variant === 'inline'
                        ? 'h-auto p-0 text-xs font-normal text-muted-foreground hover:text-foreground'
                        : ''
                "
                :aria-label="LOCALE_LABELS[active]"
            >
                <Languages :class="variant === 'inline' ? 'size-3.5' : ''" />
                <span v-if="variant === 'inline'">{{
                    LOCALE_LABELS[active]
                }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="locale in LOCALES"
                :key="locale"
                @select="choose(locale)"
            >
                <Check
                    :class="locale === active ? 'opacity-100' : 'opacity-0'"
                />
                {{ LOCALE_LABELS[locale] }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
