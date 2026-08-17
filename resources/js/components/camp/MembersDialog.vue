<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Check, Copy, Crown, Link2, Mail, Trash2, UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { shareLink as shareLinkRoute } from '@/routes/camps';
import { destroy as destroyInvitation } from '@/routes/invitations';
import { destroy as destroyMember, store as storeMember } from '@/routes/members';
import type { CampInvitation, CampMember, ShareLink } from '@/types/camp';

const props = defineProps<{
    open: boolean;
    campId: number;
    members: CampMember[];
    invitations: CampInvitation[];
    shareLink: ShareLink | null;
    isOwner: boolean;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const form = useForm({ email: '' });
const copiedId = ref<number | null>(null);
const shareLinkCopied = ref(false);

function createShareLink() {
    router.post(shareLinkRoute(props.campId).url, {}, { preserveScroll: true });
}

function revokeShareLink() {
    if (!props.shareLink) {
return;
}

    if (!confirm('Zrušiť zdieľateľný odkaz? Existujúci odkaz prestane fungovať.')) {
return;
}

    router.delete(destroyInvitation({ camp: props.campId, invitation: props.shareLink.id }).url, {
        preserveScroll: true,
    });
}

async function copyShareLink() {
    if (!props.shareLink) {
return;
}

    try {
        await navigator.clipboard.writeText(props.shareLink.link);
        shareLinkCopied.value = true;
        setTimeout(() => (shareLinkCopied.value = false), 1500);
    } catch {
        window.prompt('Skopíruj odkaz:', props.shareLink.link);
    }
}

function invite() {
    form.post(storeMember(props.campId).url, {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
}

function removeMember(member: CampMember) {
    if (!confirm(`Odobrať ${member.name} z tábora?`)) {
        return;
    }

    router.delete(destroyMember({ camp: props.campId, user: member.id }).url, { preserveScroll: true });
}

function cancelInvitation(invitation: CampInvitation) {
    router.delete(destroyInvitation({ camp: props.campId, invitation: invitation.id }).url, {
        preserveScroll: true,
    });
}

async function copyLink(invitation: CampInvitation) {
    try {
        await navigator.clipboard.writeText(invitation.link);
        copiedId.value = invitation.id;
        setTimeout(() => (copiedId.value = null), 1500);
    } catch {
        window.prompt('Skopíruj pozývací odkaz:', invitation.link);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Vedúci tábora</DialogTitle>
                <DialogDescription>
                    Pozvi ďalších animátorov, aby mohli plánovať program spolu s tebou.
                </DialogDescription>
            </DialogHeader>

            <!-- Shareable link (unlimited use) -->
            <div v-if="isOwner || shareLink" class="grid gap-2 rounded-lg border p-3">
                <p class="flex items-center gap-1.5 text-sm font-medium">
                    <Link2 class="size-4" /> Zdieľateľný odkaz
                </p>
                <template v-if="shareLink">
                    <div class="flex items-center gap-1">
                        <Input :model-value="shareLink.link" readonly class="h-8 flex-1 text-xs" @focus="($event.target as HTMLInputElement).select()" />
                        <Button variant="outline" size="sm" @click="copyShareLink">
                            <component :is="shareLinkCopied ? Check : Copy" />
                            {{ shareLinkCopied ? 'Skopírované' : 'Kopírovať' }}
                        </Button>
                        <Button v-if="isOwner" variant="ghost" size="icon-sm" title="Zrušiť odkaz" @click="revokeShareLink">
                            <Trash2 class="text-destructive" />
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Ktokoľvek s odkazom sa môže pridať ako vedúci. Odkaz funguje opakovane.
                    </p>
                </template>
                <template v-else>
                    <Button variant="outline" size="sm" class="w-fit" @click="createShareLink">
                        <Link2 /> Vytvoriť odkaz
                    </Button>
                    <p class="text-xs text-muted-foreground">
                        Vytvorí odkaz na pripojenie bez pozvánky e-mailom — pre neobmedzený počet ľudí.
                    </p>
                </template>
            </div>

            <form v-if="isOwner" class="flex flex-col gap-2 sm:flex-row sm:items-end" @submit.prevent="invite">
                <div class="grid flex-1 gap-2">
                    <Label for="invite-email">Pozvať e-mailom</Label>
                    <Input id="invite-email" v-model="form.email" type="email" placeholder="animator@farnost.sk" />
                    <InputError :message="form.errors.email" />
                </div>
                <Button type="submit" :disabled="form.processing">
                    <UserPlus /> Pozvať
                </Button>
            </form>

            <div class="grid gap-2">
                <p class="text-sm font-medium text-muted-foreground">Členovia ({{ members.length }})</p>
                <div
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center justify-between gap-2 rounded-lg border p-3"
                >
                    <div class="min-w-0">
                        <p class="flex items-center gap-1.5 truncate font-medium">
                            {{ member.name }}
                            <Crown v-if="member.role === 'owner'" class="size-3.5 text-amber-500" />
                        </p>
                        <p class="truncate text-sm text-muted-foreground">{{ member.email }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge :variant="member.role === 'owner' ? 'secondary' : 'outline'">
                            {{ member.role === 'owner' ? 'Vlastník' : 'Vedúci' }}
                        </Badge>
                        <Button
                            v-if="isOwner && member.role !== 'owner'"
                            variant="ghost"
                            size="icon-sm"
                            @click="removeMember(member)"
                        >
                            <Trash2 class="text-destructive" />
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="invitations.length" class="grid gap-2">
                <p class="text-sm font-medium text-muted-foreground">Čakajúce pozvánky</p>
                <div
                    v-for="invitation in invitations"
                    :key="invitation.id"
                    class="flex items-center justify-between gap-2 rounded-lg border border-dashed p-3"
                >
                    <p class="flex min-w-0 items-center gap-1.5 truncate text-sm">
                        <Mail class="size-3.5 shrink-0 text-muted-foreground" />
                        {{ invitation.email }}
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="ghost" size="sm" @click="copyLink(invitation)">
                            <component :is="copiedId === invitation.id ? Check : Copy" />
                            {{ copiedId === invitation.id ? 'Skopírované' : 'Odkaz' }}
                        </Button>
                        <Button v-if="isOwner" variant="ghost" size="icon-sm" @click="cancelInvitation(invitation)">
                            <Trash2 class="text-destructive" />
                        </Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
