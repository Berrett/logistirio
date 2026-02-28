<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRequest extends Model
{
    protected $fillable = [
        'prompt',
        'file_id',
        'response_status',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    /**
     * Get the file associated with the AI request.
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the analyses associated with the AI request.
     */
    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }
}
