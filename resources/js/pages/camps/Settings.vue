<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref, watchEffect } from 'vue';
import CampAppearanceFields from '@/components/camp/CampAppearanceFields.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useI18n } from '@/i18n';
import {
    index as campsIndex,
    destroy as destroyCamp,
    show,
    update as updateCamp,
} from '@/routes/camps';
import type { Camp } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{ camp: Camp }>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.camps'), href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: t('settings.title'), href: '#' },
        ],
    });
});

const form = useForm({
    name: props.camp.name,
    icon: props.camp.icon,
    color: props.camp.color,
    year: props.camp.year,
    description: props.camp.description ?? '',
    location: props.camp.location ?? '',
    start_date: props.camp.start_date,
    end_date: props.camp.end_date,
});

function submit() {
    form.put(updateCamp(props.camp.id).url, { preserveScroll: true });
}

const confirmingDelete = ref(false);

function deleteCamp() {
    router.delete(destroyCamp(props.camp.id).url);
}
</script>

<template>
    <Head :title="`${t('settings.title')} — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('camps.settings.title')"
            :description="t('camps.settings.description')"
        />

        <Card>
            <CardContent>
                <form
                    class="grid gap-8 lg:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <div class="grid content-start gap-4">
                        <div class="grid gap-2">
                            <Label for="set-name">{{ t('common.name') }}</Label>
                            <Input id="set-name" v-model="form.name" required />
                            <InputError :message="form.errors.name" />
                        </div>

                        <CampAppearanceFields
                            v-model:icon="form.icon"
                            v-model:color="form.color"
                            v-model:location="form.location"
                        />
                    </div>

                    <div class="grid content-start gap-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="set-year">{{
                                    t('camps.field.year')
                                }}</Label>
                                <Input
                                    id="set-year"
                                    v-model="form.year"
                                    type="number"
                                />
                                <InputError :message="form.errors.year" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="set-start">{{
                                    t('camps.field.start')
                                }}</Label>
                                <Input
                                    id="set-start"
                                    v-model="form.start_date"
                                    type="date"
                                />
                                <InputError :message="form.errors.start_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="set-end">{{
                                    t('camps.field.end')
                                }}</Label>
                                <Input
                                    id="set-end"
                                    v-model="form.end_date"
                                    type="date"
                                />
                                <InputError :message="form.errors.end_date" />
                            </div>
                        </div>
                        <p class="-mt-2 text-xs text-muted-foreground">
                            {{ t('camps.settings.datesHint') }}
                        </p>
                        <div class="grid gap-2">
                            <Label for="set-desc">{{
                                t('activities.descriptionLabel')
                            }}</Label>
                            <Textarea
                                id="set-desc"
                                v-model="form.description"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end lg:col-span-2">
                        <Button type="submit" :disabled="form.processing">{{
                            t('common.save')
                        }}</Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card v-if="camp.is_owner" class="border-destructive/40">
            <CardContent
                class="flex flex-wrap items-center justify-between gap-3"
            >
                <div>
                    <p class="font-medium">{{ t('camps.settings.delete') }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('camps.settings.deleteHint') }}
                    </p>
                </div>
                <Button variant="destructive" @click="confirmingDelete = true">
                    <Trash2 /> {{ t('camps.settings.delete') }}
                </Button>
            </CardContent>
        </Card>
    </div>

    <ConfirmDialog
        v-model:open="confirmingDelete"
        :title="t('camps.settings.delete')"
        :description="t('camps.settings.confirmDelete', { name: camp.name })"
        :confirm-label="t('camps.settings.delete')"
        @confirm="deleteCamp"
    />
</template>
