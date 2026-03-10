<?php

namespace App\Http\Requests\Batch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('batch')->user_id;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'speed_factor' => ['sometimes', 'numeric', 'min:0.25', 'max:1.0'],
            'watermark_type' => ['nullable', 'string', 'in:image,text'],
            'watermark_image' => ['nullable', 'required_if:watermark_type,image', 'image', 'max:5120'],
            'watermark_text' => ['nullable', 'required_if:watermark_type,text', 'string', 'max:255'],
            'watermark_position' => ['nullable', 'string', 'in:top-left,top-right,bottom-left,bottom-right,center,custom'],
            'watermark_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'watermark_scale' => ['nullable', 'numeric', 'min:0.1', 'max:2'],
            'watermark_custom_x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'watermark_custom_y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'watermark_rotation' => ['nullable', 'numeric', 'min:-180', 'max:180'],
            'target_width' => ['nullable', 'integer', 'min:1'],
            'target_height' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
