<?php

namespace App\Exports\Sheets;

use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class SummarySheet implements FromCollection
{
    public function collection(): Collection
    {
        return collect([
            ['FAMS SUMMARY REPORT'],
            ['Generated At', now()->format('Y-m-d H:i:s')],
            [''],
            ['Metric', 'Total'],
            ['Registered Farmers', Farmer::count()],
            ['Active Programs', Program::where('is_active', true)->count()],
            ['Total Distributions', Distribution::count()],
            ['Beneficiaries', Distribution::distinct('farmer_id')->count('farmer_id')],
        ]);
    }
}