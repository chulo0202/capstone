<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcicDamageIncident extends Model
{

    public static function generateReference(): string
    {
        return 'INC-'
            .now()->format('Y')
            .'-'
            .strtoupper(Str::random(16));
    }

    protected $table = 'pcic_damage_incidents';

    protected $fillable = [
        'farmer_id',
        'pcic_insurance_coverage_id',
        'incident_reference',
        'date_of_loss',
        'cause_of_loss',
        'crop_type',
        'area_damaged',
        'status',
        'application_id',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_loss' => 'date',
            'area_damaged' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function insuranceCoverage(): BelongsTo
    {
        return $this->belongsTo(
            PcicInsuranceCoverage::class,
            'pcic_insurance_coverage_id'
        );
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
