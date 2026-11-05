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
     * A readable reason for a failed request: OpenAI's error message, or the stored text.
     */
    public function errorMessage(): string
    {
        $message = data_get($this->response, 'error.message')
            ?? (is_string($this->response) ? $this->response : null);

        return filled($message) ? $message : 'Unknown error.';
    }

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
