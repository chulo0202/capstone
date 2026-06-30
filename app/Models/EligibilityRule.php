<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'crop_types',
        'min_land_size',
        'max_land_size',
        'requires_rsbsa',
        'requires_association',
    ];

    protected function casts(): array
    {
        return [
            'crop_types' => 'array',
            'min_land_size' => 'decimal:2',
            'max_land_size' => 'decimal:2',
            'requires_rsbsa' => 'boolean',
            'requires_association' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
