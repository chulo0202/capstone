<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function detail(): HasOne
    {
        return $this->hasOne(ApplicationDetail::class);
    }
}
