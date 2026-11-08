<?php

namespace App\Exports\Sheets;

use App\Models\Distribution;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class DistributionsSheet implements FromCollection
{
    public function collection(): Collection
    {
        $rows = collect([
            [
                'ID',
                'Farmer ID',
                'Farmer Name',
                'Program ID',
                'Program Name',
                'Released By',
                'Distributed At',
                'Notes',
            ],
        ]);

        Distribution::with(['farmer', 'program', 'releasedBy'])
            ->latest('distributed_at')
            ->get()
            ->each(function (Distribution $distribution) use ($rows) {
                $rows->push([
                    $distribution->id,
                    $distribution->farmer_id,
                    $distribution->farmer?->full_name,
                    $distribution->program_id,
                    $distribution->program?->name,
                    $distribution->releasedBy?->name,
                    $distribution->distributed_at?->format('Y-m-d H:i:s'),
                    $distribution->notes,
                ]);
            });

        return $rows;
    }
}

