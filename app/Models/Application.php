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
        'loan_decision_attachment_path',
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

    public static function hasApprovedPcicNewApplication(int $farmerId, int $programId): bool
    {
        return static::where('farmer_id', $farmerId)
            ->where('program_id', $programId)
            ->where('status', 'approved')
            ->whereHas('benefit', fn ($query) => $query->where('name', 'New Application'))
            ->exists();
    }

    public function hasPcicSubmittedForm(): bool
    {
        return $this->program?->isPcicCropInsurance() && (
            in_array($this->detail?->form_type, ['pcic_new_application', 'pcic_claim'], true)
            || in_array($this->benefit?->name, ['New Application', 'Claim for Indemnity'], true)
        );
    }

    public function isAgriculturalLoan(): bool
    {
        return $this->program?->name === 'Agricultural Loans';
    }

    public function requiresDecisionAttachment(): bool
    {
        return $this->program?->isPcicCropInsurance() || $this->isAgriculturalLoan();
    }

    public function isPcicClaim(): bool
    {
        return $this->program?->isPcicCropInsurance() && $this->benefit?->name === 'Claim for Indemnity';
    }

    public function hasOfficialPcicApproval(): bool
    {
        $status = $this->pcicTrackingUpdates()->whereIn('status', PcicTrackingUpdate::OFFICIAL_STATUSES)
            ->whereNotNull('pcic_reference')->latest('id')->value('status');

        return in_array($status, ['approved', 'release_scheduled', 'released'], true);
    }

    public function latestPcicTrackingUpdate(): HasOne
    {
        return $this->hasOne(PcicTrackingUpdate::class)->latestOfMany();
    }

    public function pcicDecisionAttachment(): HasOne
    {
        return $this->hasOne(PcicTrackingUpdate::class)
            ->ofMany(['id' => 'max'], fn ($query) => $query->whereNotNull('attachment_path'));
    }

    public function detail(): HasOne
    {
        return $this->hasOne(ApplicationDetail::class);
    }
}
