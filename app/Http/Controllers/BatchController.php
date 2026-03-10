<?php

namespace App\Http\Controllers;

use App\Enums\BatchStatus;
use App\Enums\VideoStatus;
use App\Http\Requests\Batch\StoreBatchRequest;
use App\Http\Requests\Batch\UpdateBatchRequest;
use App\Jobs\ProcessVideoJob;
use App\Models\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BatchController extends Controller
{
    public function index(Request $request): Response
    {
        $batches = $request->user()
            ->batches()
            ->withCount([
                'videos',
                'videos as videos_completed_count' => fn ($q) => $q->where('status', VideoStatus::Completed),
                'videos as videos_failed_count' => fn ($q) => $q->where('status', VideoStatus::Failed),
            ])
            ->latest()
            ->get();

        return Inertia::render('Batches/Index', [
            'batches' => $batches,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Batches/Create');
    }

    public function store(StoreBatchRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $watermarkImagePath = null;
        if ($request->hasFile('watermark_image')) {
            $userId = $request->user()->id;
            $watermarkImagePath = $request->file('watermark_image')
                ->store($userId, 'watermarks');
        }

        $isCustomPosition = ($data['watermark_position'] ?? null) === 'custom';

        $batch = $request->user()->batches()->create([
            'name' => $data['name'],
            'speed_factor' => $data['speed_factor'] ?? 1.0,
            'watermark_type' => $data['watermark_type'] ?? null,
            'watermark_image_path' => $watermarkImagePath,
            'watermark_text' => $data['watermark_text'] ?? null,
            'watermark_position' => $data['watermark_position'] ?? null,
            'watermark_opacity' => $data['watermark_opacity'] ?? null,
            'watermark_scale' => $isCustomPosition ? ($data['watermark_scale'] ?? null) : null,
            'watermark_custom_x' => $isCustomPosition ? ($data['watermark_custom_x'] ?? null) : null,
            'watermark_custom_y' => $isCustomPosition ? ($data['watermark_custom_y'] ?? null) : null,
            'watermark_rotation' => $isCustomPosition ? ($data['watermark_rotation'] ?? null) : null,
            'target_width' => $data['target_width'] ?? null,
            'target_height' => $data['target_height'] ?? null,
        ]);

        return to_route('batches.show', $batch);
    }

    public function show(Request $request, Batch $batch): Response
    {
        abort_unless($batch->user_id === $request->user()->id, 403);

        $batch->loadCount([
            'videos',
            'videos as videos_completed_count' => fn ($q) => $q->where('status', VideoStatus::Completed),
            'videos as videos_failed_count' => fn ($q) => $q->where('status', VideoStatus::Failed),
        ]);

        $videos = $batch->videos()->latest()->get();

        return Inertia::render('Batches/Show', [
            'batch' => $batch,
            'videos' => $videos,
        ]);
    }

    public function update(UpdateBatchRequest $request, Batch $batch): RedirectResponse
    {
        if ($batch->status === BatchStatus::Processing) {
            return back()->withErrors([
                'status' => 'Não é possível editar um lote que está em processamento.',
            ]);
        }

        $data = $request->validated();

        if ($request->hasFile('watermark_image')) {
            if ($batch->watermark_image_path) {
                Storage::disk('watermarks')->delete($batch->watermark_image_path);
            }

            $userId = $request->user()->id;
            $data['watermark_image_path'] = $request->file('watermark_image')
                ->store($userId, 'watermarks');
        }

        unset($data['watermark_image']);

        if (isset($data['watermark_type']) && $data['watermark_type'] !== 'image') {
            if ($batch->watermark_image_path) {
                Storage::disk('watermarks')->delete($batch->watermark_image_path);
            }
            $data['watermark_image_path'] = null;
        }

        if (isset($data['watermark_position']) && $data['watermark_position'] !== 'custom') {
            $data['watermark_scale'] = null;
            $data['watermark_custom_x'] = null;
            $data['watermark_custom_y'] = null;
            $data['watermark_rotation'] = null;
        }

        $batch->update($data);

        return to_route('batches.show', $batch);
    }

    public function destroy(Request $request, Batch $batch): RedirectResponse
    {
        abort_unless($batch->user_id === $request->user()->id, 403);

        $userId = $request->user()->id;
        $batchId = $batch->id;

        Storage::disk('videos_originals')->deleteDirectory("{$userId}/{$batchId}");
        Storage::disk('videos_processed')->deleteDirectory("{$userId}/{$batchId}");

        if ($batch->watermark_image_path) {
            Storage::disk('watermarks')->delete($batch->watermark_image_path);
        }

        $batch->delete();

        return to_route('batches.index');
    }

    public function process(Request $request, Batch $batch): RedirectResponse
    {
        abort_unless($batch->user_id === $request->user()->id, 403);

        if ($batch->status === BatchStatus::Processing) {
            return back()->withErrors([
                'status' => 'Este lote já está em processamento.',
            ]);
        }

        $videos = $batch->videos()
            ->whereIn('status', [VideoStatus::Pending, VideoStatus::Failed])
            ->get();

        if ($videos->isEmpty()) {
            return back()->withErrors([
                'videos' => 'Nenhum vídeo pendente para processar neste lote.',
            ]);
        }

        $batch->update(['status' => BatchStatus::Processing]);

        foreach ($videos as $video) {
            $video->update(['status' => VideoStatus::Pending, 'error_message' => null]);
            ProcessVideoJob::dispatch($video);
        }

        return back()->with('success', 'Processamento iniciado.');
    }
}
