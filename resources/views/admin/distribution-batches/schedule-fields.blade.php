<div class="row g-3 mb-3">
    @foreach ([
        'name' => ['Distribution Name', 'text', $batch?->name, 150],
        'scheduled_date' => ['Distribution Date', 'date', $batch?->scheduled_date?->toDateString(), null],
        'start_time' => ['Start Time', 'time', substr($batch?->start_time ?? '', 0, 5), null],
        'end_time' => ['End Time', 'time', substr($batch?->end_time ?? '', 0, 5), null],
        'location' => ['Distribution Location', 'text', $batch?->location, 255],
    ] as $field => [$label, $type, $default, $length])
        <div class="col-md-6">
            <label for="batch-{{ $field }}" class="form-label">{{ $label }}</label>
            <input id="batch-{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $default) }}"
                @if ($length) maxlength="{{ $length }}" @endif
                @if ($type === 'date') min="{{ today()->toDateString() }}" @endif required>
            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endforeach
    <div class="col-12">
        <label class="form-label" for="batch-instructions">Additional Instructions</label>
        <textarea class="form-control @error('instructions') is-invalid @enderror" id="batch-instructions"
            name="instructions" maxlength="2000" rows="3">{{ old('instructions', $batch?->instructions) }}</textarea>
        @error('instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
