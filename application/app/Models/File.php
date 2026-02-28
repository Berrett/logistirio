<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $guarded = ['id'];

    public function aiRequests()
    {
        return $this->hasMany(AiRequest::class, 'file_id');
    }

    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
