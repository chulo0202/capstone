<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BenefitConfiguration extends Model
{
    protected $fillable = [
        'program_benefit_id',
        'description',
        'start_at',
        'end_at',
        'eligibility_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'eligibility_snapshot' => 'array',
        ];
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(
            ProgramBenefit::class,
            'program_benefit_id'
        );
    }

    public function isCurrentAt(Carbon $now): bool
    {
        return $this->start_at->lte($now) && $this->end_at->gte($now);
    }

    public function getStatusAttribute(): string
    {
        $now = now();

        if ($this->end_at->lt($now)) {
            return 'Expired';
        }

        if ($this->start_at->gt($now)) {
            return 'Upcoming';
        }

        return 'Active';
    }
}
