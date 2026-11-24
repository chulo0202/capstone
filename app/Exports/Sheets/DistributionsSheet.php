<?php

namespace App\Exports\Sheets;

use App\Models\Distribution;
use App\Services\ReportData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class DistributionsSheet implements FromCollection, WithTitle
{
    public function __construct(private ReportData $report) {}

    public function title(): string
    {
        return 'Distributions';
    }

    public function collection(): Collection
    {
        $rows = collect([
            [
                'ID',
                'Farmer ID',
                'Farmer Name',
                'Program ID',
                'Program Name',
                'Benefit ID',
                'Benefit Name',
                'Application ID',
                'Batch',
                'Released By',
                'Distributed At',
                'Notes',
            ],
        ]);

        $this->report->distributions
            ->each(function (Distribution $distribution) use ($rows) {
                $rows->push([
                    $distribution->id,
                    $distribution->farmer_id,
                    $distribution->farmer?->full_name,
                    $distribution->program_id,
                    $distribution->program?->name,
                    $distribution->program_benefit_id,
                    $distribution->benefit?->name,
                    $distribution->application_id,
                    $distribution->batch?->name ?? 'Direct release',
                    $distribution->releasedBy?->name,
                    $distribution->distributed_at?->format('Y-m-d H:i:s'),
                    $distribution->notes,
                ]);
            });

        return $rows;
    }
}
