@extends('layouts.admin')

@section('page-title', $batch ? 'Edit Distribution Schedule' : 'Create Distribution')

@section('main')
@if (!$batch)
    <form method="GET" action="{{ route('admin.distribution-batches.create') }}" class="card card-body mb-3" id="distribution-selection-form"
        data-program="{{ $program?->id }}" data-benefit="{{ $benefit?->id }}">
        <div class="row g-3 align-items-end">
            <div class="col-md">
                <label class="form-label" for="distribution-program">Program</label>
                <select class="form-select" name="program" id="distribution-program" required>
                    <option value="">Select a program</option>
                    @foreach ($programs as $option)
                        <option value="{{ $option->id }}" @selected($program?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md">
                <label class="form-label" for="distribution-benefit">Specific Benefit</label>
                <select class="form-select" name="benefit" id="distribution-benefit" @disabled(!$program)>
                    <option value="">{{ $program ? 'Select a benefit' : 'Select a program first' }}</option>
                    @foreach ($program?->activeBenefits ?? [] as $option)
                        <option value="{{ $option->id }}" @selected($benefit?->id === $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <noscript><div class="col-md-auto"><button class="btn btn-outline-secondary">Load selection</button></div></noscript>
        </div>
    </form>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
@endif

@if ($batch || ($benefit && $applications->isNotEmpty()))
    <form class="card card-body" method="POST" id="distribution-batch-form"
        action="{{ $batch ? route('admin.distribution-batches.update', $batch) : route('admin.distribution-batches.store') }}">
        @csrf
        @if ($batch)
            @method('PATCH')
        @else
            <input type="hidden" name="program_id" value="{{ $program->id }}">
            <input type="hidden" name="program_benefit_id" value="{{ $benefit->id }}">
        @endif

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

        @unless ($batch)
            <fieldset class="mb-3">
                <legend class="h6">Approved beneficiaries</legend>
                @error('application_ids')<p class="text-danger">{{ $message }}</p>@enderror
                @foreach ($applications as $application)
                    <label class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="application_ids[]" value="{{ $application->id }}"
                            @checked(in_array($application->id, (array) old('application_ids', [])))>
                        {{ $application->farmer->full_name }} · Application #{{ $application->id }}
                    </label>
                @endforeach
            </fieldset>
        @endunless

        <div><button class="btn btn-fams">{{ $batch ? 'Save Schedule' : 'Create Draft' }}</button></div>
    </form>
@elseif ($benefit)
    <div class="alert alert-info">No available approved applications for this benefit.</div>
@endif
<script>
    const selectionForm = document.getElementById('distribution-selection-form');

    if (selectionForm) {
        const program = selectionForm.elements.program;
        const benefit = selectionForm.elements.benefit;

        program.addEventListener('change', () => {
            benefit.value = '';
            benefit.disabled = true;
            // Submit even if the admin clears the program to reset the page.
            selectionForm.submit();
        });

        benefit.addEventListener('change', () => selectionForm.requestSubmit());

        // Browsers can restore select values after navigating back.
        window.addEventListener('pageshow', () => {
            program.value = selectionForm.dataset.program;
            benefit.value = selectionForm.dataset.benefit;
            benefit.disabled = !program.value;
        });
    }

    const scheduleForm = document.getElementById('distribution-batch-form');

    if (scheduleForm) {
        const start = scheduleForm.elements.start_time;
        const end = scheduleForm.elements.end_time;
        const beneficiaries = [...scheduleForm.querySelectorAll('[name="application_ids[]"]')];

        function validateTimes() {
            const invalid = start.value && end.value && start.value >= end.value;
            end.setCustomValidity(invalid ? 'End time must be later than start time.' : '');
        }

        function validateBeneficiaries() {
            if (beneficiaries.length) {
                beneficiaries[0].setCustomValidity(beneficiaries.some(field => field.checked)
                    ? '' : 'Please select at least one approved beneficiary.');
            }
        }

        start.addEventListener('input', validateTimes);
        end.addEventListener('input', validateTimes);
        beneficiaries.forEach(field => field.addEventListener('change', validateBeneficiaries));
        validateTimes();
        validateBeneficiaries();
    }
</script>
@endsection
