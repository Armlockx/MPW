<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'status',
        'speed_factor',
        'watermark_type',
        'watermark_image_path',
        'watermark_text',
        'watermark_position',
        'watermark_opacity',
        'watermark_scale',
        'watermark_custom_x',
        'watermark_custom_y',
        'watermark_rotation',
        'target_width',
        'target_height',
    ];

    protected function casts(): array
    {
        return [
            'status' => BatchStatus::class,
            'speed_factor' => 'float',
            'watermark_opacity' => 'float',
            'watermark_scale' => 'float',
            'watermark_custom_x' => 'float',
            'watermark_custom_y' => 'float',
            'watermark_rotation' => 'float',
            'target_width' => 'integer',
            'target_height' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
