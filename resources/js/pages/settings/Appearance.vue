<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import SettingsSection from '@/components/SettingsSection.vue';
import { useI18n } from '@/i18n';
import { edit } from '@/routes/appearance';

const { t } = useI18n();

// The layout props carry translated text, so they have to be set during
// render rather than in defineOptions(), which is hoisted out of setup()
// and would freeze the language at module-evaluation time.
watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('settings.appearance.head'),
                href: edit(),
            },
        ],
    });
});
</script>

<template>
    <Head :title="t('settings.appearance.head')" />

    <h1 class="sr-only">{{ t('settings.appearance.head') }}</h1>

    <SettingsSection
        :title="t('settings.nav.appearance')"
        :description="t('settings.appearance.description')"
    >
        <AppearanceTabs />
    </SettingsSection>
</template>
