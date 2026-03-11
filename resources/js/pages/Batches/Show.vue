<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Download,
    Film,
    Loader2,
    Play,
    PackageOpen,
    Settings2,
    Gauge,
    ImagePlus,
    Type,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import BatchProgressBar from '@/components/BatchProgressBar.vue';
import VideoCard from '@/components/VideoCard.vue';
import VideoUploader from '@/components/VideoUploader.vue';
import type { Video } from '@/components/VideoCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as batchesIndex, process as batchesProcess, download as batchesDownload } from '@/routes/batches';
import { destroy as videosDestroy } from '@/routes/videos';
import type { BreadcrumbItem } from '@/types';

interface Batch {
    id: number;
    name: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    speed_factor: number;
    watermark_type: string | null;
    watermark_text: string | null;
    watermark_position: string | null;
    watermark_opacity: number | null;
    watermark_scale: number | null;
    watermark_custom_x: number | null;
    watermark_custom_y: number | null;
    watermark_rotation: number | null;
    target_width: number | null;
    target_height: number | null;
    videos_count: number;
    videos_completed_count: number;
    videos_failed_count: number;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    batch: Batch;
    videos: Video[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Lotes', href: batchesIndex().url },
    { title: props.batch.name, href: '#' },
];

const statusConfig: Record<Batch['status'], { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline'; class: string }> = {
    pending: { label: 'Pendente', variant: 'secondary', class: '' },
    processing: { label: 'Processando', variant: 'default', class: 'animate-pulse' },
    completed: { label: 'Concluído', variant: 'default', class: 'bg-emerald-600 hover:bg-emerald-600/90' },
    failed: { label: 'Falhou', variant: 'destructive', class: '' },
};

const positionLabels: Record<string, string> = {
    'top-left': 'Superior Esquerdo',
    'top-right': 'Superior Direito',
    'center': 'Centro',
    'bottom-left': 'Inferior Esquerdo',
    'bottom-right': 'Inferior Direito',
    'custom': 'Personalizado',
};

const isProcessing = ref(false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

const hasCompletedVideos = computed(() => props.batch.videos_completed_count > 0);
const hasPendingOrFailedVideos = computed(() =>
    props.videos.some((v) => v.status === 'pending' || v.status === 'failed'),
);
const canUpload = computed(() => props.batch.status !== 'processing');
const isPolling = computed(() => props.batch.status === 'processing');

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function startProcessing() {
    isProcessing.value = true;
    router.post(batchesProcess(props.batch.id).url, {}, {
        preserveScroll: true,
        onFinish: () => {
            isProcessing.value = false;
        },
    });
}

function deleteVideo(video: Video) {
    if (!confirm(`Remover o vídeo "${video.original_filename}"?`)) return;
    router.delete(videosDestroy(video.id).url, { preserveScroll: true });
}

function startPolling() {
    if (pollInterval) return;
    pollInterval = setInterval(() => {
        router.reload({ only: ['batch', 'videos'] });
    }, 5000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

watch(isPolling, (shouldPoll) => {
    if (shouldPoll) {
        startPolling();
    } else {
        stopPolling();
    }
}, { immediate: true });

onBeforeUnmount(() => {
    stopPolling();
});
</script>

<template>
    <Head :title="batch.name">
        <meta name="description" :content="`Detalhes do lote ${batch.name}. Acompanhe o progresso e faça download dos vídeos processados.`" />
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="batchesIndex().url">
                        <Button variant="outline" size="icon-sm" aria-label="Voltar">
                            <ArrowLeft class="size-4" />
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight">{{ batch.name }}</h1>
                            <Badge
                                :variant="statusConfig[batch.status].variant"
                                :class="statusConfig[batch.status].class"
                            >
                                <Loader2 v-if="batch.status === 'processing'" class="size-3 animate-spin" />
                                {{ statusConfig[batch.status].label }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            Criado em {{ formatDate(batch.created_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="hasPendingOrFailedVideos && batch.status !== 'processing'"
                        @click="startProcessing"
                        :disabled="isProcessing"
                    >
                        <Loader2 v-if="isProcessing" class="size-4 animate-spin" />
                        <Play v-else class="size-4" />
                        {{ isProcessing ? 'Iniciando...' : 'Processar Todos' }}
                    </Button>

                    <a
                        v-if="hasCompletedVideos"
                        :href="batchesDownload(batch.id).url"
                        class="inline-flex"
                    >
                        <Button variant="outline">
                            <Download class="size-4" />
                            Download ZIP
                        </Button>
                    </a>
                </div>
            </div>

            <h2 class="sr-only">Conteúdo do lote</h2>
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <Card v-if="batch.videos_count > 0">
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">Progresso</CardTitle>
                                <span class="text-muted-foreground text-sm">
                                    {{ batch.videos_completed_count + batch.videos_failed_count }} / {{ batch.videos_count }}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <BatchProgressBar
                                :total="batch.videos_count"
                                :completed="batch.videos_completed_count"
                                :failed="batch.videos_failed_count"
                                :status="batch.status"
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base">Vídeos</CardTitle>
                                    <CardDescription>
                                        {{ batch.videos_count }} {{ batch.videos_count === 1 ? 'vídeo' : 'vídeos' }} no lote
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <VideoUploader
                                :batch-id="batch.id"
                                :disabled="!canUpload"
                            />

                            <div v-if="videos.length === 0 && !canUpload" class="flex flex-col items-center gap-3 py-8">
                                <PackageOpen class="text-muted-foreground size-10" />
                                <p class="text-muted-foreground text-sm">Nenhum vídeo neste lote.</p>
                            </div>

                            <div v-if="videos.length > 0" class="divide-y rounded-lg border">
                                <VideoCard
                                    v-for="video in videos"
                                    :key="video.id"
                                    :video="video"
                                    :can-delete="batch.status !== 'processing'"
                                    @delete="deleteVideo"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Settings2 class="size-4" />
                                Configurações
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-muted flex size-8 items-center justify-center rounded-md">
                                    <Gauge class="text-muted-foreground size-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground">Velocidade</p>
                                    <p class="text-sm">
                                        {{ batch.speed_factor === 1.0 ? 'Normal' : `${batch.speed_factor}x (slow motion)` }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="batch.watermark_type" class="flex items-center gap-3">
                                <div class="bg-muted flex size-8 items-center justify-center rounded-md">
                                    <ImagePlus v-if="batch.watermark_type === 'image'" class="text-muted-foreground size-4" />
                                    <Type v-else class="text-muted-foreground size-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground">Marca d'Água</p>
                                    <p class="text-sm">
                                        {{ batch.watermark_type === 'image' ? 'Imagem' : batch.watermark_text }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="batch.watermark_position" class="flex items-center gap-3">
                                <div class="bg-muted flex size-8 items-center justify-center rounded-md">
                                    <Film class="text-muted-foreground size-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground">Posição</p>
                                    <p class="text-sm">{{ positionLabels[batch.watermark_position] ?? batch.watermark_position }}</p>
                                </div>
                            </div>

                            <div v-if="batch.watermark_position === 'custom'" class="border-border ml-8 space-y-2 border-l pl-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-muted-foreground">Escala</p>
                                    <p class="text-xs font-medium">{{ batch.watermark_scale?.toFixed(1) }}x</p>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-muted-foreground">Posição X</p>
                                    <p class="text-xs font-medium">{{ Math.round(batch.watermark_custom_x ?? 50) }}%</p>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-muted-foreground">Posição Y</p>
                                    <p class="text-xs font-medium">{{ Math.round(batch.watermark_custom_y ?? 50) }}%</p>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-muted-foreground">Rotação</p>
                                    <p class="text-xs font-medium">{{ batch.watermark_rotation ?? 0 }}°</p>
                                </div>
                            </div>

                            <div v-if="batch.watermark_opacity !== null && batch.watermark_type" class="flex items-center gap-3">
                                <div class="bg-muted flex size-8 items-center justify-center rounded-md">
                                    <span class="text-muted-foreground text-xs font-bold">%</span>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground">Opacidade</p>
                                    <p class="text-sm">{{ Math.round(batch.watermark_opacity * 100) }}%</p>
                                </div>
                            </div>

                            <div v-if="!batch.watermark_type" class="text-muted-foreground text-sm">
                                Sem marca d'água configurada.
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Film class="size-4" />
                                Resumo
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <dt class="text-muted-foreground text-sm">Total</dt>
                                    <dd class="text-sm font-medium">{{ batch.videos_count }} vídeo{{ batch.videos_count !== 1 ? 's' : '' }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-muted-foreground text-sm">Concluídos</dt>
                                    <dd class="text-sm font-medium text-emerald-500">{{ batch.videos_completed_count }}</dd>
                                </div>
                                <div v-if="batch.videos_failed_count > 0" class="flex items-center justify-between">
                                    <dt class="text-muted-foreground text-sm">Falhas</dt>
                                    <dd class="text-destructive text-sm font-medium">{{ batch.videos_failed_count }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-muted-foreground text-sm">Pendentes</dt>
                                    <dd class="text-sm font-medium">
                                        {{ batch.videos_count - batch.videos_completed_count - batch.videos_failed_count }}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                        <CardFooter v-if="hasCompletedVideos" class="flex-col gap-2">
                            <a :href="batchesDownload(batch.id).url" class="w-full">
                                <Button variant="outline" class="w-full">
                                    <Download class="size-4" />
                                    Download ZIP
                                </Button>
                            </a>
                        </CardFooter>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
