@extends('layouts.admin')

@section('page-title', 'Create Distribution')

@section('main')
<style>
    #distribution-wizard[hidden], #distribution-wizard [hidden] { display: none !important; }
    .distribution-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .distribution-steps li { position: relative; text-align: center; }
    .distribution-steps li + li::before {
        content: ''; position: absolute; height: 2px; background: #dee2e6;
        top: 1rem; right: calc(50% + 1rem); width: calc(100% - 2rem);
    }
    .distribution-steps button { background: transparent; border: 0; color: #495057; font-size: .875rem; }
    .distribution-steps button:disabled { opacity: 1; }
    .distribution-steps .step-number {
        display: grid; place-items: center; margin: 0 auto .4rem;
        width: 2rem; height: 2rem; border-radius: 50%; background: #e9ecef;
    }
    .distribution-steps [aria-current="step"] { color: #146c43; font-weight: 700; }
    .distribution-steps [aria-current="step"] .step-number,
    .distribution-steps .is-complete .step-number { background: #198754; color: white; }
    .distribution-beneficiary:has(input:checked) { background: #e8f5ee; border-color: #198754 !important; }
    @media (max-width: 575.98px) {
        .distribution-steps button { font-size: .75rem; padding-inline: .125rem; }
    }
</style>

<noscript><div class="alert alert-warning">Enable JavaScript to use the distribution wizard.</div></noscript>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
@endif

<form class="card card-body" method="POST" id="distribution-wizard" hidden novalidate
    action="{{ route('admin.distribution-batches.store') }}"
    data-options-url="{{ route('admin.distribution-batches.create') }}">
    @csrf
    <nav aria-label="Distribution steps">
        <ol class="distribution-steps list-unstyled mb-4 pb-3 border-bottom">
            @foreach (['Program & Benefit', 'Beneficiaries', 'Schedule Details', 'Review'] as $label)
                <li><button type="button" data-step-link="{{ $loop->index }}" disabled>
                    <span class="step-number" aria-hidden="true">{{ $loop->iteration }}</span>{{ $label }}
                </button></li>
            @endforeach
        </ol>
    </nav>

    <section data-step aria-labelledby="distribution-program-heading">
        <h2 id="distribution-program-heading" class="h5 mb-3" tabindex="-1">Program &amp; Benefit</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="distribution-program">Program</label>
                <select class="form-select" name="program_id" id="distribution-program" required>
                    <option value="">Select a program</option>
                    @foreach ($programs as $option)
                        <option value="{{ $option->id }}" @selected($program?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="distribution-benefit">Specific Benefit</label>
                <select class="form-select" name="program_benefit_id" id="distribution-benefit" required @disabled(!$program)>
                    <option value="">{{ $program ? 'Select a benefit' : 'Select a program first' }}</option>
                    @foreach ($program?->activeBenefits ?? [] as $option)
                        <option value="{{ $option->id }}" @selected($benefit?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="text-danger mt-2 mb-0" data-load-error role="alert" hidden></p>
    </section>

    <section data-step aria-labelledby="distribution-beneficiaries-heading" hidden>
        <h2 id="distribution-beneficiaries-heading" class="h5 mb-3" tabindex="-1">Select Approved Beneficiaries</h2>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <label class="form-check mb-0">
                <input class="form-check-input" type="checkbox" data-select-all> Select all eligible beneficiaries
            </label>
            <span data-count aria-live="polite" class="fw-semibold"></span>
        </div>
        <div class="alert alert-info" data-empty hidden>No eligible approved beneficiaries are available for this benefit.</div>
        <p class="text-body-secondary small mb-2" data-selection-limit hidden>Select All includes up to 200 beneficiaries, with one application per farmer.</p>
        <div data-beneficiaries class="d-grid gap-2"></div>
        <p class="text-danger mt-2 mb-0" data-selection-error role="alert" hidden></p>
        <template data-beneficiary-template>
            <label class="distribution-beneficiary d-flex gap-3 align-items-center border rounded p-3">
                <input class="form-check-input mt-0 flex-shrink-0" type="checkbox" name="application_ids[]">
                <span class="flex-grow-1">
                    <strong class="d-block" data-farmer-name></strong>
                    <small class="text-body-secondary" data-farmer-reference></small>
                </span>
                <span class="badge text-bg-success">Approved</span>
            </label>
        </template>
    </section>

    <section data-step aria-labelledby="distribution-schedule-heading" hidden>
        <h2 id="distribution-schedule-heading" class="h5 mb-3" tabindex="-1">Distribution Schedule</h2>
        <p class="text-body-secondary small">Schedule times use {{ config('app.timezone') }}.</p>
        @include('admin.distribution-batches.schedule-fields', ['batch' => null])
        <p class="text-danger small" data-time-error role="alert" hidden></p>
    </section>

    <section data-step aria-labelledby="distribution-review-heading" hidden>
        <h2 id="distribution-review-heading" class="h5 mb-3" tabindex="-1">Review &amp; Create Draft</h2>
        <dl class="row mb-3">
            @foreach ([
                'program_id' => 'Program', 'program_benefit_id' => 'Specific Benefit', 'name' => 'Distribution Name',
                'scheduled_date' => 'Distribution Date', 'start_time' => 'Start Time', 'end_time' => 'End Time',
                'location' => 'Distribution Location', 'instructions' => 'Additional Instructions',
            ] as $field => $label)
                <dt class="col-sm-4">{{ $label }}</dt>
                <dd class="col-sm-8 text-break" data-review="{{ $field }}" style="white-space: pre-wrap"></dd>
            @endforeach
        </dl>
        <h3 class="h6" data-review-count></h3>
        <ul data-review-beneficiaries></ul>
        <p class="alert alert-info mb-0">Creating a draft does not publish the schedule or release assistance.</p>
    </section>

    <div class="d-flex gap-2 justify-content-end mt-4">
        <button type="button" class="btn btn-outline-secondary" data-back hidden>Back</button>
        <button type="button" class="btn btn-fams" data-next>Next</button>
        <button type="submit" class="btn btn-fams" data-create hidden>Create Draft</button>
    </div>
</form>

@include('admin.distribution-batches.wizard-script')
@endsection
