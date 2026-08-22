<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { watchEffect } from 'vue';
import CampAppearanceFields from '@/components/camp/CampAppearanceFields.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    index as campsIndex,
    destroy as destroyCamp,
    show,
    update as updateCamp,
} from '@/routes/camps';
import type { Camp } from '@/types/camp';

const props = defineProps<{ camp: Camp }>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tábory', href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: 'Nastavenia', href: '#' },
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

function deleteCamp() {
    if (
        !confirm(
            `Naozaj zmazať tábor „${props.camp.name}"? Táto akcia je nezvratná.`,
        )
    ) {
        return;
    }

    router.delete(destroyCamp(props.camp.id).url);
}
</script>

<template>
    <Head :title="`Nastavenia — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Nastavenia tábora"
            description="Uprav základné údaje alebo zmaž tábor."
        />

        <Card class="max-w-2xl">
            <CardContent>
                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="set-name">Názov</Label>
                        <Input id="set-name" v-model="form.name" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <CampAppearanceFields
                        v-model:icon="form.icon"
                        v-model:color="form.color"
                        v-model:location="form.location"
                    />

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="set-year">Rok</Label>
                            <Input
                                id="set-year"
                                v-model="form.year"
                                type="number"
                            />
                            <InputError :message="form.errors.year" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="set-start">Začiatok</Label>
                            <Input
                                id="set-start"
                                v-model="form.start_date"
                                type="date"
                            />
                            <InputError :message="form.errors.start_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="set-end">Koniec</Label>
                            <Input
                                id="set-end"
                                v-model="form.end_date"
                                type="date"
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </div>
                    <p class="-mt-2 text-xs text-muted-foreground">
                        Dni tábora sú dané dátumami. Zmenou termínu sa posunú
                        (program ostáva); skrátením/predĺžením sa dni odoberú
                        alebo pridajú.
                    </p>
                    <div class="grid gap-2">
                        <Label for="set-desc">Popis</Label>
                        <Textarea id="set-desc" v-model="form.description" />
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing"
                            >Uložiť</Button
                        >
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card v-if="camp.is_owner" class="max-w-2xl border-destructive/40">
            <CardContent
                class="flex flex-wrap items-center justify-between gap-3"
            >
                <div>
                    <p class="font-medium">Zmazať tábor</p>
                    <p class="text-sm text-muted-foreground">
                        Zmaže program, hodnotenia aj históriu. Táto akcia je
                        nezvratná.
                    </p>
                </div>
                <Button variant="destructive" @click="deleteCamp">
                    <Trash2 /> Zmazať tábor
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
