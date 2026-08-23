<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Copy, Link2, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/i18n';
import { shareLink as shareLinkRoute } from '@/routes/camps';
import { destroy as destroyInvitation } from '@/routes/invitations';
import type { ShareLink } from '@/types/camp';

const { t } = useI18n();

const props = defineProps<{
    campId: number;
    shareLink: ShareLink | null;
    isOwner: boolean;
}>();

const open = defineModel<boolean>('open', { required: true });

const copied = ref(false);
const confirmingRevoke = ref(false);

function create() {
    router.post(shareLinkRoute(props.campId).url, {}, { preserveScroll: true });
}

function revoke() {
    if (!props.shareLink) {
        return;
    }

    router.delete(
        destroyInvitation({
            camp: props.campId,
            invitation: props.shareLink.id,
        }).url,
        { preserveScroll: true },
    );
}

async function copy() {
    if (!props.shareLink) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.shareLink.link);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch {
        window.prompt(t('members.copyPrompt'), props.shareLink.link);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-1.5">
                    <Link2 class="size-4" /> {{ t('members.shareLink') }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        shareLink
                            ? t('members.shareLinkHint')
                            : t('members.createLinkHint')
                    }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="shareLink" class="flex items-center gap-1">
                <Input
                    :model-value="shareLink.link"
                    readonly
                    class="h-9 flex-1 text-xs"
                    @focus="($event.target as HTMLInputElement).select()"
                />
                <Button variant="outline" size="sm" @click="copy">
                    <component :is="copied ? Check : Copy" />
                    {{ copied ? t('common.copied') : t('common.copy') }}
                </Button>
                <Button
                    v-if="isOwner"
                    variant="ghost"
                    size="icon-sm"
                    :title="t('members.revokeLink')"
                    @click="confirmingRevoke = true"
                >
                    <Trash2 class="text-destructive" />
                </Button>
            </div>
            <Button
                v-else-if="isOwner"
                variant="outline"
                class="w-fit"
                @click="create"
            >
                <Link2 /> {{ t('members.createLink') }}
            </Button>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="confirmingRevoke"
        :title="t('members.revokeLink')"
        :description="t('members.confirmRevokeLink')"
        @confirm="revoke"
    />
</template>
