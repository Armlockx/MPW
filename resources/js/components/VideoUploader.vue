<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Loader2, Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import { store as videosStore } from '@/routes/videos';

const props = defineProps<{
    batchId: number;
    disabled?: boolean;
}>();

const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const uploadForm = useForm({
    videos: [] as File[],
});

function onDragOver(event: DragEvent) {
    event.preventDefault();
    if (!props.disabled) isDragging.value = true;
}

function onDragLeave() {
    isDragging.value = false;
}

function onDrop(event: DragEvent) {
    event.preventDefault();
    isDragging.value = false;
    if (props.disabled || !event.dataTransfer?.files.length) return;
    handleFiles(event.dataTransfer.files);
}

function onFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files?.length) {
        handleFiles(target.files);
        target.value = '';
    }
}

function handleFiles(fileList: FileList) {
    const videoFiles = Array.from(fileList).filter((f) =>
        f.type.startsWith('video/'),
    );
    if (videoFiles.length === 0) return;

    uploadForm.videos = videoFiles;
    uploadForm.post(videosStore(props.batchId).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
        },
    });
}
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="!disabled"
            class="relative cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors"
            :class="isDragging
                ? 'border-primary bg-primary/5'
                : 'border-border hover:border-muted-foreground/50'"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            @click="fileInput?.click()"
        >
            <div class="flex flex-col items-center gap-2">
                <div
                    class="flex size-12 items-center justify-center rounded-full transition-colors"
                    :class="isDragging ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                >
                    <Upload class="size-6" />
                </div>
                <div>
                    <p class="font-medium" :class="isDragging ? 'text-primary' : ''">
                        {{ isDragging ? 'Solte os vídeos aqui' : 'Arraste vídeos ou clique para selecionar' }}
                    </p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        MP4, MOV, AVI, MKV, WebM - Máximo 500 MB por arquivo
                    </p>
                </div>
            </div>
            <input
                ref="fileInput"
                type="file"
                accept="video/*"
                multiple
                class="hidden"
                @change="onFileSelect"
            />
        </div>

        <div
            v-if="uploadForm.processing"
            class="flex items-center gap-3 rounded-lg border p-4"
        >
            <Loader2 class="text-primary size-5 animate-spin" />
            <span class="text-sm font-medium">Enviando vídeos...</span>
        </div>

        <div v-if="uploadForm.errors.videos" class="text-destructive text-sm">
            {{ uploadForm.errors.videos }}
        </div>
    </div>
</template>
