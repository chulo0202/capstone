<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    public function pcicDamageIncident(): HasOne
    {
        return $this->hasOne(
            PcicDamageIncident::class,
            'application_id'
        );
    }

    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'program_id',
        'program_benefit_id',
        'status',
        'remarks',
        'applied_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function scopeWithCurrentBenefit(Builder $query): Builder
    {
        return $query->whereHas('benefit', fn (Builder $benefit) => $benefit->active())
            ->whereHas('program', fn (Builder $program) => $program->whereNull('archived_at'));
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(ProgramBenefit::class, 'program_benefit_id');
    }

    public function activeDistributionAssignment(): HasOne
    {
        return $this->hasOne(DistributionAssignment::class, 'active_application_id');
    }

    public function pcicTrackingUpdates(): HasMany
    {
        return $this->hasMany(PcicTrackingUpdate::class);
    }

    public function latestPcicTrackingUpdate(): HasOne
    {
        return $this->hasOne(PcicTrackingUpdate::class)->latestOfMany();
    }

    public function detail(): HasOne
    {
        return $this->hasOne(ApplicationDetail::class);
    }
}
