<?php

namespace App\Exports\Sheets;

use App\Models\Program;
use App\Services\ReportData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProgramsSheet implements FromCollection, WithTitle
{
    public function __construct(private ReportData $report) {}

    public function title(): string
    {
        return 'Programs';
    }

    public function collection(): Collection
    {
        $rows = collect([
            [
                'ID',
                'Program Name',
                'Description',
                'Benefits',
                'Eligibility Criteria',
                'Status',
                'Start Date',
                'End Date',
            ],
        ]);

        $this->report->programs
            ->each(function (Program $program) use ($rows) {
                $rows->push([
                    $program->id,
                    $program->name,
                    $program->description,
                    $program->activeBenefits->pluck('name')->implode(', '),
                    $program->eligibility_criteria,
                    $program->archived_at ? 'Archived' : ($program->isPcicCropInsurance() ? 'Open year-round' : ($program->is_active ? 'Active' : 'Inactive')),
                    $program->isPcicCropInsurance() ? null : $program->start_at?->format('Y-m-d H:i:s'),
                    $program->isPcicCropInsurance() ? null : $program->end_at?->format('Y-m-d H:i:s'),
                ]);
            });

        return $rows;
    }
}