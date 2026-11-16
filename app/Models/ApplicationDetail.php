<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDetail extends Model
{
    protected $fillable = [
        'application_id',
        'form_type',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
