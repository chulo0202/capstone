<?php

namespace App\Models;

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

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(EligibilityRule::class);
    }
}