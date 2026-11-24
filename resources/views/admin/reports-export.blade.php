<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>FAMS Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #172638; font-size: 10px; }
        h1 { color: #173d2d; font-size: 22px; margin: 0 0 4px; }
        h2 { color: #173d2d; font-size: 14px; margin: 16px 0 6px; }
        p { margin: 3px 0; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        thead { display: table-header-group; }
        th, td { padding: 5px 6px; border: 1px solid #dce6df; text-align: left; overflow-wrap: break-word; }
        th { background: #e8f3ec; color: #173d2d; }
        tbody tr:nth-child(even) { background: #f7faf8; }
        .section { page-break-before: always; }
    </style>
</head>
<body>
<h1>FAMS Analytics Report</h1>
<p><strong>Reporting period</strong> {{ $report->periodLabel() }}</p>
<p><strong>Generated</strong> {{ now()->format('M j, Y, g:i A T') }}</p>

<h2>Summary</h2>
<table>
    <thead><tr><th>Metric</th><th>Total</th></tr></thead>
    <tbody>
        @foreach ($report->totals() as $metric => $total)
            <tr><td>{{ $metric }}</td><td>{{ $total }}</td></tr>
        @endforeach
    </tbody>
</table>

<h2>Programs covered</h2>
<table>
    <thead><tr><th>Program</th><th>Current benefits</th><th>Program schedule</th></tr></thead>
    <tbody>
        @forelse ($report->programs as $program)
            <tr>
                <td>{{ $program->name }} @if ($program->archived_at) (Archived) @endif</td>
                <td>{{ $program->activeBenefits->pluck('name')->implode(', ') ?: '—' }}</td>
                <td>{{ $program->isPcicCropInsurance() && !$program->archived_at
                    ? 'Open year-round'
                    : ($program->start_at?->toDateString() ?? '—').' / '.($program->end_at?->toDateString() ?? '—') }}</td>
            </tr>
        @empty
            <tr><td colspan="3">No programs for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<h2 class="section">Farmer registrations</h2>
<table>
    <thead><tr><th>Farmer</th><th>Barangay</th><th>Crop</th><th>Land size (ha)</th><th>Association member</th><th>4Ps member</th><th>Registered</th></tr></thead>
    <tbody>
        @forelse ($report->farmers as $farmer)
            <tr>
                <td>{{ $farmer->full_name }}</td><td>{{ $farmer->barangay }}</td><td>{{ $farmer->crop_type }}</td>
                <td>{{ $farmer->land_size }}</td><td>{{ $farmer->association_membership ? 'Yes' : 'No' }}</td>
                <td>{{ $farmer->four_ps_membership ? 'Yes' : 'No' }}</td><td>{{ $farmer->created_at?->toDateString() }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No farmer registrations for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<h2 class="section">Applications</h2>
<table>
    <thead><tr><th>Reference</th><th>Farmer</th><th>Program and benefit</th><th>Status</th><th>Applied</th><th>Reviewed</th><th>Remarks</th></tr></thead>
    <tbody>
        @forelse ($report->applications as $application)
            <tr>
                <td>#{{ $application->id }}</td><td>{{ $application->farmer?->full_name }}</td>
                <td>{{ $application->program?->name }}<br>{{ $application->benefit?->name ?? 'Program-level benefit' }}</td>
                <td>{{ ucfirst($application->status) }}</td><td>{{ $application->applied_at?->format('M j, Y g:i A') }}</td>
                <td>{{ $application->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</td><td>{{ $application->remarks ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No applications for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<h2 class="section">Benefit releases</h2>
<table>
    <thead><tr><th>Farmer</th><th>Program and benefit</th><th>Application</th><th>Distribution</th><th>Released at</th><th>Released by</th><th>Notes</th></tr></thead>
    <tbody>
        @forelse ($report->distributions as $distribution)
            <tr>
                <td>{{ $distribution->farmer?->full_name }}</td>
                <td>{{ $distribution->program?->name }}<br>{{ $distribution->benefit?->name ?? 'Program-level benefit' }}</td>
                <td>{{ $distribution->application_id ? '#'.$distribution->application_id : '—' }}</td>
                <td>{{ $distribution->batch?->name ?? 'Direct release' }}</td>
                <td>{{ $distribution->distributed_at?->format('M j, Y g:i A') }}</td>
                <td>{{ $distribution->releasedBy?->name ?? '—' }}</td><td>{{ $distribution->notes ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No benefit releases for this period.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
