<?php

namespace App\Exports\Sheets;

use App\Services\ReportData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class SummarySheet implements FromCollection, WithTitle
{
    public function __construct(private ReportData $report) {}

    public function title(): string
    {
        return 'Summary';
    }

    public function collection(): Collection
    {
        return collect([
            ['FAMS SUMMARY REPORT'],
            ['Reporting period', $this->report->periodLabel()],
            ['Generated at', now()->format('Y-m-d H:i:s T')],
            ['Metric', 'Total'],
        ])->concat(collect($this->report->totals())->map(fn ($value, $metric) => [$metric, $value])->values());
    }
}
