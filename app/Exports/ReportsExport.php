<?php

namespace App\Exports;

use App\Exports\Sheets\DistributionsSheet;
use App\Exports\Sheets\FarmersSheet;
use App\Exports\Sheets\ProgramsSheet;
use App\Exports\Sheets\SummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportsExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new SummarySheet(),
            new FarmersSheet(),
            new ProgramsSheet(),
            new DistributionsSheet(),
        ];
    }
}