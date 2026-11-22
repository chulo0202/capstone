<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionAssignment extends Model
{
    protected $fillable = ['application_id', 'active_application_id', 'cancelled_at', 'released_at'];

    protected $casts = ['cancelled_at' => 'datetime', 'released_at' => 'datetime'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DistributionBatch::class, 'distribution_batch_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
