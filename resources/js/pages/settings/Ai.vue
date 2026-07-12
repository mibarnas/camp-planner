<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
import AiController from '@/actions/App/Http/Controllers/Settings/AiController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'AI súhrny', href: '/settings/ai' }],
    },
});

defineProps<{ hasKey: boolean }>();
</script>

<template>
    <Head title="AI súhrny" />

    <h1 class="sr-only">AI súhrny</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="AI súhrny"
            description="Pridaj vlastný Gemini API kľúč a nechaj AI zhrnúť zhodnotenia z tábora."
        />

        <div class="flex items-start gap-3 rounded-lg border bg-muted/40 p-4 text-sm">
            <Sparkles class="mt-0.5 size-5 shrink-0 text-primary" />
            <div class="space-y-1">
                <p>
                    Kľúč získaš zdarma v
                    <TextLink href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio</TextLink>.
                    Ukladá sa zašifrovane a používa sa len na generovanie súhrnov z hodnotení.
                </p>
                <p v-if="hasKey" class="font-medium text-emerald-600 dark:text-emerald-400">
                    Kľúč je nastavený. Súhrny nájdeš na stránke tábora.
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
                <Label for="gemini_api_key">Gemini API kľúč</Label>
                <Input
                    id="gemini_api_key"
                    name="gemini_api_key"
                    type="password"
                    autocomplete="off"
                    :placeholder="hasKey ? '•••••••••• (nastavený)' : 'AIza…'"
                />
                <InputError :message="errors.gemini_api_key" />
                <p class="text-xs text-muted-foreground">
                    Nechaj prázdne a ulož pre odstránenie kľúča.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Uložiť</Button>
            </div>
        </Form>
    </div>
</template>
