<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const passwordInput = useTemplateRef('passwordInput');

const page = usePage();

/**
 * Camps cascade off their owner, so deleting the account takes them away from
 * every other leader in them too. Name them in the dialog — an erasure request
 * should not quietly destroy someone else's work.
 */
const ownedCamps = computed<string[]>(
    () => (page.props.ownedCamps as string[] | undefined) ?? [],
);
</script>

<template>
    <div class="space-y-6">
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">
                    {{ t('settings.delete.warningTitle') }}
                </p>
                <p class="text-sm">{{ t('settings.delete.warningBody') }}</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                        >{{ t('settings.delete.button') }}</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{
                                t('settings.delete.confirmTitle')
                            }}</DialogTitle>
                            <DialogDescription>
                                {{ t('settings.delete.confirmBody') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div
                            v-if="ownedCamps.length > 0"
                            class="space-y-2 rounded-md border border-red-200 bg-red-50 p-3 text-sm dark:border-red-200/20 dark:bg-red-700/10"
                        >
                            <p
                                class="font-medium text-red-700 dark:text-red-100"
                            >
                                {{
                                    t('settings.delete.campsWarning', {
                                        count: ownedCamps.length,
                                    })
                                }}
                            </p>
                            <ul
                                class="list-inside list-disc text-red-700/90 dark:text-red-100/90"
                            >
                                <li v-for="camp in ownedCamps" :key="camp">
                                    {{ camp }}
                                </li>
                            </ul>
                            <p
                                class="text-xs text-red-700/80 dark:text-red-100/80"
                            >
                                {{ t('settings.delete.campsHint') }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">{{
                                t('auth.field.password')
                            }}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                :placeholder="t('auth.field.password')"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{ t('common.cancel') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ t('settings.delete.button') }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
