<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Overenie e-mailu',
        description: 'Over si e-mailovú adresu kliknutím na odkaz, ktorý sme ti práve poslali.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Overenie e-mailu" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        Na e-mail, ktorý si zadal pri registrácii, sme poslali nový overovací odkaz.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            Poslať overovací e-mail znova
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Odhlásiť sa
        </TextLink>
    </Form>
</template>
