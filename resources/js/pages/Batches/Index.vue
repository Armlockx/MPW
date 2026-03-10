<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Film, Plus, FolderOpen, Eye, Trash2, Loader2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import BatchProgressBar from '@/components/BatchProgressBar.vue';
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
import { index as batchesIndex, create as batchesCreate, show as batchesShow, destroy as batchesDestroy } from '@/routes/batches';
import type { BreadcrumbItem } from '@/types';

interface Batch {
    id: number;
    name: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    speed_factor: number;
    watermark_type: string | null;
    videos_count: number;
    videos_completed_count: number;
    videos_failed_count: number;
    created_at: string;
    updated_at: string;
}

defineProps<{
    batches: Batch[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Lotes',
        href: batchesIndex().url,
    },
];

const statusConfig: Record<Batch['status'], { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline'; class: string }> = {
    pending: { label: 'Pendente', variant: 'secondary', class: '' },
    processing: { label: 'Processando', variant: 'default', class: 'animate-pulse' },
    completed: { label: 'Concluído', variant: 'default', class: 'bg-emerald-600 hover:bg-emerald-600/90' },
    failed: { label: 'Falhou', variant: 'destructive', class: '' },
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function deleteBatch(batch: Batch) {
    if (!confirm(`Tem certeza que deseja excluir o lote "${batch.name}"?`)) return;
    router.delete(batchesDestroy(batch.id).url);
}
</script>

<template>
    <Head title="Lotes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Lotes de Vídeos</h1>
                    <p class="text-muted-foreground text-sm">
                        Gerencie seus lotes de processamento de vídeos.
                    </p>
                </div>
                <Link :href="batchesCreate().url">
                    <Button>
                        <Plus class="size-4" />
                        Novo Lote
                    </Button>
                </Link>
            </div>

            <div v-if="batches.length === 0" class="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-12">
                <div class="bg-muted flex size-16 items-center justify-center rounded-full">
                    <FolderOpen class="text-muted-foreground size-8" />
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-semibold">Nenhum lote criado</h3>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Crie seu primeiro lote para começar a processar vídeos.
                    </p>
                </div>
                <Link :href="batchesCreate().url">
                    <Button>
                        <Plus class="size-4" />
                        Criar Primeiro Lote
                    </Button>
                </Link>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="batch in batches"
                    :key="batch.id"
                    class="transition-shadow hover:shadow-md"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <CardTitle class="text-base">{{ batch.name }}</CardTitle>
                            <Badge
                                :variant="statusConfig[batch.status].variant"
                                :class="statusConfig[batch.status].class"
                            >
                                <Loader2 v-if="batch.status === 'processing'" class="size-3 animate-spin" />
                                {{ statusConfig[batch.status].label }}
                            </Badge>
                        </div>
                        <CardDescription>
                            Criado em {{ formatDate(batch.created_at) }}
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-2 text-sm">
                            <Film class="text-muted-foreground size-4" />
                            <span>{{ batch.videos_count }} {{ batch.videos_count === 1 ? 'vídeo' : 'vídeos' }}</span>
                        </div>

                        <BatchProgressBar
                            v-if="batch.videos_count > 0"
                            :total="batch.videos_count"
                            :completed="batch.videos_completed_count"
                            :failed="batch.videos_failed_count"
                            :status="batch.status"
                            size="sm"
                        />

                        <div v-if="batch.speed_factor !== 1.0" class="text-muted-foreground text-xs">
                            Velocidade: {{ batch.speed_factor }}x
                        </div>
                        <div v-if="batch.watermark_type" class="text-muted-foreground text-xs">
                            Marca d'água: {{ batch.watermark_type === 'image' ? 'Imagem' : 'Texto' }}
                        </div>
                    </CardContent>

                    <CardFooter class="gap-2">
                        <Link :href="batchesShow(batch.id).url" class="flex-1">
                            <Button variant="outline" size="sm" class="w-full">
                                <Eye class="size-4" />
                                Ver Detalhes
                            </Button>
                        </Link>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive hover:text-white"
                            @click="deleteBatch(batch)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
