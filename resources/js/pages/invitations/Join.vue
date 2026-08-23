<script setup lang="ts">
import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { CalendarDays, Check, LogIn, Tent, UserPlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { login, register } from '@/routes';
import { show as campShow } from '@/routes/camps';
import { accept } from '@/routes/invitations';

const { t } = useI18n();

const props = defineProps<{
    valid: boolean;
    token?: string;
    invitedEmail?: string;
    inviter?: { name: string } | null;
    camp?: {
        id: number;
        name: string;
        year: number;
        description: string | null;
    };
    alreadyMember?: boolean;
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

setLayoutProps({
    title: t('invite.title'),
    description: props.valid ? t('invite.subtitle') : t('invite.short'),
});

const joining = ref(false);
function join() {
    if (!props.token) {
        return;
    }

    router.post(
        accept(props.token).url,
        {},
        {
            onStart: () => (joining.value = true),
            onFinish: () => (joining.value = false),
        },
    );
}
</script>

<template>
    <Head :title="t('invite.title')" />

    <!-- Invalid / expired -->
    <div v-if="!valid" class="flex flex-col items-center gap-4 text-center">
        <p class="text-sm text-muted-foreground">
            {{ t('invite.invalid') }}
        </p>
        <Button as-child variant="outline">
            <Link :href="login().url">{{ t('auth.login.submit') }}</Link>
        </Button>
    </div>

    <!-- Valid invite -->
    <div v-else class="flex flex-col gap-6">
        <div class="rounded-xl border bg-card p-5 text-center">
            <div
                class="mx-auto flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
            >
                <Tent class="size-6" />
            </div>
            <h2 class="mt-3 text-lg font-semibold">{{ camp?.name }}</h2>
            <p
                class="flex items-center justify-center gap-1.5 text-sm text-muted-foreground"
            >
                <CalendarDays class="size-4" /> {{ camp?.year }}
            </p>
            <p
                v-if="camp?.description"
                class="mt-2 line-clamp-3 text-sm text-muted-foreground"
            >
                {{ camp.description }}
            </p>
            <p v-if="inviter" class="mt-3 text-sm">
                {{ t('invite.invitedByPrefix') }}
                <span class="font-medium">{{ inviter.name }}</span>
                {{ t('invite.invitedBySuffix') }}
            </p>
        </div>

        <!-- Already a member -->
        <div v-if="alreadyMember" class="flex flex-col gap-3">
            <p
                class="flex items-center justify-center gap-2 text-sm text-emerald-600 dark:text-emerald-400"
            >
                <Check class="size-4" /> {{ t('invite.alreadyMember') }}
            </p>
            <Button as-child>
                <Link :href="campShow(camp!.id).url">{{
                    t('invite.goToCamp')
                }}</Link>
            </Button>
        </div>

        <!-- Logged in -> join -->
        <div v-else-if="user" class="flex flex-col gap-3">
            <Button :disabled="joining" @click="join">
                <UserPlus /> {{ t('invite.join') }}
            </Button>
            <p class="text-center text-xs text-muted-foreground">
                {{ t('invite.joiningAs', { email: user.email }) }}
            </p>
        </div>

        <!-- Guest -> log in or register -->
        <div v-else class="flex flex-col gap-3">
            <Button as-child>
                <Link :href="login().url">
                    <LogIn /> {{ t('invite.loginAndJoin') }}
                </Link>
            </Button>
            <Button as-child variant="outline">
                <Link :href="register().url">
                    <UserPlus /> {{ t('invite.registerAndJoin') }}
                </Link>
            </Button>
            <p
                v-if="invitedEmail"
                class="text-center text-xs text-muted-foreground"
            >
                {{ t('invite.for', { email: invitedEmail }) }}
            </p>
        </div>
    </div>
</template>
