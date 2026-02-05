<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiRequest extends Model
{
    protected $fillable = [
        'prompt',
        'file_id',
        'response_status',
        'response',
    ];

    /**
     * Get the file associated with the AI request.
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
