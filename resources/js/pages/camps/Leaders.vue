<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import LeadersPanel from '@/components/camp/LeadersPanel.vue';
import MembersPanel from '@/components/camp/MembersPanel.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as campsIndex, show } from '@/routes/camps';
import type {
    CampInvitation,
    CampLeader,
    CampMember,
    ShareLink,
} from '@/types/camp';

const props = defineProps<{
    camp: { id: number; name: string; is_owner: boolean };
    leaders: CampLeader[];
    members: CampMember[];
    invitations: CampInvitation[];
    shareLink: ShareLink | null;
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tábory', href: campsIndex().url },
            { title: props.camp.name, href: show(props.camp.id).url },
            { title: 'Vedúci', href: '#' },
        ],
    });
});
</script>

<template>
    <Head :title="`Vedúci — ${camp.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Vedúci tábora"
            description="Kto vedie tábor — s účtom aj bez neho."
        />

        <Card>
            <CardHeader>
                <CardTitle>Vedúci</CardTitle>
            </CardHeader>
            <CardContent>
                <LeadersPanel :camp-id="camp.id" :leaders="leaders" />
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Účty a pozvánky</CardTitle>
            </CardHeader>
            <CardContent>
                <MembersPanel
                    :camp-id="camp.id"
                    :members="members"
                    :invitations="invitations"
                    :share-link="shareLink"
                    :is-owner="camp.is_owner"
                />
            </CardContent>
        </Card>
    </div>
</template>
