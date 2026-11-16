<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PcicInsuranceCoverage extends Model
{
    protected $table = 'pcic_insurance_coverages';

    protected $fillable = [
        'farmer_id',
        'policy_number',
        'crop_type',
        'area_insured',
        'coverage_start_date',
        'coverage_end_date',
        'verification_status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'verification_evidence_reference',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'area_insured' => 'decimal:2',
            'coverage_start_date' => 'date',
            'coverage_end_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function damageIncidents(): HasMany
    {
        return $this->hasMany(
            PcicDamageIncident::class,
            'pcic_insurance_coverage_id'
        );
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified'
            && $this->verified_by !== null
            && $this->verified_at !== null;
    }
}
