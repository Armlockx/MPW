<?php

namespace App\Jobs;

use App\Enums\BatchStatus;
use App\Enums\VideoStatus;
use App\Models\Batch;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public Video $video,
    ) {}

    public function handle(): void
    {
        if ($this->video->status === VideoStatus::Completed) {
            return;
        }

        $this->video->update(['status' => VideoStatus::Processing]);

        $batch = $this->video->batch;
        $inputPath = Storage::disk('videos_originals')->path($this->video->original_path);
        $outputRelPath = $this->outputPath($batch);

        Storage::disk('videos_processed')->makeDirectory(dirname($outputRelPath));
        $outputFullPath = Storage::disk('videos_processed')->path($outputRelPath);

        $command = $this->buildCommand($inputPath, $outputFullPath, $batch);

        Log::info('ProcessVideoJob: executing FFmpeg', [
            'video_id' => $this->video->id,
            'command' => implode(' ', array_map('escapeshellarg', $command)),
        ]);

        $result = Process::timeout(max($this->timeout - 30, 60))->run($command);

        if (! $result->successful()) {
            throw new \RuntimeException(
                'FFmpeg exited with code '.$result->exitCode().': '.Str::limit($result->errorOutput(), 1000)
            );
        }

        $this->video->update([
            'status' => VideoStatus::Completed,
            'processed_path' => $outputRelPath,
            'processed_size' => Storage::disk('videos_processed')->size($outputRelPath),
        ]);

        $this->updateBatchStatus();
    }

    public function failed(\Throwable $exception): void
    {
        $this->video->update([
            'status' => VideoStatus::Failed,
            'error_message' => Str::limit($exception->getMessage(), 500),
        ]);

        $this->updateBatchStatus();
    }

    private function outputPath(Batch $batch): string
    {
        $dir = "{$this->video->user_id}/{$batch->id}";
        $name = pathinfo($this->video->original_filename, PATHINFO_FILENAME);

        return "{$dir}/{$name}_processed.mp4";
    }

    private function buildCommand(string $inputPath, string $outputPath, Batch $batch): array
    {
        $ffmpeg = config('laravel-ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $threads = config('laravel-ffmpeg.ffmpeg.threads', 12);

        $speedFactor = $batch->speed_factor ?? 1.0;
        $needsSlowMotion = abs($speedFactor - 1.0) > 0.001 && $speedFactor > 0;
        $hasImageWatermark = $batch->watermark_type === 'image' && $batch->watermark_image_path;
        $hasTextWatermark = $batch->watermark_type === 'text' && ! empty($batch->watermark_text);
        $hasResize = $batch->target_width && $batch->target_height;

        $cmd = [$ffmpeg, '-y', '-i', $inputPath];

        if ($hasImageWatermark) {
            $cmd[] = '-i';
            $cmd[] = Storage::disk('watermarks')->path($batch->watermark_image_path);
        }

        $videoFilters = [];

        if ($needsSlowMotion) {
            $pts = round(1.0 / $speedFactor, 4);
            $videoFilters[] = "setpts={$pts}*PTS";
        }

        if ($hasResize) {
            $w = $batch->target_width;
            $h = $batch->target_height;
            $videoFilters[] = "scale={$w}:{$h}:force_original_aspect_ratio=decrease";
            $videoFilters[] = "pad={$w}:{$h}:(ow-iw)/2:(oh-ih)/2";
        }

        if ($hasTextWatermark) {
            $videoFilters[] = $this->drawtextFilter($batch);
        }

        $audioFilters = $needsSlowMotion ? $this->atempoChain($speedFactor) : [];

        $hasAnyFilter = ! empty($videoFilters) || ! empty($audioFilters) || $hasImageWatermark;

        if ($hasImageWatermark) {
            $this->appendComplexFilter($cmd, $videoFilters, $audioFilters, $batch);
        } elseif ($hasAnyFilter) {
            if (! empty($videoFilters)) {
                $cmd[] = '-vf';
                $cmd[] = implode(',', $videoFilters);
            }
            if (! empty($audioFilters)) {
                $cmd[] = '-af';
                $cmd[] = implode(',', $audioFilters);
            }
        }

        if ($hasAnyFilter) {
            array_push($cmd, '-c:v', 'libx264', '-crf', '23', '-preset', 'medium');
            array_push($cmd, '-c:a', 'aac', '-b:a', '128k');
        } else {
            array_push($cmd, '-c', 'copy');
        }

        array_push($cmd,
            '-threads', (string) ($threads ?: 0),
            '-movflags', '+faststart',
            $outputPath,
        );

        return $cmd;
    }

    private const WATERMARK_REFERENCE_WIDTH = 1080;

    /**
     * Append -filter_complex and -map args for image-watermark overlay.
     *
     * Uses scale2ref to size the watermark proportionally to the video,
     * so the same logo occupies the same percentage of the frame
     * regardless of video resolution.
     */
    private function appendComplexFilter(array &$cmd, array $videoFilters, array $audioFilters, Batch $batch): void
    {
        $opacity = $batch->watermark_opacity ?? 1.0;
        $isCustom = $batch->watermark_position === 'custom';
        $overlay = $isCustom
            ? $this->customOverlayPosition($batch)
            : $this->overlayPosition($batch->watermark_position);

        $ref = self::WATERMARK_REFERENCE_WIDTH;
        $segments = [];

        if (! empty($videoFilters)) {
            $segments[] = '[0:v]'.implode(',', $videoFilters).'[vbase]';
            $videoRef = '[vbase]';
        } else {
            $videoRef = '[0:v]';
        }

        $scaleExpr = "main_w*iw/{$ref}";
        if ($isCustom) {
            $scale = $batch->watermark_scale ?? 1.0;
            $scaleExpr = "main_w*iw/{$ref}*{$scale}";
        }
        $segments[] = "[1:v]{$videoRef}scale2ref=w={$scaleExpr}:h=-1[wmraw][vref]";

        $wmFilters = ['format=rgba'];
        if ($opacity < 1.0) {
            $wmFilters[] = "colorchannelmixer=aa={$opacity}";
        }
        if ($isCustom) {
            $rotation = $batch->watermark_rotation ?? 0;
            if (abs($rotation) > 0.001) {
                $radians = round(deg2rad($rotation), 6);
                $wmFilters[] = "rotate={$radians}:fillcolor=none:ow=rotw({$radians}):oh=roth({$radians})";
            }
        }
        $segments[] = '[wmraw]'.implode(',', $wmFilters).'[wm]';

        $segments[] = "[vref][wm]overlay={$overlay}[outv]";

        $hasAudioFilter = ! empty($audioFilters);
        if ($hasAudioFilter) {
            $segments[] = '[0:a]'.implode(',', $audioFilters).'[outa]';
        }

        $cmd[] = '-filter_complex';
        $cmd[] = implode(';', $segments);
        $cmd[] = '-map';
        $cmd[] = '[outv]';
        $cmd[] = '-map';
        $cmd[] = $hasAudioFilter ? '[outa]' : '0:a?';
    }

    private function customOverlayPosition(Batch $batch): string
    {
        $x = $batch->watermark_custom_x ?? 50;
        $y = $batch->watermark_custom_y ?? 50;

        $xExpr = round($x / 100, 6);
        $yExpr = round($y / 100, 6);

        return "(W*{$xExpr}-w/2):(H*{$yExpr}-h/2)";
    }

    private function drawtextFilter(Batch $batch): string
    {
        $text = str_replace(
            ['\\', "'", ':', '%'],
            ['\\\\', "\\'", '\\:', '%%'],
            $batch->watermark_text
        );

        $opacity = round($batch->watermark_opacity ?? 1.0, 2);
        $ref = self::WATERMARK_REFERENCE_WIDTH;
        $isCustom = $batch->watermark_position === 'custom';

        if ($isCustom) {
            [$x, $y] = $this->customTextPosition($batch);
            $scale = $batch->watermark_scale ?? 1.0;
            $fontsizeExpr = "h*48/{$ref}*{$scale}";
        } else {
            [$x, $y] = $this->textPosition($batch->watermark_position);
            $fontsizeExpr = "h*48/{$ref}";
        }

        return "drawtext=text='{$text}':fontsize={$fontsizeExpr}:fontcolor=white@{$opacity}"
            .":x={$x}:y={$y}:shadowcolor=black@0.5:shadowx=2:shadowy=2";
    }

    private function customTextPosition(Batch $batch): array
    {
        $x = $batch->watermark_custom_x ?? 50;
        $y = $batch->watermark_custom_y ?? 50;

        $xExpr = round($x / 100, 6);
        $yExpr = round($y / 100, 6);

        return ["w*{$xExpr}-tw/2", "h*{$yExpr}-th/2"];
    }

    private function textPosition(?string $position): array
    {
        return match ($position) {
            'top-left' => ['10', '10'],
            'top-right' => ['w-tw-10', '10'],
            'top-center' => ['(w-tw)/2', '10'],
            'center' => ['(w-tw)/2', '(h-th)/2'],
            'bottom-left' => ['10', 'h-th-10'],
            'bottom-center' => ['(w-tw)/2', 'h-th-10'],
            default => ['w-tw-10', 'h-th-10'],
        };
    }

    private function overlayPosition(?string $position): string
    {
        return match ($position) {
            'top-left' => '10:10',
            'top-right' => 'W-w-10:10',
            'top-center' => '(W-w)/2:10',
            'center' => '(W-w)/2:(H-h)/2',
            'bottom-left' => '10:H-h-10',
            'bottom-center' => '(W-w)/2:H-h-10',
            default => 'W-w-10:H-h-10',
        };
    }

    /**
     * Build atempo filter chain. Each atempo instance supports 0.5–100.0,
     * so values below 0.5 require chaining multiple filters.
     */
    private function atempoChain(float $speedFactor): array
    {
        $filters = [];
        $remaining = $speedFactor;

        while ($remaining < 0.5) {
            $filters[] = 'atempo=0.5';
            $remaining /= 0.5;
        }

        $filters[] = 'atempo='.round($remaining, 4);

        return $filters;
    }

    private function updateBatchStatus(): void
    {
        /** @var Batch $batch */
        $batch = $this->video->batch;
        $total = $batch->videos()->count();
        $completed = $batch->videos()->where('status', VideoStatus::Completed)->count();
        $failed = $batch->videos()->where('status', VideoStatus::Failed)->count();

        if ($completed + $failed < $total) {
            return;
        }

        $batch->update([
            'status' => $failed === $total
                ? BatchStatus::Failed
                : BatchStatus::Completed,
        ]);
    }
}
