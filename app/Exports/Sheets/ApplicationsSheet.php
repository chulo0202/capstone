<?php

namespace App\Exports\Sheets;

use App\Services\ReportData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ApplicationsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private ReportData $report) {}

    public function title(): string
    {
        return 'Applications';
    }

    public function headings(): array
    {
        return ['ID', 'Farmer', 'Barangay', 'Program', 'Benefit', 'Status', 'Applied At', 'Reviewed At', 'Remarks'];
    }

    public function collection(): Collection
    {
        return $this->report->applications->map(fn ($application) => [
            $application->id,
            $application->farmer?->full_name,
            $application->farmer?->barangay,
            $application->program?->name,
            $application->benefit?->name ?? 'Program-level benefit',
            ucfirst($application->status),
            $application->applied_at?->format('Y-m-d H:i:s'),
            $application->reviewed_at?->format('Y-m-d H:i:s'),
            $application->remarks,
        ]);
    }
}
