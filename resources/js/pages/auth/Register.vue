<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/i18n';
import { login } from '@/routes';
import { privacy, terms } from '@/routes/legal';
import { store } from '@/routes/register';

const { t } = useI18n();

defineProps<{
    passwordRules: string;
}>();

// The layout props carry translated text, so they have to be set during
// render rather than in defineOptions(), which is hoisted out of setup()
// and would freeze the language at module-evaluation time.
watchEffect(() => {
    setLayoutProps({
        title: t('auth.register.title'),
        description: t('auth.register.description'),
    });
});
</script>

<template>
    <Head :title="t('auth.register.head')" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">{{ t('auth.field.name') }}</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    :placeholder="t('auth.placeholder.name')"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('auth.field.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    :placeholder="t('auth.placeholder.email')"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ t('auth.field.password') }}</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    :placeholder="t('auth.field.password')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('auth.field.passwordConfirm')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    :placeholder="t('auth.placeholder.passwordConfirm')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <!--
                The account is only created once this is ticked, which is what
                makes the agreement to the terms and the privacy notice
                demonstrable rather than assumed.
            -->
            <div class="grid gap-2">
                <Label
                    for="terms"
                    class="flex items-start gap-3 text-sm leading-snug font-normal"
                >
                    <Checkbox
                        id="terms"
                        name="terms"
                        :tabindex="5"
                        class="mt-0.5"
                    />
                    <span class="text-muted-foreground">
                        {{ t('auth.register.consent.prefix') }}
                        <TextLink :href="terms()" target="_blank">{{
                            t('auth.register.consent.termsLink')
                        }}</TextLink>
                        {{ t('auth.register.consent.and') }}
                        <TextLink :href="privacy()" target="_blank">{{
                            t('auth.register.consent.privacyLink')
                        }}</TextLink
                        >{{ t('auth.register.consent.suffix') }}
                    </span>
                </Label>
                <InputError :message="errors.terms" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.register.submit') }}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.register.haveAccount') }}
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="7"
                >{{ t('auth.register.loginLink') }}</TextLink
            >
        </div>
    </Form>
</template>
