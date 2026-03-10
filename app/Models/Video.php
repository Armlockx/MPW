<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    protected $fillable = [
        'batch_id',
        'user_id',
        'original_filename',
        'original_path',
        'processed_path',
        'status',
        'original_size',
        'processed_size',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => VideoStatus::class,
            'original_size' => 'integer',
            'processed_size' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
