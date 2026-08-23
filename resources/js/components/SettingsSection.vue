<script setup lang="ts">
import Heading from '@/components/Heading.vue';

defineProps<{ title: string; description?: string }>();
</script>

<template>
    <section
        class="grid gap-4 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] lg:gap-10"
    >
        <Heading variant="small" :title="title" :description="description" />
        <div class="max-w-2xl">
            <slot />
        </div>
    </section>
</template>
