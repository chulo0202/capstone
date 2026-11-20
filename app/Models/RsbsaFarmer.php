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

    public function registrationDetails(): array
    {
        $address = $this->address;

        if (filled($address)) {
            $parts = array_map('trim', explode(',', $address));
            $locality = array_values(array_filter([$this->barangay, 'Bulan', 'Sorsogon']));

            foreach ($locality as $part) {
                if (! in_array(mb_strtolower($part), array_map('mb_strtolower', $parts), true)) {
                    $parts[] = $part;
                }
            }

            $address = implode(', ', array_filter($parts, fn ($part) => $part !== ''));
        }

        return [
            'full_name' => $this->full_name,
            'address' => $address,
            'barangay' => $this->barangay,
            'birthdate' => $this->birthdate?->toDateString(),
            'crop_type' => $this->crop_type,
            'land_size' => $this->land_size,
            'rsbsa_number' => $this->rsbsa_number,
        ];
    }
}