<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionScheduleEmail extends Model
{
    protected $fillable = ['distribution_batch_id', 'farmer_id', 'status', 'gmail_message_id'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DistributionBatch::class, 'distribution_batch_id');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}
