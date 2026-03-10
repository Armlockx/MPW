<?php

namespace App\Http\Requests\Video;

use App\Enums\BatchStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'videos' => ['required', 'array', 'min:1'],
            'videos.*' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm', 'max:512000'],
        ];
    }

    public function messages(): array
    {
        return [
            'videos.required' => 'Selecione ao menos um vídeo para upload.',
            'videos.*.mimetypes' => 'O arquivo :position não é um formato de vídeo suportado (MP4, MOV, AVI, MKV, WebM).',
            'videos.*.max' => 'O arquivo :position excede o tamanho máximo de 500 MB.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $batch = $this->route('batch');

            if ($batch->status === BatchStatus::Processing) {
                $validator->errors()->add('batch', 'Não é possível adicionar vídeos a um lote em processamento.');
            }
        });
    }
}
