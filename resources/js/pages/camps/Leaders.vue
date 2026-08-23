<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    Crown,
    Link2,
    Mail,
    Pencil,
    Plus,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { ref, watchEffect } from 'vue';
import InviteDialog from '@/components/camp/InviteDialog.vue';
import PersonFormDialog from '@/components/camp/PersonFormDialog.vue';
import ShareLinkDialog from '@/components/camp/ShareLinkDialog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { colorStyle } from '@/lib/campColors';
import { index as campsIndex, show } from '@/routes/camps';
import { destroy as destroyInvitation } from '@/routes/invitations';
import { destroy as destroyLeader } from '@/routes/leaders';
import { destroy as destroyMember } from '@/routes/members';
import type { CampPerson, ShareLink } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    camp: { id: number; name: string; is_owner: boolean };
    people: CampPerson[];
    shareLink: ShareLink | null;
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.camps'), href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: t('nav.camp.leaders'), href: '#' },
        ],
    });
});

const editing = ref<CampPerson | null>(null);
const personDialog = ref(false);
const inviteDialog = ref(false);
const shareDialog = ref(false);
const removingLeader = ref<CampPerson | null>(null);
const removingAccess = ref<CampPerson | null>(null);
const revokingInvite = ref<CampPerson | null>(null);
const copiedKey = ref<string | null>(null);

function add() {
    editing.value = null;
    personDialog.value = true;
}

function edit(person: CampPerson) {
    editing.value = person;
    personDialog.value = true;
}

function removeLeader() {
    const person = removingLeader.value;

    if (!person?.leader_id) {
        return;
    }

    router.delete(
        destroyLeader({ camp: props.camp.id, leader: person.leader_id }).url,
        { preserveScroll: true },
    );
}

function removeAccess() {
    const person = removingAccess.value;

    if (!person?.user_id) {
        return;
    }

    router.delete(
        destroyMember({ camp: props.camp.id, user: person.user_id }).url,
        { preserveScroll: true },
    );
}

function revokeInvite() {
    const person = revokingInvite.value;

    if (!person?.invitation_id) {
        return;
    }

    router.delete(
        destroyInvitation({
            camp: props.camp.id,
            invitation: person.invitation_id,
        }).url,
        { preserveScroll: true },
    );
}

async function copyInvite(person: CampPerson) {
    if (!person.invite_link) {
        return;
    }

    try {
        await navigator.clipboard.writeText(person.invite_link);
        copiedKey.value = person.key;
        setTimeout(() => (copiedKey.value = null), 1500);
    } catch {
        window.prompt(t('members.copyInvitePrompt'), person.invite_link);
    }
}
</script>

<template>
    <Head :title="`${t('nav.camp.leaders')} — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="t('camps.leaders.title')"
                :description="t('camps.leaders.description')"
            />
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button @click="add"> <Plus /> {{ t('leaders.add') }} </Button>
                <Button
                    v-if="camp.is_owner"
                    variant="outline"
                    @click="inviteDialog = true"
                >
                    <Mail /> {{ t('members.inviteByEmail') }}
                </Button>
                <Button
                    v-if="camp.is_owner || shareLink"
                    variant="outline"
                    @click="shareDialog = true"
                >
                    <Link2 /> {{ t('members.shareLink') }}
                </Button>
            </div>
        </div>

        <div class="grid content-start gap-2 sm:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="person in people"
                :key="person.key"
                class="flex items-center justify-between gap-2 rounded-lg border p-3"
                :class="person.status === 'invited' ? 'border-dashed' : ''"
            >
                <div class="flex min-w-0 items-center gap-2.5">
                    <span
                        class="flex size-7 shrink-0 items-center justify-center rounded-full"
                        :class="
                            person.status === 'invited'
                                ? 'bg-muted text-muted-foreground'
                                : colorStyle(person.color).chip
                        "
                    >
                        <component
                            :is="person.status === 'invited' ? Mail : UserRound"
                            class="size-3.5"
                        />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-medium">
                            {{ person.name ?? person.email }}
                        </p>
                        <p
                            v-if="person.name && person.email"
                            class="truncate text-xs text-muted-foreground"
                        >
                            {{ person.email }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    <Badge
                        v-if="person.status === 'owner'"
                        variant="secondary"
                        class="gap-1"
                    >
                        <Crown class="size-3 text-amber-500" />
                        {{ t('camps.role.owner') }}
                    </Badge>
                    <Badge
                        v-else-if="person.status === 'member'"
                        variant="secondary"
                        class="gap-1"
                    >
                        <Check class="size-3" />
                        {{ t('leaders.status.member') }}
                    </Badge>
                    <Badge
                        v-else-if="person.status === 'invited'"
                        variant="outline"
                    >
                        {{ t('leaders.status.invited') }}
                    </Badge>
                    <Badge v-else variant="outline">
                        {{ t('leaders.status.nameOnly') }}
                    </Badge>

                    <template v-if="person.status === 'invited'">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="t('members.link')"
                            @click="copyInvite(person)"
                        >
                            <component
                                :is="copiedKey === person.key ? Check : Copy"
                            />
                        </Button>
                        <Button
                            v-if="camp.is_owner"
                            variant="ghost"
                            size="icon-sm"
                            :title="t('common.remove')"
                            @click="revokingInvite = person"
                        >
                            <Trash2 class="text-destructive" />
                        </Button>
                    </template>

                    <template v-else>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="t('common.rename')"
                            @click="edit(person)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            v-if="
                                camp.is_owner && person.status === 'name_only'
                            "
                            variant="ghost"
                            size="icon-sm"
                            :title="t('leaders.invitePerson')"
                            @click="inviteDialog = true"
                        >
                            <Mail />
                        </Button>
                        <Button
                            v-if="person.status === 'name_only'"
                            variant="ghost"
                            size="icon-sm"
                            :title="t('common.remove')"
                            @click="removingLeader = person"
                        >
                            <Trash2 class="text-destructive" />
                        </Button>
                        <Button
                            v-else-if="
                                camp.is_owner && person.status === 'member'
                            "
                            variant="ghost"
                            size="icon-sm"
                            :title="t('members.removeAccess')"
                            @click="removingAccess = person"
                        >
                            <Trash2 class="text-destructive" />
                        </Button>
                    </template>
                </div>
            </div>

            <p
                v-if="!people.length"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground sm:col-span-2 xl:col-span-3"
            >
                {{ t('leaders.empty') }}
            </p>
        </div>
    </div>

    <PersonFormDialog
        v-model:open="personDialog"
        :camp-id="camp.id"
        :editing="editing"
    />
    <InviteDialog v-model:open="inviteDialog" :camp-id="camp.id" />
    <ShareLinkDialog
        v-model:open="shareDialog"
        :camp-id="camp.id"
        :share-link="shareLink"
        :is-owner="camp.is_owner"
    />

    <ConfirmDialog
        :open="!!removingLeader"
        :title="t('common.remove')"
        :description="
            t('leaders.confirmRemove', { name: removingLeader?.name ?? '' })
        "
        @update:open="removingLeader = null"
        @confirm="removeLeader"
    />
    <ConfirmDialog
        :open="!!removingAccess"
        :title="t('members.removeAccess')"
        :description="t('leaders.removeAccessHint')"
        @update:open="removingAccess = null"
        @confirm="removeAccess"
    />
    <ConfirmDialog
        :open="!!revokingInvite"
        :title="t('members.revokeInvite')"
        :description="
            t('members.confirmRevokeInvite', {
                email: revokingInvite?.email ?? '',
            })
        "
        @update:open="revokingInvite = null"
        @confirm="revokeInvite"
    />
</template>
