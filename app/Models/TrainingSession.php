<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    protected $fillable = [
        'title', 'topic', 'objectives', 'scheduled_date', 'start_time',
        'end_time', 'venue', 'trainer', 'slots', 'instructions',
    ];

    protected $casts = ['scheduled_date' => 'date'];

    public function registrations(): HasMany
    {
        return $this->hasMany(TrainingRegistration::class);
    }

    public function isUpcoming(): bool
    {
        $now = now('Asia/Manila');

        return $this->scheduled_date->format('Y-m-d').' '.substr($this->start_time, 0, 5)
            > $now->format('Y-m-d H:i');
    }
}
