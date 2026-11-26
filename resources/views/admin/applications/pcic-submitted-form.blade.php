<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PCIC Submitted Application Form</title>
    <style>
        body { font: 11px "DejaVu Sans", sans-serif; color: #18251e; }
        h1 { font-size: 22px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 18px 0 8px; }
        h3 { font-size: 12px; }
        p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 7px; border-bottom: 1px solid #dce6df; text-align: left; vertical-align: top; }
        th { width: 33%; background: #f1f6f2; }
        td, dd { overflow-wrap: break-word; white-space: pre-wrap; }
        dt { font-weight: bold; }
        dd { margin: 0 0 5px; }
    </style>
</head>
<body>
    @php
        $details = $application->detail?->details ?? [];
        $incident = $application->pcicDamageIncident;
        $benefitName = $application->benefit?->name
            ?? ($application->detail?->form_type === 'pcic_claim' ? 'Claim for Indemnity' : 'New Application');
    @endphp

    <h1>PCIC Crop Insurance</h1>
    <p><strong>{{ $benefitName }}</strong> · FAMS application #{{ $application->id }}</p>
    <p>Farmer: {{ $application->farmer?->full_name ?? 'Not available' }}</p>
    <p>Submitted: {{ $application->applied_at?->format('F d, Y h:i A') ?? 'Not available' }}</p>

    <h2>Submitted Application Form</h2>
    @if ($details)
        <table>
            @foreach ($details as $key => $value)
                @continue(in_array($key, ['damage_photo_path', 'damage_photo_paths', 'lots'], true))
                <tr>
                    <th scope="row">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                    <td>@if (is_array($value)){{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}@elseif (is_bool($value)){{ $value ? 'Yes' : 'No' }}@else{{ $value === null || $value === '' ? 'Not provided' : $value }}@endif</td>
                </tr>
            @endforeach
        </table>
        @includeWhen(!empty($details['lots']), 'admin.applications.insured-lots', ['lots' => $details['lots'] ?? []])
    @else
        <p>No additional form details were recorded.</p>
    @endif

    @if ($incident)
        <h2>Damage Evidence</h2>
        <p>PCIC Incident Reference: {{ $incident->incident_reference }}</p>
        @foreach ($incident->damagePhotoPaths() as $index => $path)
            <p><a href="{{ route('admin.pcic-incidents.damage-photo', ['incident' => $incident, 'photo' => $index + 1]) }}">View Damage Photo {{ $index + 1 }} in FAMS</a></p>
        @endforeach
    @endif
</body>
</html>
