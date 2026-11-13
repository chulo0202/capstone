<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'eligibility_configured_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeConfigured(Builder $query): Builder
    {
        return $query->whereNotNull('eligibility_configured_at');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(EligibilityRule::class);
    }
}