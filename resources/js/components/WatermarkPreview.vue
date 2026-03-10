<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';

const props = withDefaults(defineProps<{
    type: string | null;
    imagePreviewUrl: string | null;
    imageNaturalWidth?: number;
    imageNaturalHeight?: number;
    text: string;
    position: string;
    opacity: number;
    scale: number;
    customX: number;
    customY: number;
    rotation: number;
}>(), {
    imageNaturalWidth: 0,
    imageNaturalHeight: 0,
});

const REFERENCE_WIDTH = 1080;

const aspectOptions = [
    { value: '16:9', label: '16:9', cssClass: 'aspect-video' },
    { value: '9:16', label: '9:16', cssClass: 'aspect-[9/16]' },
    { value: '4:3', label: '4:3', cssClass: 'aspect-[4/3]' },
    { value: '1:1', label: '1:1', cssClass: 'aspect-square' },
];

const aspectRatio = ref('16:9');

const aspectClass = computed(() => {
    return aspectOptions.find((o) => o.value === aspectRatio.value)?.cssClass ?? 'aspect-video';
});

const frameRef = ref<HTMLElement | null>(null);
const { height: frameHeight } = useElementSize(frameRef);

const positionOffsetPercent = `${(10 / REFERENCE_WIDTH) * 100}%`;

const predefinedPositionStyles: Record<string, Record<string, string>> = {
    'top-left': { top: positionOffsetPercent, left: positionOffsetPercent },
    'top-right': { top: positionOffsetPercent, right: positionOffsetPercent },
    'center': { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' },
    'bottom-left': { bottom: positionOffsetPercent, left: positionOffsetPercent },
    'bottom-right': { bottom: positionOffsetPercent, right: positionOffsetPercent },
};

const imageWidthPercent = computed(() => {
    if (!props.imageNaturalWidth) return 0;
    return (props.imageNaturalWidth / REFERENCE_WIDTH) * 100;
});

const textFontSize = computed(() => {
    if (!frameHeight.value) return 14;
    const baseFontSize = 48;
    const size = (baseFontSize / REFERENCE_WIDTH) * frameHeight.value;
    return Math.max(size, 4);
});

const watermarkStyle = computed(() => {
    const isImage = props.type === 'image';
    const base: Record<string, string> = {
        position: 'absolute',
        opacity: String(props.opacity),
        pointerEvents: 'none',
        transition: 'all 0.15s ease',
    };

    if (isImage && imageWidthPercent.value > 0) {
        base.width = `${imageWidthPercent.value}%`;
        base.height = 'auto';
    }

    if (props.position === 'custom') {
        return {
            ...base,
            left: `${props.customX}%`,
            top: `${props.customY}%`,
            transform: `translate(-50%, -50%) scale(${props.scale}) rotate(${props.rotation}deg)`,
        };
    }

    const posStyles = predefinedPositionStyles[props.position] ?? predefinedPositionStyles['bottom-right'];
    return { ...base, ...posStyles };
});

const textStyle = computed(() => {
    const scaleFactor = props.position === 'custom' ? props.scale : 1;
    const fontSize = textFontSize.value * scaleFactor;
    return {
        ...watermarkStyle.value,
        color: 'white',
        fontSize: `${fontSize}px`,
        fontWeight: '600',
        textShadow: '1px 1px 3px rgba(0,0,0,0.7)',
        whiteSpace: 'nowrap',
    };
});

const hasContent = computed(() => {
    if (props.type === 'image') return !!props.imagePreviewUrl;
    if (props.type === 'text') return !!props.text;
    return false;
});
</script>

<template>
    <Card>
        <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2 text-base">
                <Eye class="size-4" />
                Visualização
            </CardTitle>
            <CardDescription>
                Prévia de como a marca d'água ficará no vídeo.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="mb-3 flex items-center justify-between">
                <Label class="text-xs text-muted-foreground">Proporção</Label>
                <div class="flex gap-1">
                    <button
                        v-for="opt in aspectOptions"
                        :key="opt.value"
                        type="button"
                        class="rounded-md border px-2 py-1 text-xs font-medium transition-colors"
                        :class="aspectRatio === opt.value
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'border-border text-muted-foreground hover:border-muted-foreground/50'"
                        @click="aspectRatio = opt.value"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>
            <div
                ref="frameRef"
                :class="['relative w-full max-h-[70vh] overflow-hidden rounded-lg bg-neutral-800', aspectClass]"
            >
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center text-neutral-500">
                        <div class="text-4xl font-bold tracking-wider">{{ aspectRatio }}</div>
                        <div class="mt-1 text-xs uppercase tracking-widest">Preview do Vídeo</div>
                    </div>
                </div>

                <template v-if="hasContent">
                    <img
                        v-if="type === 'image' && imagePreviewUrl"
                        :src="imagePreviewUrl"
                        alt="Watermark preview"
                        :style="watermarkStyle"
                    />
                    <span
                        v-else-if="type === 'text' && text"
                        :style="textStyle"
                    >
                        {{ text }}
                    </span>
                </template>

                <div v-else class="absolute inset-0 flex items-center justify-center">
                    <p class="rounded-md bg-neutral-700/60 px-3 py-1.5 text-xs text-neutral-400">
                        {{ type === 'image' ? 'Selecione uma imagem' : type === 'text' ? 'Digite o texto' : 'Configure a marca d\'água' }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
