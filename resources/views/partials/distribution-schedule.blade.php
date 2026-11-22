<dl class="row g-3 mb-0">
    @foreach ([
        'Program' => $batch->program->name,
        'Specific Benefit' => $batch->benefit->name,
        'Distribution Date' => $batch->scheduled_date->format('l, M j, Y'),
        'Release Window' => substr($batch->start_time, 0, 5).' – '.substr($batch->end_time, 0, 5).' ('.config('app.timezone').')',
        'Location' => $batch->location,
        'Instructions' => $batch->instructions,
    ] as $label => $value)
        @if ($value !== null && $value !== '')
            <div class="{{ $label === 'Instructions' ? 'col-12' : 'col-sm-6' }}">
                <div class="bg-light rounded-3 px-3 py-2 h-100">
                    <dt class="small text-muted">{{ $label }}</dt>
                    <dd class="fw-semibold text-break mb-0" style="white-space: pre-wrap">{{ $value }}</dd>
                </div>
            </div>
        @endif
    @endforeach
</dl>
