<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/i18n';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const { t } = useI18n();

// The layout props carry translated text, so they have to be set during
// render rather than in defineOptions(), which is hoisted out of setup()
// and would freeze the language at module-evaluation time.
watchEffect(() => {
    setLayoutProps({
        title: t('auth.verify.title'),
        description: t('auth.verify.description'),
    });
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.verify.title')" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ t('auth.verify.resent') }}
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            {{ t('auth.verify.resend') }}
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            {{ t('user.logout') }}
        </TextLink>
    </Form>
</template>
