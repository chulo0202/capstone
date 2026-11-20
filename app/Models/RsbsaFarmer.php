<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsbsaFarmer extends Model
{
    use HasFactory;

    protected $fillable = [
        'rsbsa_number',
        'full_name',
        'barangay',
        'address',
        'birthdate',
        'crop_type',
        'land_size',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];
}