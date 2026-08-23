<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/i18n';
import { changelogOpen as open } from '@/lib/changelog';
import { getJson } from '@/lib/http';
import { seen, show } from '@/routes/changelog';
import type { Changelog } from '@/types/changelog';

const { t } = useI18n();

const page = usePage();

// The prop is shared, so it is re-sent on every page load until acknowledged —
// keep a local copy so the dialog can animate out after the acknowledgement
// request clears it server-side, and so re-opening it from the sidebar in the
// same session does not need another round trip.
const changelog = ref<Changelog | null>(page.props.changelog);

const loading = ref(false);

watch(
    () => page.props.changelog,
    (value) => {
        if (value) {
            changelog.value = value;
            open.value = true;
        }
    },
    { immediate: true },
);

watch(open, (value) => {
    if (value) {
        void load();

        return;
    }

    // Any way out counts as read — the button, the X, Escape, the overlay.
    // Only the unread notes need acknowledging; re-reading them from the
    // sidebar later is not an event the server cares about.
    if (page.props.changelog) {
        router.post(
            seen.url(),
            {},
            { preserveScroll: true, preserveState: true, only: ['changelog'] },
        );
    }
});

/**
 * Fetch the notes when the sidebar opens the dialog on a page that was not
 * given them — which is every page, once they have been read.
 */
async function load(): Promise<void> {
    if (changelog.value || loading.value) {
        return;
    }

    loading.value = true;

    try {
        changelog.value = await getJson<Changelog>(show.url());
    } catch (e) {
        open.value = false;
        toast.error(e instanceof Error ? e.message : t('common.error'));
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogScrollContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{ t('changelog.title') }}</DialogTitle>
                <DialogDescription>
                    {{
                        changelog
                            ? t('changelog.version', {
                                  version: changelog.version,
                              })
                            : ''
                    }}
                </DialogDescription>
            </DialogHeader>

            <!-- Only reached when the sidebar opens the dialog on a page that
                 was not shipped the notes. -->
            <div v-if="!changelog" class="flex justify-center py-10">
                <Spinner class="size-6 text-muted-foreground" />
            </div>

            <!-- eslint-disable-next-line vue/no-v-html -- rendered server-side from our own markdown files, raw HTML escaped -->
            <article v-else class="changelog-prose" v-html="changelog.html" />

            <DialogFooter>
                <Button @click="open = false">{{
                    t('changelog.dismiss')
                }}</Button>
            </DialogFooter>
        </DialogScrollContent>
    </Dialog>
</template>

<style scoped>
/*
 * The project does not use @tailwindcss/typography, so the rendered markdown
 * gets its own small prose sheet — a trimmed version of the one in
 * pages/legal/Document.vue, which is scoped there and cannot be shared.
 */
.changelog-prose {
    color: var(--foreground);
    font-size: 0.875rem;
    line-height: 1.7;
}

.changelog-prose :deep(h2) {
    font-size: 1rem;
    font-weight: 600;
    margin-top: 1.75rem;
    margin-bottom: 0.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--border);
}

/* The lead paragraph sits above the first section, so it needs no rule. */
.changelog-prose :deep(h2:first-child) {
    margin-top: 0;
    padding-top: 0;
    border-top: 0;
}

.changelog-prose :deep(h3) {
    font-size: 0.9375rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.375rem;
}

.changelog-prose :deep(p) {
    margin-bottom: 0.75rem;
}

.changelog-prose :deep(ul),
.changelog-prose :deep(ol) {
    margin-bottom: 0.75rem;
    padding-left: 1.25rem;
    list-style: disc;
    color: var(--muted-foreground);
}

.changelog-prose :deep(ol) {
    list-style: decimal;
}

.changelog-prose :deep(li) {
    margin-bottom: 0.375rem;
}

.changelog-prose :deep(strong) {
    font-weight: 600;
    color: var(--foreground);
}

.changelog-prose :deep(a) {
    color: var(--primary);
    text-decoration: underline;
    text-underline-offset: 3px;
}
</style>
