<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    /** Stored images (uploads and PDF pages) are shrunk to this width at most. */
    public const MAX_IMAGE_WIDTH = 400;

    protected $guarded = ['id'];

    public function aiRequests()
    {
        return $this->hasMany(AiRequest::class, 'file_id');
    }

    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
