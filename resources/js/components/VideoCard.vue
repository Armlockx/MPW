<script setup lang="ts">
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Download,
    FileVideo,
    Loader2,
    Trash2,
    XCircle,
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { download as videosDownload } from '@/routes/videos';

export interface Video {
    id: number;
    batch_id: number;
    user_id: number;
    original_filename: string;
    original_path: string;
    processed_path: string | null;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    original_size: number;
    processed_size: number | null;
    error_message: string | null;
    created_at: string;
    updated_at: string;
}

defineProps<{
    video: Video;
    canDelete?: boolean;
}>();

const emit = defineEmits<{
    delete: [video: Video];
}>();

const statusConfig: Record<Video['status'], { label: string; icon: typeof Clock; colorClass: string }> = {
    pending: { label: 'Pendente', icon: Clock, colorClass: 'text-muted-foreground' },
    processing: { label: 'Processando', icon: Loader2, colorClass: 'text-primary' },
    completed: { label: 'Concluído', icon: CheckCircle2, colorClass: 'text-emerald-500' },
    failed: { label: 'Falhou', icon: XCircle, colorClass: 'text-destructive' },
};

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`;
}
</script>

<template>
    <div class="flex items-center gap-4 p-4 transition-colors hover:bg-muted/30">
        <div
            class="flex size-10 shrink-0 items-center justify-center rounded-lg"
            :class="{
                'bg-muted': video.status === 'pending',
                'bg-primary/10': video.status === 'processing',
                'bg-emerald-500/10': video.status === 'completed',
                'bg-destructive/10': video.status === 'failed',
            }"
        >
            <FileVideo
                class="size-5"
                :class="statusConfig[video.status].colorClass"
            />
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">
                {{ video.original_filename }}
            </p>
            <div class="text-muted-foreground mt-0.5 flex items-center gap-3 text-xs">
                <span>{{ formatFileSize(video.original_size) }}</span>
                <span
                    v-if="video.processed_size"
                    class="text-emerald-500"
                >
                    &rarr; {{ formatFileSize(video.processed_size) }}
                </span>
            </div>
            <p
                v-if="video.error_message"
                class="text-destructive mt-1 flex items-center gap-1 text-xs"
            >
                <AlertTriangle class="size-3 shrink-0" />
                <span class="truncate">{{ video.error_message }}</span>
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <Badge
                :variant="video.status === 'failed' ? 'destructive' : video.status === 'completed' ? 'default' : 'secondary'"
                :class="{
                    'bg-emerald-600 hover:bg-emerald-600/90': video.status === 'completed',
                    'animate-pulse': video.status === 'processing',
                }"
                class="hidden sm:inline-flex"
            >
                <component
                    :is="statusConfig[video.status].icon"
                    class="size-3"
                    :class="{ 'animate-spin': video.status === 'processing' }"
                />
                {{ statusConfig[video.status].label }}
            </Badge>

            <TooltipProvider v-if="video.status === 'completed' && video.processed_path">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <a :href="videosDownload(video.id).url">
                            <Button variant="outline" size="icon-sm" aria-label="Download do vídeo processado">
                                <Download class="size-4" />
                            </Button>
                        </a>
                    </TooltipTrigger>
                    <TooltipContent>Download processado</TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <TooltipProvider v-if="canDelete">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive hover:text-white"
                            aria-label="Excluir vídeo"
                            @click="emit('delete', video)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Remover vídeo</TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
    </div>
</template>
