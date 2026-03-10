<?php

namespace App\Http\Controllers;

use App\Enums\VideoStatus;
use App\Http\Requests\Video\StoreVideoRequest;
use App\Models\Batch;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class VideoController extends Controller
{
    public function store(StoreVideoRequest $request, Batch $batch): RedirectResponse
    {
        $userId = $request->user()->id;
        $storagePath = "{$userId}/{$batch->id}";

        foreach ($request->file('videos') as $file) {
            $path = $file->store($storagePath, 'videos_originals');

            $batch->videos()->create([
                'user_id' => $userId,
                'original_filename' => $file->getClientOriginalName(),
                'original_path' => $path,
                'status' => VideoStatus::Pending,
                'original_size' => $file->getSize(),
            ]);
        }

        return back()->with('success', count($request->file('videos')) . ' vídeo(s) adicionado(s) com sucesso.');
    }

    public function destroy(Request $request, Video $video): RedirectResponse
    {
        abort_unless($video->user_id === $request->user()->id, 403);

        Storage::disk('videos_originals')->delete($video->original_path);

        if ($video->processed_path) {
            Storage::disk('videos_processed')->delete($video->processed_path);
        }

        $batchId = $video->batch_id;
        $video->delete();

        return back()->with('success', 'Vídeo removido com sucesso.');
    }

    public function download(Request $request, Video $video): BinaryFileResponse
    {
        abort_unless($video->user_id === $request->user()->id, 403);
        abort_unless($video->status === VideoStatus::Completed && $video->processed_path, 404);

        $disk = Storage::disk('videos_processed');
        abort_unless($disk->exists($video->processed_path), 404);

        $processedName = pathinfo($video->original_filename, PATHINFO_FILENAME) . '_processed.'
            . pathinfo($video->original_filename, PATHINFO_EXTENSION);

        return response()->download(
            $disk->path($video->processed_path),
            $processedName,
        );
    }

    public function downloadBatch(Request $request, Batch $batch): BinaryFileResponse
    {
        abort_unless($batch->user_id === $request->user()->id, 403);

        $completedVideos = $batch->videos()
            ->where('status', VideoStatus::Completed)
            ->whereNotNull('processed_path')
            ->get();

        abort_if($completedVideos->isEmpty(), 404, 'Nenhum vídeo processado disponível para download.');

        $disk = Storage::disk('videos_processed');

        $zipFileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $batch->name) . '_processados.zip';
        $zipPath = storage_path("app/private/temp/{$zipFileName}");

        $tempDir = dirname($zipPath);
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        foreach ($completedVideos as $video) {
            if ($disk->exists($video->processed_path)) {
                $processedName = pathinfo($video->original_filename, PATHINFO_FILENAME) . '_processed.'
                    . pathinfo($video->original_filename, PATHINFO_EXTENSION);

                $zip->addFile($disk->path($video->processed_path), $processedName);
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend();
    }
}
