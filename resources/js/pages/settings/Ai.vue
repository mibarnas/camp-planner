<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { Sparkles, TriangleAlert } from '@lucide/vue';
import { watchEffect } from 'vue';
import AiController from '@/actions/App/Http/Controllers/Settings/AiController';
import InputError from '@/components/InputError.vue';
import SettingsSection from '@/components/SettingsSection.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n';
import { privacy } from '@/routes/legal';

const { t } = useI18n();

// The layout props carry translated text, so they have to be set during
// render rather than in defineOptions(), which is hoisted out of setup()
// and would freeze the language at module-evaluation time.
watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('settings.nav.ai'), href: '/settings/ai' }],
    });
});

defineProps<{ hasKey: boolean }>();
</script>

<template>
    <Head :title="t('settings.nav.ai')" />

    <h1 class="sr-only">{{ t('settings.nav.ai') }}</h1>

    <SettingsSection
        :title="t('settings.nav.ai')"
        :description="t('settings.ai.description')"
    >
        <div class="flex flex-col space-y-6">
            <div
                class="flex items-start gap-3 rounded-lg border bg-muted/40 p-4 text-sm"
            >
                <Sparkles class="mt-0.5 size-5 shrink-0 text-primary" />
                <div class="space-y-1">
                    <p>
                        {{ t('settings.ai.keyHintPrefix') }}
                        <TextLink
                            href="https://aistudio.google.com/app/apikey"
                            target="_blank"
                            rel="noopener"
                            >Google AI Studio</TextLink
                        >{{ t('settings.ai.keyHintSuffix') }}
                    </p>
                    <p
                        v-if="hasKey"
                        class="font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        {{ t('settings.ai.keySet') }}
                    </p>
                </div>
            </div>

            <!--
            GDPR transparency: using this feature ships camp text and leaders'
            written feedback to Google in the US. Say so where the choice is
            actually made, not only in the privacy policy.
        -->
            <div
                class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm dark:border-amber-200/20 dark:bg-amber-500/10"
            >
                <TriangleAlert
                    class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <div class="space-y-1">
                    <p class="font-medium">
                        {{ t('settings.ai.transferTitle') }}
                    </p>
                    <p class="text-muted-foreground">
                        {{ t('settings.ai.transferBody') }}
                    </p>
                    <p class="text-muted-foreground">
                        {{ t('settings.ai.transferMore') }}
                        <TextLink :href="privacy()">{{
                            t('legal.privacy.title')
                        }}</TextLink
                        >.
                    </p>
                </div>
            </div>

            <Form
                v-bind="AiController.update.form()"
                :options="{ preserveScroll: true }"
                :reset-on-success="['gemini_api_key']"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="gemini_api_key">{{
                        t('settings.ai.keyLabel')
                    }}</Label>
                    <Input
                        id="gemini_api_key"
                        name="gemini_api_key"
                        type="password"
                        autocomplete="off"
                        :placeholder="
                            hasKey
                                ? t('settings.ai.keyPlaceholderSet')
                                : 'AIza…'
                        "
                    />
                    <InputError :message="errors.gemini_api_key" />
                    <p class="text-xs text-muted-foreground">
                        {{ t('settings.ai.keyClearHint') }}
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing">{{
                        t('common.save')
                    }}</Button>
                </div>
            </Form>
        </div>
    </SettingsSection>
</template>
