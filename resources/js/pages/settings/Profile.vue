<script setup lang="ts">
import { Form, Head, Link, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed, watchEffect } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import SettingsSection from '@/components/SettingsSection.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n';
import { privacy } from '@/routes/legal';
import { dataExport, edit } from '@/routes/profile';
import { send } from '@/routes/verification';

const { t } = useI18n();

// The layout props carry translated text, so they have to be set during
// render rather than in defineOptions(), which is hoisted out of setup()
// and would freeze the language at module-evaluation time.
watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('settings.profile.head'),
                href: edit(),
            },
        ],
    });
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head :title="t('settings.profile.head')" />

    <h1 class="sr-only">{{ t('settings.profile.head') }}</h1>

    <SettingsSection
        :title="t('settings.nav.profile')"
        :description="t('settings.profile.description')"
    >
        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">{{ t('auth.field.name') }}</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    :placeholder="t('auth.placeholder.name')"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('auth.field.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    :placeholder="t('auth.field.email')"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    {{ t('settings.profile.unverified') }}
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        {{ t('settings.profile.resend') }}
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    {{ t('settings.profile.resent') }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-profile-button"
                    >{{ t('common.save') }}</Button
                >
            </div>
        </Form>
    </SettingsSection>

    <!--
        GDPR Art. 15 and Art. 20: the user must be able to get a copy of their
        data, in a machine-readable form, without having to ask anybody.
    -->
    <SettingsSection
        :title="t('settings.data.title')"
        :description="t('settings.data.description')"
    >
        <div class="space-y-4 rounded-lg border p-4">
            <p class="text-sm text-muted-foreground">
                {{ t('settings.data.body') }}
            </p>
            <Button variant="outline" as-child>
                <a :href="dataExport.url()" download>
                    <Download />
                    {{ t('settings.data.download') }}
                </a>
            </Button>
            <p class="text-xs text-muted-foreground">
                {{ t('settings.data.rights') }}
                <TextLink :href="privacy()">{{
                    t('legal.privacy.title')
                }}</TextLink
                >.
            </p>
        </div>
    </SettingsSection>

    <SettingsSection
        :title="t('settings.delete.title')"
        :description="t('settings.delete.description')"
    >
        <DeleteUser />
    </SettingsSection>
</template>
