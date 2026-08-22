<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Archive, CalendarDays, Pencil, Plus, Tent, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    destroy as destroyQuestion,
    store as storeQuestion,
    update as updateQuestion,
} from '@/routes/feedbackQuestions';
import type { FeedbackQuestion } from '@/types/camp';

const props = defineProps<{ campId: number; questions: FeedbackQuestion[] }>();

const active = computed(() => props.questions.filter((q) => !q.archived));
const archived = computed(() => props.questions.filter((q) => q.archived));

const editingId = ref<number | null>(null);
const form = useForm({ scope: 'day' as 'day' | 'camp', text: '' });

function startNew() {
    editingId.value = null;
    form.clearErrors();
    form.defaults({ scope: 'day', text: '' });
    form.reset();
}

function startEdit(question: FeedbackQuestion) {
    editingId.value = question.id;
    form.clearErrors();
    form.defaults({ scope: question.scope, text: question.text });
    form.reset();
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => startNew() };

    if (editingId.value) {
        form.put(
            updateQuestion({
                camp: props.campId,
                feedbackQuestion: editingId.value,
            }).url,
            options,
        );
    } else {
        form.post(storeQuestion(props.campId).url, options);
    }
}

function remove(question: FeedbackQuestion) {
    if (
        !confirm(
            `Odstrániť otázku „${question.text}"? Ak už na ňu niekto odpovedal, len sa skryje.`,
        )
    ) {
        return;
    }

    router.delete(
        destroyQuestion({ camp: props.campId, feedbackQuestion: question.id })
            .url,
        {
            preserveScroll: true,
            onSuccess: () => {
                if (editingId.value === question.id) {
                    startNew();
                }
            },
        },
    );
}
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
        <div class="grid content-start gap-2">
            <div
                v-for="question in active"
                :key="question.id"
                class="flex flex-wrap items-center gap-2 rounded-lg border p-3"
                :class="editingId === question.id ? 'border-primary' : ''"
            >
                <Badge variant="outline" class="gap-1">
                    <component
                        :is="question.scope === 'camp' ? Tent : CalendarDays"
                        class="size-3"
                    />
                    {{ question.scope === 'camp' ? 'Celý tábor' : 'Každý deň' }}
                </Badge>
                <p class="min-w-0 flex-1">{{ question.text }}</p>
                <div class="flex gap-1">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        title="Upraviť"
                        @click="startEdit(question)"
                    >
                        <Pencil />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        title="Odstrániť"
                        @click="remove(question)"
                    >
                        <X class="text-destructive" />
                    </Button>
                </div>
            </div>

            <p
                v-if="!active.length"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                Žiadne vlastné otázky. Pridaj otázku a vedúci na ňu odpovedia
                priamo v zhodnotení dňa.
            </p>

            <div v-if="archived.length" class="grid gap-2 pt-2">
                <p
                    class="flex items-center gap-1.5 text-sm font-medium text-muted-foreground"
                >
                    <Archive class="size-3.5" /> Skryté otázky
                </p>
                <p
                    v-for="question in archived"
                    :key="question.id"
                    class="rounded-lg border border-dashed p-3 text-sm text-muted-foreground"
                >
                    {{ question.text }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Už sa nepýtajú, ale odpovede na ne ostávajú v zhodnoteniach
                    nižšie.
                </p>
            </div>
        </div>

        <form
            class="grid content-start gap-3 rounded-lg border bg-muted/30 p-3"
            @submit.prevent="submit"
        >
            <p class="text-sm font-medium">
                {{ editingId ? 'Upraviť otázku' : 'Pridať otázku' }}
            </p>
            <div class="grid gap-2">
                <Label for="question-text">Otázka</Label>
                <Input
                    id="question-text"
                    v-model="form.text"
                    placeholder="Napr. Čo by si na dnešku zmenil?"
                    required
                />
                <InputError :message="form.errors.text" />
            </div>
            <div class="grid gap-2">
                <Label>Kedy sa pýtať</Label>
                <Select
                    :model-value="form.scope"
                    @update:model-value="form.scope = $event as 'day' | 'camp'"
                >
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="day">Každý deň</SelectItem>
                        <SelectItem value="camp"
                            >Celý tábor (posledný deň)</SelectItem
                        >
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.scope" />
            </div>
            <div class="flex gap-2 pt-1">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="flex-1"
                >
                    <Plus v-if="!editingId" />
                    {{ editingId ? 'Uložiť' : 'Pridať' }}
                </Button>
                <Button
                    v-if="editingId"
                    type="button"
                    variant="outline"
                    @click="startNew"
                    >Nová</Button
                >
            </div>
            <p class="text-xs text-muted-foreground">
                Otázky sa vedúcim zobrazia ako ďalšie kroky pri zhodnotení dňa.
                Otázky na celý tábor sa pýtajú raz, v posledný deň.
            </p>
        </form>
    </div>
</template>
