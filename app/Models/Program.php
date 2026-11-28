<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Program extends Model
{
    public const PCIC_NAME = 'Philippine Crop Insurance Corporation (PCIC)';

    public const DISTRIBUTION_PROGRAMS = ['Seed Subsidies', 'Fertilizer Subsidies'];

    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'eligibility_criteria',
        'is_active',
        'start_at',
        'end_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function eligibilityRule(): HasOne
    {
        return $this->hasOne(EligibilityRule::class)
            ->whereNull('program_benefit_id');
    }

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(EligibilityRule::class);
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(ProgramBenefit::class);
    }

    public function activeBenefits(): HasMany
    {
        return $this->hasMany(ProgramBenefit::class)
            ->whereNull('archived_at');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNull('archived_at');
    }

    public function scopeForFarmerRecommendations(Builder $query): Builder
    {
        return $query->whereNull('archived_at')
            ->where(fn (Builder $programs) => $programs->where('is_active', true)
                ->orWhere('name', self::PCIC_NAME))
            ->with(['eligibilityRule', 'activeBenefits.configurations']);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function isPcicCropInsurance(): bool
    {
        return $this->name === self::PCIC_NAME;
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}