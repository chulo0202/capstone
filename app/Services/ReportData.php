<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use Illuminate\Support\Collection;

class ReportData
{
    public readonly Collection $farmers;

    public readonly Collection $programs;

    public readonly Collection $applications;

    public readonly Collection $distributions;

    public function __construct(public readonly ?ReportingPeriod $period)
    {
        $range = $period ? [$period->start, $period->end] : null;

        $this->farmers = Farmer::query()->when($range, fn ($query) => $query->whereBetween('created_at', $range))->orderBy('full_name')->get();
        $this->applications = Application::with(['farmer', 'program', 'benefit'])
            ->when($range, fn ($query) => $query->whereBetween('applied_at', $range))->latest('applied_at')->get();
        $this->distributions = Distribution::with(['farmer', 'program', 'benefit', 'releasedBy', 'batch'])
            ->when($range, fn ($query) => $query->whereBetween('distributed_at', $range))->latest('distributed_at')->get();

        $activity = $this->applications->pluck('program_id')->merge($this->distributions->pluck('program_id'))->unique();
        $this->programs = Program::with('activeBenefits.configurations')
            ->when($period, fn ($programs) => $programs->where(fn ($query) => $query
                ->whereIn('id', $activity)
                ->orWhere(fn ($scheduled) => $scheduled->where('start_at', '<=', $period->end)
                    ->where('end_at', '>=', $period->start))
                ->orWhereHas('activeBenefits.configurations', fn ($schedule) => $schedule
                    ->where('start_at', '<=', $period->end)->where('end_at', '>=', $period->start))
                ->orWhere(fn ($pcic) => $pcic->whereNull('archived_at')->where('name', Program::PCIC_NAME))))
            ->orderBy('name')->get();
    }

    public function periodLabel(): string
    {
        return $this->period?->label() ?? 'All time';
    }

    public function filename(): string
    {
        return 'fams-report-'.($this->period?->month
            ? $this->period->start->format('Y-m')
            : ($this->period?->academicYear ?? now()->format('YmdHis')));
    }

    public function totals(): array
    {
        $statuses = $this->applications->countBy('status');

        return [
            'Farmers registered' => $this->farmers->count(),
            'Programs covered' => $this->programs->count(),
            'Applications submitted' => $this->applications->count(),
            'Applications approved' => $statuses->get('approved', 0),
            'Applications pending' => $statuses->get('pending', 0),
            'Applications rejected' => $statuses->get('rejected', 0),
            'Benefits released' => $this->distributions->count(),
            'Farmers who received benefits' => $this->distributions->pluck('farmer_id')->unique()->count(),
        ];
    }
}
