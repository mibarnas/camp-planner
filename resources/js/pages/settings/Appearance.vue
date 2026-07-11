<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Nastavenia vzhľadu',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Nastavenia vzhľadu" />

    <h1 class="sr-only">Nastavenia vzhľadu</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Vzhľad"
            description="Uprav vzhľad aplikácie pre svoj účet"
        />
        <AppearanceTabs />
    </div>
</template>
