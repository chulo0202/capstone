<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionBatch extends Model
{
    protected $fillable = [
        'name', 'program_id', 'program_benefit_id', 'scheduled_date', 'start_time',
        'end_time', 'location', 'instructions', 'status', 'created_by',
    ];

    protected $casts = ['scheduled_date' => 'date'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(ProgramBenefit::class, 'program_benefit_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DistributionAssignment::class);
    }

    public function scheduleEmails(): HasMany
    {
        return $this->hasMany(DistributionScheduleEmail::class);
    }

    public function smsNotifications(): HasMany
    {
        return $this->hasMany(SmsNotification::class);
    }

    public function canRelease(): bool
    {
        $now = now();

        return $this->status === 'published'
            && $this->scheduled_date->isSameDay($now)
            && $now->format('H:i') >= substr($this->start_time, 0, 5)
            && $now->format('H:i') <= substr($this->end_time, 0, 5);
    }
}
