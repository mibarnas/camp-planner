<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { CAMP_ICON_NAMES, campIcon } from '@/lib/campIcons';
import { colorStyle, COLOR_NAMES } from '@/lib/campColors';

const icon = defineModel<string>('icon', { default: 'tent' });
const color = defineModel<string>('color', { default: 'emerald' });
const location = defineModel<string>('location', { default: '' });
</script>

<template>
    <div class="grid gap-4">
        <div class="flex items-center gap-3">
            <!-- live preview -->
            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl" :class="colorStyle(color).chip">
                <component :is="campIcon(icon)" class="size-6" />
            </div>
            <div class="grid gap-1">
                <Label>Farba</Label>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="c in COLOR_NAMES"
                        :key="c"
                        type="button"
                        class="size-6 rounded-full border-2 transition"
                        :class="[colorStyle(c).dot, color === c ? 'border-foreground ring-2 ring-ring/40' : 'border-transparent']"
                        :title="c"
                        @click="color = c"
                    />
                </div>
            </div>
        </div>

        <div class="grid gap-1.5">
            <Label>Ikona</Label>
            <div class="grid grid-cols-8 gap-1.5 sm:grid-cols-11">
                <button
                    v-for="name in CAMP_ICON_NAMES"
                    :key="name"
                    type="button"
                    class="flex aspect-square items-center justify-center rounded-lg border transition-colors"
                    :class="icon === name ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent'"
                    :title="name"
                    @click="icon = name"
                >
                    <component :is="campIcon(name)" class="size-4.5" />
                </button>
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="camp-location">Miesto (voliteľné)</Label>
            <Input id="camp-location" v-model="location" placeholder="Napr. Farské centrum, Svätý Jur" />
        </div>
    </div>
</template>
