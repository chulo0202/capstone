<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Program extends Model
{
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

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
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