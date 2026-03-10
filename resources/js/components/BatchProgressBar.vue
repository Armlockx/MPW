<script setup lang="ts">
import { CheckCircle2, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

type BatchStatus = 'pending' | 'processing' | 'completed' | 'failed';

const props = withDefaults(
    defineProps<{
        total: number;
        completed: number;
        failed?: number;
        status: BatchStatus;
        size?: 'sm' | 'md';
    }>(),
    {
        failed: 0,
        size: 'md',
    },
);

const progressPercent = computed(() => {
    if (props.total === 0) return 0;
    return Math.round(((props.completed + props.failed) / props.total) * 100);
});
</script>

<template>
    <div class="space-y-2" :class="size === 'sm' ? 'text-xs' : 'text-sm'">
        <div v-if="size === 'sm'" class="flex items-center justify-between">
            <span class="text-muted-foreground">Progresso</span>
            <span class="font-medium">{{ progressPercent }}%</span>
        </div>

        <div
            class="bg-secondary w-full overflow-hidden rounded-full"
            :class="size === 'sm' ? 'h-2' : 'h-3'"
        >
            <div
                class="h-full rounded-full transition-all duration-500"
                :class="{
                    'bg-emerald-500': status === 'completed',
                    'bg-primary': status === 'processing',
                    'bg-destructive': status === 'failed' && failed > 0 && completed === 0,
                    'bg-amber-500': status === 'failed' && completed > 0,
                    'bg-muted-foreground/40': status === 'pending',
                }"
                :style="{ width: `${progressPercent}%` }"
            />
        </div>

        <div v-if="size === 'sm'" class="text-muted-foreground flex justify-between">
            <span>{{ completed }} concluído{{ completed !== 1 ? 's' : '' }}</span>
            <span v-if="failed > 0" class="text-destructive">
                {{ failed }} falha{{ failed !== 1 ? 's' : '' }}
            </span>
        </div>

        <div v-else class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5 text-emerald-500">
                    <CheckCircle2 class="size-3.5" />
                    {{ completed }} concluído{{ completed !== 1 ? 's' : '' }}
                </span>
                <span
                    v-if="failed > 0"
                    class="text-destructive flex items-center gap-1.5"
                >
                    <XCircle class="size-3.5" />
                    {{ failed }} falha{{ failed !== 1 ? 's' : '' }}
                </span>
            </div>
            <span class="font-medium">{{ progressPercent }}%</span>
        </div>
    </div>
</template>
