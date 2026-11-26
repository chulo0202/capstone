@extends('layouts.admin')

@section('page-title', 'Create Training Schedule')
@section('page-subtitle', 'Training and Technology Demonstrations')

@section('main')
<a href="{{ route('admin.schedules.options') }}" class="btn btn-fams-back mb-3">Back to Create Schedule</a>
@php($fields = [
    ['title', 'Training Title', 'text', 'col-md-6', 150, 'Use the name farmers will see, such as Rice Planting Demonstration.'],
    ['topic', 'Topic', 'text', 'col-md-6', 150, 'Name the main skill or subject, such as Proper Rice Seedling Spacing.'],
    ['objectives', 'Description and Objectives', 'textarea', 'col-12', 3000, 'Describe the activities and what farmers should be able to do afterward. For example, demonstrate how to mark rows and space seedlings.'],
    ['scheduled_date', 'Date', 'date', 'col-md-4', null, 'Choose today or a future date.'],
    ['start_time', 'Start Time', 'time', 'col-md-4', null, 'Enter the session start time in Philippine time.'],
    ['end_time', 'End Time', 'time', 'col-md-4', null, 'Enter the end time in Philippine time. It must be later than the start time.'],
    ['venue', 'Venue', 'text', 'col-md-6', 255, 'Include the venue name and barangay or a nearby landmark so farmers can find it.'],
    ['trainer', 'Trainer / Resource Person', 'text', 'col-md-6', 255, 'Enter the full name of the trainer or the name of the office leading the session.'],
    ['slots', 'Number of Slots', 'number', 'col-md-4', null, 'Enter the total capacity. If you selected 5 farmers, enter at least 5 slots.'],
    ['instructions', 'Instructions (optional)', 'textarea', 'col-12', 3000, 'Add anything farmers should bring or do before arriving, such as bringing boots and arriving 15 minutes early.'],
])
<form id="training-stepper" method="POST" action="{{ route('admin.trainings.store') }}" class="card shadow-sm">
    @csrf
    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif
        <ol class="list-unstyled d-flex justify-content-around gap-2 text-center border-bottom pb-3 mb-4">
            @foreach (['Select Farmers', 'Training Details', 'Review & Save'] as $label)
                <li class="flex-fill" data-step-label="{{ $loop->index }}">
                    <span class="badge rounded-pill text-bg-secondary">{{ $loop->iteration }}</span>
                    <div class="small mt-1">{{ $label }}</div>
                </li>
            @endforeach
        </ol>
        <section data-step="0" aria-labelledby="select-farmer-heading">
            <h2 id="select-farmer-heading" class="h5 mb-3" tabindex="-1">Select Approved Farmers</h2>
            <fieldset aria-describedby="farmer-selection-help farmer-selection-error">
                <legend class="form-label fs-6">Choose one or more farmers</legend>
                <p id="farmer-selection-help" class="text-muted small">This list shows active farmers with an approved training application. Check each farmer you want to include, then select Next.</p>
                <div class="row g-2">
                    @foreach ($farmers as $farmer)
                        <div class="col-12 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="farmer_ids[]" value="{{ $farmer->id }}" id="training-farmer-{{ $farmer->id }}" @checked(in_array($farmer->id, (array) old('farmer_ids', [])))>
                                <label class="form-check-label" for="training-farmer-{{ $farmer->id }}">{{ $farmer->full_name }}{{ $farmer->barangay ? ' · '.$farmer->barangay : '' }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p id="farmer-selection-error" class="text-danger mt-2 mb-0" role="alert">{{ $errors->first('farmer_ids') ?: $errors->first('farmer_ids.*') }}</p>
            </fieldset>
            @if ($farmers->isEmpty())
                <p class="text-muted mt-2">No active farmers with an approved training application are available. Review training applications before creating a schedule.</p>
            @endif
        </section>
        <section data-step="1" aria-labelledby="training-details-heading">
            <h2 id="training-details-heading" class="h5 mb-3" tabindex="-1">Training Details</h2>
            <p class="text-muted small">Fields marked with an asterisk (*) are required. Select Review when the schedule is complete.</p>
            <div class="row g-3">
                @foreach ($fields as [$field, $label, $input, $column, $length, $hint])
                    <div class="{{ $column }}">
                        <label class="form-label" for="{{ $field }}">{{ $label }} @if ($field !== 'instructions')<span class="text-danger" aria-hidden="true">*</span>@endif</label>
                        @if ($input === 'textarea')
                            <textarea class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" aria-describedby="{{ $field }}-help @error($field) {{ $field }}-error @enderror" maxlength="{{ $length }}" rows="3" @required($field !== 'instructions')>{{ old($field) }}</textarea>
                        @else
                            <input class="form-control @error($field) is-invalid @enderror" type="{{ $input }}" id="{{ $field }}" name="{{ $field }}" aria-describedby="{{ $field }}-help @error($field) {{ $field }}-error @enderror" value="{{ old($field) }}" @if($length) maxlength="{{ $length }}" @endif @if($field === 'slots') min="1" max="10000" @endif @if($input === 'date') min="{{ today('Asia/Manila')->toDateString() }}" @endif required>
                        @endif
                        @error($field)<div id="{{ $field }}-error" class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="{{ $field }}-help" class="form-text">{{ $hint }}</div>
                    </div>
                @endforeach
            </div>
        </section>
        <section data-step="2" aria-labelledby="review-training-heading" hidden>
            <h2 id="review-training-heading" class="h5 mb-3" tabindex="-1">Review &amp; Save</h2>
            <p class="text-muted small">Check the farmer list, date, times, and venue. Select Back to make a correction.</p>
            <dl class="row mb-0 text-break">
                <dt class="col-sm-4">Farmers</dt><dd class="col-sm-8" data-review-farmers style="white-space: pre-line"></dd>
                @foreach ($fields as [$field, $label])
                    <dt class="col-sm-4">{{ $label }}</dt><dd class="col-sm-8" data-review="{{ $field }}" style="white-space: pre-wrap"></dd>
                @endforeach
            </dl>
            <p class="small text-muted mt-3 mb-0">Save Training Draft keeps the schedule unpublished and records the selected farmers as pending registrations. Once you publish, you can confirm their slots from the schedule page.</p>
        </section>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary" data-back hidden>Back</button>
        <button type="button" class="btn btn-fams" data-next hidden>Next</button>
        <button type="submit" class="btn btn-fams" data-save>Save Training Draft</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('training-stepper');
    const panels = Array.from(form.querySelectorAll('[data-step]'));
    const labels = form.querySelectorAll('[data-step-label]');
    const back = form.querySelector('[data-back]');
    const next = form.querySelector('[data-next]');
    const save = form.querySelector('[data-save]');
    const farmerChoices = Array.from(form.querySelectorAll('[name="farmer_ids[]"]'));
    const farmerError = document.getElementById('farmer-selection-error');
    const selectedFarmers = () => farmerChoices.filter(choice => choice.checked);
    let step = 0;

    function showStep(index, focus = true) {
        step = index;
        panels.forEach((panel, position) => {
            const current = index === position;
            panel.hidden = !current;
            labels[position].toggleAttribute('aria-current', current);
            if (current) labels[position].setAttribute('aria-current', 'step');
            const badge = labels[position].querySelector('.badge');
            badge.classList.toggle('text-bg-success', current);
            badge.classList.toggle('text-bg-secondary', !current);
        });
        back.hidden = index === 0;
        next.hidden = index === 2;
        next.disabled = index === 0 && farmerChoices.length === 0;
        next.textContent = index === 1 ? 'Review' : 'Next';
        save.hidden = index !== 2;

        if (index === 2) {
            form.querySelector('[data-review-farmers]').textContent =
                selectedFarmers().map(choice => choice.labels[0].textContent.trim()).join('\n');
            form.querySelectorAll('[data-review]').forEach(item => {
                const field = form.elements[item.dataset.review];
                item.textContent = field.value || '—';
            });
        }

        if (focus) panels[index].querySelector('h2').focus();
    }

    function advance() {
        if (step === 0 && selectedFarmers().length === 0) {
            farmerError.textContent = 'Select at least one approved farmer.';
            farmerChoices[0]?.focus();
            return;
        }

        farmerError.textContent = '';
        for (const field of panels[step].querySelectorAll('input, select, textarea')) {
            if (!field.reportValidity()) return;
        }

        if (step === 1 && form.elements.end_time.value <= form.elements.start_time.value) {
            const endTime = form.elements.end_time;
            endTime.setCustomValidity('End time must be after start time.');
            endTime.reportValidity();
            endTime.setCustomValidity('');
            return;
        }

        if (step === 1 && Number(form.elements.slots.value) < selectedFarmers().length) {
            const slots = form.elements.slots;
            slots.setCustomValidity('Enter enough slots for the selected farmers.');
            slots.reportValidity();
            slots.setCustomValidity('');
            return;
        }

        showStep(step + 1);
    }

    form.noValidate = true;
    next.addEventListener('click', advance);
    back.addEventListener('click', () => showStep(step - 1));
    form.addEventListener('submit', event => {
        if (step !== 2) {
            event.preventDefault();
            advance();
        }
    });

    showStep(@json($errors->any() && !$errors->has('farmer_ids') && !$errors->has('farmer_ids.*') && old('farmer_ids') ? 1 : 0), false);
})();
</script>
@endpush
