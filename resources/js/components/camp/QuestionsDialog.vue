<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    Archive,
    CalendarDays,
    ChevronLeft,
    Pencil,
    Plus,
    Tent,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useI18n } from '@/i18n';
import {
    destroy as destroyQuestion,
    store as storeQuestion,
    update as updateQuestion,
} from '@/routes/feedbackQuestions';
import type { FeedbackQuestion } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{ campId: number; questions: FeedbackQuestion[] }>();

const open = defineModel<boolean>('open', { required: true });

const active = computed(() => props.questions.filter((q) => !q.archived));
const archived = computed(() => props.questions.filter((q) => q.archived));

/** The dialog is either browsing the list or editing one question. */
const view = ref<'list' | 'form'>('list');
const editing = ref<FeedbackQuestion | null>(null);
const removing = ref<FeedbackQuestion | null>(null);

const form = useForm({ scope: 'day' as 'day' | 'camp', text: '' });

watch(open, (isOpen) => {
    if (isOpen) {
        view.value = 'list';
        editing.value = null;
    }
});

function startAdd() {
    editing.value = null;
    form.clearErrors();
    form.defaults({ scope: 'day', text: '' });
    form.reset();
    view.value = 'form';
}

function startEdit(question: FeedbackQuestion) {
    editing.value = question;
    form.clearErrors();
    form.defaults({ scope: question.scope, text: question.text });
    form.reset();
    view.value = 'form';
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            view.value = 'list';
            editing.value = null;
        },
    };

    if (editing.value) {
        form.put(
            updateQuestion({
                camp: props.campId,
                feedbackQuestion: editing.value.id,
            }).url,
            options,
        );
    } else {
        form.post(storeQuestion(props.campId).url, options);
    }
}

function remove() {
    if (!removing.value) {
        return;
    }

    router.delete(
        destroyQuestion({
            camp: props.campId,
            feedbackQuestion: removing.value.id,
        }).url,
        { preserveScroll: true },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogScrollContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{
                        view === 'list'
                            ? t('feedback.customQuestions')
                            : editing
                              ? t('questions.edit')
                              : t('questions.add')
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('questions.hint') }}
                </DialogDescription>
            </DialogHeader>

            <!-- List -->
            <div v-if="view === 'list'" class="grid content-start gap-2">
                <div
                    v-for="question in active"
                    :key="question.id"
                    class="flex flex-wrap items-center gap-2 rounded-lg border p-3"
                >
                    <Badge variant="outline" class="gap-1">
                        <component
                            :is="
                                question.scope === 'camp' ? Tent : CalendarDays
                            "
                            class="size-3"
                        />
                        {{
                            question.scope === 'camp'
                                ? t('summary.wholeCamp')
                                : t('questions.everyDay')
                        }}
                    </Badge>
                    <p class="min-w-0 flex-1 text-sm">{{ question.text }}</p>
                    <div class="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="t('common.edit')"
                            @click="startEdit(question)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="t('common.remove')"
                            @click="removing = question"
                        >
                            <X class="text-destructive" />
                        </Button>
                    </div>
                </div>

                <p
                    v-if="!active.length"
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('questions.empty') }}
                </p>

                <div v-if="archived.length" class="grid gap-2 pt-2">
                    <p
                        class="flex items-center gap-1.5 text-sm font-medium text-muted-foreground"
                    >
                        <Archive class="size-3.5" />
                        {{ t('questions.archived') }}
                    </p>
                    <p
                        v-for="question in archived"
                        :key="question.id"
                        class="rounded-lg border border-dashed p-3 text-sm text-muted-foreground"
                    >
                        {{ question.text }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ t('questions.archivedHint') }}
                    </p>
                </div>
            </div>

            <!-- Add / edit -->
            <form
                v-else
                id="question-form"
                class="grid gap-4"
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="question-text">
                        {{ t('questions.question') }}
                    </Label>
                    <Input
                        id="question-text"
                        v-model="form.text"
                        :placeholder="t('questions.placeholder')"
                        required
                    />
                    <InputError :message="form.errors.text" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('questions.when') }}</Label>
                    <Select
                        :model-value="form.scope"
                        @update:model-value="
                            form.scope = $event as 'day' | 'camp'
                        "
                    >
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="day">
                                {{ t('questions.everyDay') }}
                            </SelectItem>
                            <SelectItem value="camp">
                                {{ t('questions.wholeCampLastDay') }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.scope" />
                </div>
            </form>

            <DialogFooter>
                <template v-if="view === 'list'">
                    <Button @click="startAdd">
                        <Plus /> {{ t('questions.add') }}
                    </Button>
                </template>
                <template v-else>
                    <Button variant="outline" @click="view = 'list'">
                        <ChevronLeft /> {{ t('common.back') }}
                    </Button>
                    <Button
                        type="submit"
                        form="question-form"
                        :disabled="form.processing"
                    >
                        {{ editing ? t('common.save') : t('common.add') }}
                    </Button>
                </template>
            </DialogFooter>
        </DialogScrollContent>
    </Dialog>

    <ConfirmDialog
        :open="!!removing"
        :title="t('common.remove')"
        :description="
            t('questions.confirmRemove', { text: removing?.text ?? '' })
        "
        @update:open="removing = null"
        @confirm="remove"
    />
</template>
