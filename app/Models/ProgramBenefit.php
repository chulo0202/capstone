<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ProgramBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'name',
        'description',
        'always_active',
    ];
   protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'eligibility_configured_at' => 'datetime',
            'always_active' => 'boolean',
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

    public function inventoryStatus(Program $program, Carbon $now): string
    {
        if ($program->archived_at || $this->archived_at) {
            return 'Archived';
        }

        if ($this->configurations->contains(fn ($configuration) => $configuration->isCurrentAt($now))) {
            return $program->is_active ? 'Active' : 'Inactive';
        }

        if ($this->configurations->contains(fn ($configuration) => $configuration->start_at->gt($now))) {
            return 'Upcoming';
        }

        if ($this->configurations->isNotEmpty()) {
            return 'Expired';
        }

        if ($program->isPcicCropInsurance()) {
            return $this->isAvailableToFarmers($program, $now) ? 'Active' : 'Inactive';
        }

        return 'Available';
    }

    public function isAvailableToFarmers(Program $program, Carbon $now): bool
    {
        if ($program->archived_at || $this->archived_at) {
            return false;
        }

        if ($program->isPcicCropInsurance()) {
            return match ($this->name) {
                'Claim for Indemnity' => $this->always_active || $program->is_active,
                'New Application' => $program->is_active,
                default => false,
            };
        }

        if (! $program->is_active || ! $this->eligibility_configured_at) {
            return false;
        }

        if ($this->configurations->isNotEmpty()) {
            return $this->configurations->contains(fn ($configuration) => $configuration->isCurrentAt($now));
        }

        // Legacy benefits used the parent schedule before configurations were introduced.
        return $this->always_active || ($program->start_at?->lte($now) && $program->end_at?->gte($now));
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(
            BenefitConfiguration::class,
            'program_benefit_id'
        );
    }
}