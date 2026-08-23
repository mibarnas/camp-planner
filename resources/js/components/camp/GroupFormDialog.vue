<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ColorSelect from '@/components/ColorSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
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
import { store as storeGroup, update as updateGroup } from '@/routes/groups';
import type { CampGroup, CampLeader, GroupType } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    campId: number;
    groupTypes: GroupType[];
    leaders: CampLeader[];
    editing: CampGroup | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const search = ref('');

const form = useForm({
    name: '',
    group_type_id: null as number | null,
    competes: true,
    color: 'emerald' as string,
    leader_ids: [] as number[],
});

const visibleLeaders = computed(() => {
    const needle = search.value.trim().toLowerCase();

    return needle
        ? props.leaders.filter((l) => l.name.toLowerCase().includes(needle))
        : props.leaders;
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    search.value = '';
    form.clearErrors();
    form.defaults({
        name: props.editing?.name ?? '',
        group_type_id: props.editing?.group_type_id ?? null,
        competes: props.editing?.competes ?? true,
        color: props.editing?.color ?? 'emerald',
        leader_ids: props.editing ? [...props.editing.leader_ids] : [],
    });
    form.reset();
});

function toggleLeader(leaderId: number, checked: boolean) {
    form.leader_ids = checked
        ? [...form.leader_ids, leaderId]
        : form.leader_ids.filter((id) => id !== leaderId);
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    if (props.editing) {
        form.put(
            updateGroup({ camp: props.campId, group: props.editing.id }).url,
            options,
        );
    } else {
        form.post(storeGroup(props.campId).url, options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? t('groups.edit') : t('groups.add') }}
                </DialogTitle>
            </DialogHeader>

            <form id="group-form" class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="group-name">{{ t('common.name') }}</Label>
                    <Input
                        id="group-name"
                        v-model="form.name"
                        :placeholder="t('groups.namePlaceholder')"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>{{ t('common.type') }}</Label>
                        <Select
                            :model-value="
                                form.group_type_id === null
                                    ? 'none'
                                    : String(form.group_type_id)
                            "
                            @update:model-value="
                                form.group_type_id =
                                    $event === 'none' ? null : Number($event)
                            "
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {{ t('groups.noType') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="type in groupTypes"
                                    :key="type.id"
                                    :value="String(type.id)"
                                >
                                    {{ type.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <ColorSelect
                        v-model="form.color"
                        :label="t('camps.appearance.color')"
                    />
                </div>

                <label class="flex items-center gap-3 rounded-lg border p-3">
                    <Checkbox
                        :model-value="form.competes"
                        @update:model-value="form.competes = $event === true"
                    />
                    <span>
                        <span class="font-medium">
                            {{ t('groups.competes') }}
                        </span>
                        <span class="block text-xs text-muted-foreground">
                            {{ t('groups.competesHint') }}
                        </span>
                    </span>
                </label>

                <div class="grid gap-2">
                    <Label>{{ t('groups.groupLeaders') }}</Label>
                    <Input
                        v-if="leaders.length > 6"
                        v-model="search"
                        :placeholder="t('groups.searchLeaders')"
                        class="h-8"
                    />
                    <div
                        class="grid max-h-56 gap-1 overflow-y-auto rounded-lg border p-2"
                    >
                        <label
                            v-for="leader in visibleLeaders"
                            :key="leader.id"
                            class="flex items-center gap-2 rounded px-1.5 py-1 text-sm hover:bg-muted"
                        >
                            <Checkbox
                                :model-value="
                                    form.leader_ids.includes(leader.id)
                                "
                                @update:model-value="
                                    toggleLeader(leader.id, $event === true)
                                "
                            />
                            {{ leader.name }}
                            <span
                                v-if="!leader.user_id"
                                class="text-xs text-muted-foreground"
                            >
                                {{ t('groups.noAccountTag') }}
                            </span>
                        </label>
                        <p
                            v-if="!visibleLeaders.length"
                            class="p-2 text-center text-xs text-muted-foreground"
                        >
                            {{
                                leaders.length
                                    ? t('common.noResults')
                                    : t('groups.addLeadersFirst')
                            }}
                        </p>
                    </div>
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    {{ t('common.cancel') }}
                </Button>
                <Button
                    type="submit"
                    form="group-form"
                    :disabled="form.processing"
                >
                    {{ editing ? t('common.save') : t('common.add') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
