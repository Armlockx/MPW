<script setup lang="ts">
import { ImagePlus, Type, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const type = defineModel<string | null>('type', { required: true });
const image = defineModel<File | null>('image', { default: null });
const text = defineModel<string>('text', { default: '' });
const position = defineModel<string>('position', { default: 'bottom-right' });
const opacity = defineModel<number>('opacity', { default: 0.7 });
const scale = defineModel<number>('scale', { default: 1.0 });
const customX = defineModel<number>('customX', { default: 50 });
const customY = defineModel<number>('customY', { default: 50 });
const rotation = defineModel<number>('rotation', { default: 0 });

defineProps<{
    errors?: Record<string, string | undefined>;
}>();

const enabled = computed({
    get: () => type.value !== null,
    set: (val: boolean) => {
        if (val) {
            type.value = 'image';
        } else {
            type.value = null;
            image.value = null;
            text.value = '';
            position.value = 'bottom-right';
            opacity.value = 0.7;
            scale.value = 1.0;
            customX.value = 50;
            customY.value = 50;
            rotation.value = 0;
            imagePreview.value = null;
        }
    },
});

const imagePreview = ref<string | null>(null);
const imageInput = ref<HTMLInputElement | null>(null);

defineExpose({ imagePreview });

const positionOptions = [
    { value: 'top-left', label: 'Superior Esquerdo' },
    { value: 'top-right', label: 'Superior Direito' },
    { value: 'center', label: 'Centro' },
    { value: 'bottom-left', label: 'Inferior Esquerdo' },
    { value: 'bottom-right', label: 'Inferior Direito' },
    { value: 'custom', label: 'Personalizado' },
];

watch(image, (file) => {
    if (!file) {
        imagePreview.value = null;
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
});

function onImageChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        image.value = file;
    }
}

function removeImage() {
    image.value = null;
    if (imageInput.value) {
        imageInput.value.value = '';
    }
}
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle>Marca d'Água</CardTitle>
                    <CardDescription>
                        Adicione uma marca d'água aos vídeos processados.
                    </CardDescription>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="watermark_enabled"
                        v-model="enabled"
                    />
                    <Label for="watermark_enabled" class="cursor-pointer">
                        Ativar
                    </Label>
                </div>
            </div>
        </CardHeader>

        <CardContent v-if="enabled" class="space-y-6">
            <div class="grid gap-3">
                <Label>Tipo de Marca d'Água</Label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors"
                        :class="type === 'image'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border hover:border-muted-foreground/50'"
                        @click="type = 'image'"
                    >
                        <ImagePlus class="size-6" />
                        <span class="text-sm font-medium">Imagem</span>
                    </button>
                    <button
                        type="button"
                        class="flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors"
                        :class="type === 'text'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border hover:border-muted-foreground/50'"
                        @click="type = 'text'"
                    >
                        <Type class="size-6" />
                        <span class="text-sm font-medium">Texto</span>
                    </button>
                </div>
                <InputError :message="errors?.watermark_type" />
            </div>

            <div v-if="type === 'image'" class="grid gap-3">
                <Label for="watermark_image">Imagem da Marca d'Água</Label>
                <div
                    v-if="!imagePreview"
                    class="border-border hover:border-muted-foreground/50 flex cursor-pointer flex-col items-center gap-2 rounded-lg border-2 border-dashed p-8 transition-colors"
                    @click="imageInput?.click()"
                >
                    <ImagePlus class="text-muted-foreground size-8" />
                    <span class="text-muted-foreground text-sm">
                        Clique para selecionar uma imagem PNG
                    </span>
                    <span class="text-muted-foreground/60 text-xs">
                        Máximo 5MB
                    </span>
                </div>
                <div
                    v-else
                    class="border-border relative flex items-center gap-4 rounded-lg border p-4"
                >
                    <img
                        :src="imagePreview"
                        alt="Preview da marca d'água"
                        class="h-16 w-auto rounded border object-contain"
                    />
                    <div class="flex-1 truncate text-sm">
                        {{ image?.name }}
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon-sm"
                        aria-label="Remover imagem"
                        @click="removeImage"
                    >
                        <X class="size-4" />
                    </Button>
                </div>
                <input
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="onImageChange"
                />
                <InputError :message="errors?.watermark_image" />
            </div>

            <div v-if="type === 'text'" class="grid gap-3">
                <Label for="watermark_text">Texto da Marca d'Água</Label>
                <Input
                    id="watermark_text"
                    v-model="text"
                    placeholder="Ex: © Seu Nome - Fotografia"
                />
                <InputError :message="errors?.watermark_text" />
            </div>

            <div class="grid gap-3">
                <Label>Posição</Label>
                <Select v-model="position">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Selecione a posição" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in positionOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors?.watermark_position" />
            </div>

            <div v-if="position === 'custom'" class="space-y-5 rounded-lg border p-4">
                <div class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <Label for="watermark_scale">Escala</Label>
                        <span class="bg-muted text-muted-foreground rounded-md px-2.5 py-0.5 text-sm font-medium">
                            {{ scale.toFixed(1) }}x
                        </span>
                    </div>
                    <input
                        id="watermark_scale"
                        v-model.number="scale"
                        type="range"
                        min="0.1"
                        max="2"
                        step="0.1"
                        class="accent-primary h-2 w-full cursor-pointer appearance-none rounded-lg bg-neutral-200 dark:bg-neutral-700"
                    />
                    <div class="text-muted-foreground flex justify-between text-xs">
                        <span>0.1x (pequeno)</span>
                        <span>2.0x (grande)</span>
                    </div>
                    <InputError :message="errors?.watermark_scale" />
                </div>

                <div class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <Label for="watermark_custom_x">Posição Horizontal</Label>
                        <span class="bg-muted text-muted-foreground rounded-md px-2.5 py-0.5 text-sm font-medium">
                            {{ Math.round(customX) }}%
                        </span>
                    </div>
                    <input
                        id="watermark_custom_x"
                        v-model.number="customX"
                        type="range"
                        min="0"
                        max="100"
                        step="1"
                        class="accent-primary h-2 w-full cursor-pointer appearance-none rounded-lg bg-neutral-200 dark:bg-neutral-700"
                    />
                    <div class="text-muted-foreground flex justify-between text-xs">
                        <span>Esquerda</span>
                        <span>Direita</span>
                    </div>
                    <InputError :message="errors?.watermark_custom_x" />
                </div>

                <div class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <Label for="watermark_custom_y">Posição Vertical</Label>
                        <span class="bg-muted text-muted-foreground rounded-md px-2.5 py-0.5 text-sm font-medium">
                            {{ Math.round(customY) }}%
                        </span>
                    </div>
                    <input
                        id="watermark_custom_y"
                        v-model.number="customY"
                        type="range"
                        min="0"
                        max="100"
                        step="1"
                        class="accent-primary h-2 w-full cursor-pointer appearance-none rounded-lg bg-neutral-200 dark:bg-neutral-700"
                    />
                    <div class="text-muted-foreground flex justify-between text-xs">
                        <span>Topo</span>
                        <span>Base</span>
                    </div>
                    <InputError :message="errors?.watermark_custom_y" />
                </div>

                <div class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <Label for="watermark_rotation">Rotação</Label>
                        <span class="bg-muted text-muted-foreground rounded-md px-2.5 py-0.5 text-sm font-medium">
                            {{ rotation }}°
                        </span>
                    </div>
                    <input
                        id="watermark_rotation"
                        v-model.number="rotation"
                        type="range"
                        min="-180"
                        max="180"
                        step="1"
                        class="accent-primary h-2 w-full cursor-pointer appearance-none rounded-lg bg-neutral-200 dark:bg-neutral-700"
                    />
                    <div class="text-muted-foreground flex justify-between text-xs">
                        <span>-180°</span>
                        <span>0°</span>
                        <span>180°</span>
                    </div>
                    <InputError :message="errors?.watermark_rotation" />
                </div>
            </div>

            <div class="grid gap-3">
                <div class="flex items-center justify-between">
                    <Label for="watermark_opacity">Opacidade</Label>
                    <span class="bg-muted text-muted-foreground rounded-md px-2.5 py-0.5 text-sm font-medium">
                        {{ Math.round(opacity * 100) }}%
                    </span>
                </div>
                <input
                    id="watermark_opacity"
                    v-model.number="opacity"
                    type="range"
                    min="0.1"
                    max="1"
                    step="0.05"
                    class="accent-primary h-2 w-full cursor-pointer appearance-none rounded-lg bg-neutral-200 dark:bg-neutral-700"
                />
                <div class="text-muted-foreground flex justify-between text-xs">
                    <span>10% (sutil)</span>
                    <span>100% (opaco)</span>
                </div>
                <InputError :message="errors?.watermark_opacity" />
            </div>
        </CardContent>
    </Card>
</template>
