<script setup lang="ts">
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { COLOR_NAMES, colorStyle } from '@/lib/campColors';

defineProps<{ label?: string }>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="grid gap-2">
        <Label v-if="label">{{ label }}</Label>
        <Select
            :model-value="model"
            @update:model-value="model = $event as string"
        >
            <SelectTrigger><SelectValue /></SelectTrigger>
            <SelectContent>
                <SelectItem v-for="c in COLOR_NAMES" :key="c" :value="c">
                    <span class="flex items-center gap-2">
                        <span
                            class="size-3 rounded-full"
                            :class="colorStyle(c).dot"
                        />
                        {{ c }}
                    </span>
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
