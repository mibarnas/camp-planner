<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { Pencil, Plus, Tag, Trophy, UserRound, X } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import GroupFormDialog from '@/components/camp/GroupFormDialog.vue';
import GroupTypeFormDialog from '@/components/camp/GroupTypeFormDialog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { colorStyle } from '@/lib/campColors';
import { index as campsIndex, show } from '@/routes/camps';
import { destroy as destroyGroup } from '@/routes/groups';
import { destroy as destroyType } from '@/routes/groupTypes';
import type { CampGroup, CampLeader, GroupType } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    camp: { id: number; name: string };
    groups: CampGroup[];
    groupTypes: GroupType[];
    leaders: CampLeader[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.camps'), href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: t('nav.camp.groups'), href: '#' },
        ],
    });
});

const typeById = computed(
    () => new Map(props.groupTypes.map((type) => [type.id, type])),
);
const leaderById = computed(() => new Map(props.leaders.map((l) => [l.id, l])));

const editingGroup = ref<CampGroup | null>(null);
const groupDialog = ref(false);
const editingType = ref<GroupType | null>(null);
const typeDialog = ref(false);
const removingGroup = ref<CampGroup | null>(null);
const removingType = ref<GroupType | null>(null);

function addGroup() {
    editingGroup.value = null;
    groupDialog.value = true;
}

function editGroup(group: CampGroup) {
    editingGroup.value = group;
    groupDialog.value = true;
}

function addType() {
    editingType.value = null;
    typeDialog.value = true;
}

function editType(type: GroupType) {
    editingType.value = type;
    typeDialog.value = true;
}

function removeGroup() {
    if (!removingGroup.value) {
        return;
    }

    router.delete(
        destroyGroup({ camp: props.camp.id, group: removingGroup.value.id })
            .url,
        { preserveScroll: true },
    );
}

function removeType() {
    if (!removingType.value) {
        return;
    }

    router.delete(
        destroyType({ camp: props.camp.id, groupType: removingType.value.id })
            .url,
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`${t('nav.camp.groups')} — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="t('nav.camp.groups')"
                :description="t('groups.description')"
            />
            <Button class="shrink-0 sm:w-fit" @click="addGroup">
                <Plus /> {{ t('groups.add') }}
            </Button>
        </div>

        <!-- Group types: a slim strip of chips above the groups they label -->
        <div class="flex flex-wrap items-center gap-2">
            <span
                class="flex items-center gap-1.5 text-sm text-muted-foreground"
            >
                <Tag class="size-4" /> {{ t('groups.types') }}
            </span>
            <span
                v-for="type in groupTypes"
                :key="type.id"
                class="flex items-center gap-1.5 rounded-full border py-1 pr-1 pl-3 text-sm"
            >
                <span
                    class="size-2.5 rounded-full"
                    :class="colorStyle(type.color).dot"
                />
                {{ type.name }}
                <Button
                    variant="ghost"
                    size="icon-sm"
                    :title="t('common.rename')"
                    @click="editType(type)"
                >
                    <Pencil />
                </Button>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    :title="t('common.remove')"
                    @click="removingType = type"
                >
                    <X class="text-destructive" />
                </Button>
            </span>
            <Button
                variant="outline"
                size="sm"
                class="rounded-full"
                @click="addType"
            >
                <Plus /> {{ t('groups.newType') }}
            </Button>
        </div>

        <div
            class="grid content-start gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <div
                v-for="group in groups"
                :key="group.id"
                class="grid content-start gap-2 rounded-xl border p-3"
            >
                <!-- Name and actions stay on one line; badges wrap below it. -->
                <div class="flex items-start gap-2">
                    <span
                        class="mt-1.5 size-3 shrink-0 rounded-full"
                        :class="colorStyle(group.color).dot"
                    />
                    <p class="min-w-0 flex-1 truncate font-medium">
                        {{ group.name }}
                    </p>
                    <div class="-mt-1 -mr-1 flex shrink-0 gap-0.5">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="t('common.edit')"
                            @click="editGroup(group)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="t('common.remove')"
                            @click="removingGroup = group"
                        >
                            <X class="text-destructive" />
                        </Button>
                    </div>
                </div>

                <div
                    v-if="group.group_type_id || group.competes"
                    class="flex flex-wrap items-center gap-1.5"
                >
                    <Badge v-if="group.group_type_id" variant="outline">
                        {{ typeById.get(group.group_type_id)?.name }}
                    </Badge>
                    <Badge
                        v-if="group.competes"
                        variant="secondary"
                        class="gap-1"
                    >
                        <Trophy class="size-3" />
                        {{ t('groups.competing') }}
                    </Badge>
                </div>
                <div
                    v-if="group.leader_ids.length"
                    class="flex flex-wrap gap-1.5"
                >
                    <Badge
                        v-for="leaderId in group.leader_ids"
                        :key="leaderId"
                        variant="outline"
                        class="gap-1 font-normal"
                    >
                        <UserRound class="size-3" />
                        {{ leaderById.get(leaderId)?.name }}
                    </Badge>
                </div>
                <p v-else class="text-xs text-muted-foreground">
                    {{ t('groups.noLeaders') }}
                </p>
            </div>
            <p
                v-if="!groups.length"
                class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground sm:col-span-2 xl:col-span-3 2xl:col-span-4"
            >
                {{ t('groups.empty') }}
            </p>
        </div>
    </div>

    <GroupFormDialog
        v-model:open="groupDialog"
        :camp-id="camp.id"
        :group-types="groupTypes"
        :leaders="leaders"
        :editing="editingGroup"
    />
    <GroupTypeFormDialog
        v-model:open="typeDialog"
        :camp-id="camp.id"
        :editing="editingType"
    />

    <ConfirmDialog
        :open="!!removingGroup"
        :title="t('common.remove')"
        :description="
            t('groups.confirmRemove', { name: removingGroup?.name ?? '' })
        "
        @update:open="removingGroup = null"
        @confirm="removeGroup"
    />
    <ConfirmDialog
        :open="!!removingType"
        :title="t('common.remove')"
        :description="
            t('groups.confirmRemoveType', { name: removingType?.name ?? '' })
        "
        @update:open="removingType = null"
        @confirm="removeType"
    />
</template>
