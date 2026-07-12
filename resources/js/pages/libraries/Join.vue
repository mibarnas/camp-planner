<script setup lang="ts">
import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Check, Library, ListChecks, LogIn, UserPlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';
import { index as activitiesIndex } from '@/routes/activities';
import { accept } from '@/routes/libraries/join';

const props = defineProps<{
    valid: boolean;
    token?: string;
    library?: { id: number; name: string; activities_count: number };
    alreadyMember?: boolean;
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

setLayoutProps({
    title: 'Databáza aktivít',
    description: props.valid ? 'Pripoj sa k zdieľanej databáze aktivít' : 'Databáza aktivít',
});

const joining = ref(false);
function join() {
    if (!props.token) {
return;
}

    router.post(accept(props.token).url, {}, {
        onStart: () => (joining.value = true),
        onFinish: () => (joining.value = false),
    });
}
</script>

<template>
    <Head title="Databáza aktivít" />

    <div v-if="!valid" class="flex flex-col items-center gap-4 text-center">
        <p class="text-sm text-muted-foreground">Tento odkaz je neplatný alebo bol zrušený.</p>
        <Button as-child variant="outline">
            <Link :href="login().url">Prihlásiť sa</Link>
        </Button>
    </div>

    <div v-else class="flex flex-col gap-6">
        <div class="rounded-xl border bg-card p-5 text-center">
            <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <Library class="size-6" />
            </div>
            <h2 class="mt-3 text-lg font-semibold">{{ library?.name }}</h2>
            <p class="flex items-center justify-center gap-1.5 text-sm text-muted-foreground">
                <ListChecks class="size-4" /> {{ library?.activities_count }} aktivít
            </p>
        </div>

        <div v-if="alreadyMember" class="flex flex-col gap-3">
            <p class="flex items-center justify-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                <Check class="size-4" /> Už máš prístup k tejto databáze.
            </p>
            <Button as-child>
                <Link :href="activitiesIndex({ query: { library: library!.id } }).url">Otvoriť databázu</Link>
            </Button>
        </div>

        <div v-else-if="user" class="flex flex-col gap-3">
            <Button :disabled="joining" @click="join">
                <UserPlus /> Pripojiť sa k databáze
            </Button>
            <p class="text-center text-xs text-muted-foreground">Pripájaš sa ako {{ user.email }}</p>
        </div>

        <div v-else class="flex flex-col gap-3">
            <Button as-child>
                <Link :href="login().url">
                    <LogIn /> Prihlásiť sa a pripojiť
                </Link>
            </Button>
            <Button as-child variant="outline">
                <Link :href="register().url">
                    <UserPlus /> Vytvoriť účet a pripojiť
                </Link>
            </Button>
        </div>
    </div>
</template>
