<?php

namespace App\Exports\Sheets;

use App\Models\Farmer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class FarmersSheet implements FromCollection
{
    public function collection(): Collection
    {
        $rows = collect([
            [
                'ID',
                'Full Name',
                'Address',
                'Barangay',
                'Contact Number',
                'Birthdate',
                'Crop Type',
                'Land Size (ha)',
                'RSBSA Status',
                'RSBSA Number',
                'Association Member',
                '4Ps Member',
                'Farmer Association',
                'Profile Completed',
            ],
        ]);

        Farmer::query()
            ->orderBy('full_name')
            ->get()
            ->each(function (Farmer $farmer) use ($rows) {
                $rows->push([
                    $farmer->id,
                    $farmer->full_name,
                    $farmer->address,
                    $farmer->barangay,
                    $farmer->contact_number,
                    $farmer->birthdate?->format('Y-m-d'),
                    $farmer->crop_type,
                    $farmer->land_size,
                    $farmer->rsbsa_status ? 'Yes' : 'No',
                    $farmer->rsbsa_number,
                    $farmer->association_membership ? 'Yes' : 'No',
                    $farmer->four_ps_membership ? 'Yes' : 'No',
                    $farmer->farmer_association,
                    $farmer->profile_completed ? 'Yes' : 'No',
                ]);
            });

        return $rows;
    }
}