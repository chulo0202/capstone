<?php

namespace App\Exports;

use App\Exports\Sheets\ApplicationsSheet;
use App\Exports\Sheets\DistributionsSheet;
use App\Exports\Sheets\FarmersSheet;
use App\Exports\Sheets\ProgramsSheet;
use App\Exports\Sheets\SummarySheet;
use App\Services\ReportData;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class ReportsExport extends DefaultValueBinder implements WithMultipleSheets, WithCustomValueBinder
{
    public function __construct(private ReportData $report) {}

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function sheets(): array
    {
        return [
            new SummarySheet($this->report),
            new FarmersSheet($this->report),
            new ProgramsSheet($this->report),
            new ApplicationsSheet($this->report),
            new DistributionsSheet($this->report),
        ];
    }
}