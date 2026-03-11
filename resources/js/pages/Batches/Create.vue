<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import WatermarkConfig from '@/components/WatermarkConfig.vue';
import WatermarkPreview from '@/components/WatermarkPreview.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as batchesIndex, store as batchesStore } from '@/routes/batches';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Lotes', href: batchesIndex().url },
    { title: 'Novo Lote', href: '#' },
];

const form = useForm({
    name: '',
    speed_factor: 1.0,
    watermark_type: null as string | null,
    watermark_image: null as File | null,
    watermark_text: '',
    watermark_position: 'bottom-right',
    watermark_opacity: 0.7,
    watermark_scale: 1.0,
    watermark_custom_x: 50,
    watermark_custom_y: 50,
    watermark_rotation: 0,
    target_width: null as number | null,
    target_height: null as number | null,
});

const watermarkConfigRef = ref<InstanceType<typeof WatermarkConfig> | null>(null);

const imagePreviewUrl = ref<string | null>(null);
const imageNaturalWidth = ref(0);
const imageNaturalHeight = ref(0);

watch(() => form.watermark_image, (file) => {
    if (!file) {
        imagePreviewUrl.value = null;
        imageNaturalWidth.value = 0;
        imageNaturalHeight.value = 0;
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        const dataUrl = e.target?.result as string;
        imagePreviewUrl.value = dataUrl;
        const img = new Image();
        img.onload = () => {
            imageNaturalWidth.value = img.naturalWidth;
            imageNaturalHeight.value = img.naturalHeight;
        };
        img.src = dataUrl;
    };
    reader.readAsDataURL(file);
});

const watermarkEnabled = computed(() => form.watermark_type !== null);

const speedLabel = computed(() => {
    if (form.speed_factor === 1.0) return 'Normal';
    return `${form.speed_factor}x (slow motion)`;
});

function submit() {
    form.post(batchesStore().url, {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Novo Lote">
        <meta name="description" content="Configure as opções de processamento para um novo lote de vídeos: nome, velocidade e marca d'água." />
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center gap-4">
                <Link :href="batchesIndex().url">
                    <Button variant="outline" size="icon-sm" aria-label="Voltar">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Novo Lote</h1>
                    <p class="text-muted-foreground text-sm">
                        Configure as opções de processamento para o novo lote de vídeos.
                    </p>
                </div>
            </div>

            <h2 class="sr-only">Configuração do lote</h2>
            <form
                @submit.prevent="submit"
                :class="watermarkEnabled
                    ? 'mx-auto grid w-full max-w-5xl gap-6 lg:grid-cols-[1fr_380px]'
                    : 'mx-auto w-full max-w-2xl space-y-6'"
            >
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Informações Básicas</CardTitle>
                            <CardDescription>Nome e identificação do lote.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-2">
                                <Label for="name">Nome do Lote</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Ex: Casamento Silva - Junho 2025"
                                    required
                                />
                                <InputError :message="form.errors.name" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Velocidade</CardTitle>
                            <CardDescription>
                                Ajuste a velocidade de reprodução dos vídeos para criar efeito slow motion.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-3">
                                <div class="flex items-center justify-between">
                                    <Label for="speed_factor">Fator de Velocidade</Label>
                                    <span class="bg-muted text-muted-foreground rounded-md px-2.5 py-0.5 text-sm font-medium">
                                        {{ speedLabel }}
                                    </span>
                                </div>
                                <input
                                    id="speed_factor"
                                    v-model.number="form.speed_factor"
                                    type="range"
                                    min="0.25"
                                    max="1.0"
                                    step="0.05"
                                    class="accent-primary h-2 w-full cursor-pointer appearance-none rounded-lg bg-neutral-200 dark:bg-neutral-700"
                                />
                                <div class="text-muted-foreground flex justify-between text-xs">
                                    <span>0.25x (mais lento)</span>
                                    <span>1.0x (normal)</span>
                                </div>
                                <InputError :message="form.errors.speed_factor" />
                            </div>
                        </CardContent>
                    </Card>

                    <WatermarkConfig
                        ref="watermarkConfigRef"
                        v-model:type="form.watermark_type"
                        v-model:image="form.watermark_image"
                        v-model:text="form.watermark_text"
                        v-model:position="form.watermark_position"
                        v-model:opacity="form.watermark_opacity"
                        v-model:scale="form.watermark_scale"
                        v-model:customX="form.watermark_custom_x"
                        v-model:customY="form.watermark_custom_y"
                        v-model:rotation="form.watermark_rotation"
                        :errors="form.errors"
                    />

                    <div class="flex items-center justify-end gap-3">
                        <Link :href="batchesIndex().url">
                            <Button type="button" variant="outline">Cancelar</Button>
                        </Link>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Criando...' : 'Criar Lote' }}
                        </Button>
                    </div>
                </div>

                <div v-if="watermarkEnabled" class="lg:sticky lg:top-0 lg:flex lg:h-screen lg:items-center">
                    <WatermarkPreview
                        :type="form.watermark_type"
                        :image-preview-url="imagePreviewUrl"
                        :image-natural-width="imageNaturalWidth"
                        :image-natural-height="imageNaturalHeight"
                        :text="form.watermark_text"
                        :position="form.watermark_position"
                        :opacity="form.watermark_opacity"
                        :scale="form.watermark_scale"
                        :custom-x="form.watermark_custom_x"
                        :custom-y="form.watermark_custom_y"
                        :rotation="form.watermark_rotation"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
