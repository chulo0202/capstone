<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ReportingPeriod
{
    public readonly string $academicYear;

    public readonly ?string $month;

    public readonly CarbonImmutable $start;

    public readonly CarbonImmutable $end;

    public readonly array $months;

    public function __construct(Request $request)
    {
        $now = now();
        $currentYear = $now->month >= 6 ? $now->year : $now->year - 1;
        $year = $request->query('academic_year');

        if (is_string($year) && preg_match('/^([1-9]\d{3})-(\d{4})$/D', $year, $years)
            && (int) $years[2] === (int) $years[1] + 1) {
            $currentYear = (int) $years[1];
        }

        $this->academicYear = $currentYear.'-'.($currentYear + 1);
        $start = CarbonImmutable::create($currentYear, 6, 1)->startOfDay();
        $months = [];

        for ($offset = 0; $offset < 12; $offset++) {
            $date = $start->addMonths($offset);
            $months[$date->format('m')] = $date->format('F Y');
        }

        $this->months = $months;
        $month = $request->query('month');
        $this->month = is_string($month) && isset($months[$month]) ? $month : null;
        $this->start = $this->month
            ? $start->addMonths(((int) $this->month + 6) % 12)
            : $start;
        $this->end = $this->month ? $this->start->endOfMonth() : $start->addYear()->subDay()->endOfDay();
    }

    public function filters(): array
    {
        return array_filter(['academic_year' => $this->academicYear, 'month' => $this->month]);
    }

    public function label(): string
    {
        return $this->start->format('M j, Y').' – '.$this->end->format('M j, Y');
    }
}
