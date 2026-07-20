<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farmer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'address',
        'barangay',
        'contact_number',
        'birthdate',
        'crop_type',
        'land_size',
        'rsbsa_status',
        'rsbsa_number',
        'association_membership',
        'four_ps_membership',
        'farmer_association',
        'valid_id_path',
        'farmer_photo_path',
        'qr_code_path',
        'qr_code_token',
        'profile_completed',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'land_size' => 'decimal:2',
            'rsbsa_status' => 'boolean',
            'association_membership' => 'boolean',
            'four_ps_membership' => 'boolean',
            'profile_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function smsNotifications(): HasMany
    {
        return $this->hasMany(SmsNotification::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
