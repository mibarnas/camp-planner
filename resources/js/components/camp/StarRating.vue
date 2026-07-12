<script setup lang="ts">
import { Star } from '@lucide/vue';
import { ref } from 'vue';

const model = defineModel<number>({ default: 0 });
const props = withDefaults(defineProps<{ size?: string; gap?: string }>(), {
    size: 'size-6',
    gap: 'gap-0.5',
});

const hover = ref(0);

function set(n: number) {
    // Clicking the current value clears it.
    model.value = model.value === n ? 0 : n;
}
</script>

<template>
    <div class="flex items-center" :class="gap" @mouseleave="hover = 0">
        <button
            v-for="n in 5"
            :key="n"
            type="button"
            class="rounded p-0.5 text-muted-foreground/40 transition-colors hover:text-amber-400"
            :class="(hover || model) >= n ? 'text-amber-400' : ''"
            @click="set(n)"
            @mouseenter="hover = n"
            :aria-label="`${n} z 5`"
        >
            <Star :class="[props.size, (hover || model) >= n ? 'fill-amber-400' : '']" />
        </button>
    </div>
</template>
