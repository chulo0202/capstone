@extends('layouts.admin')

@section('page-title', 'Program')

@section('main')

<div class="row justify-content-center">
    <div class="col-lg-9">

        <div class="card border-0 shadow-sm">

            {{-- ============================================================
                 HEADER
                 ============================================================ --}}
            <div class="card-header bg-white py-3">

                <h5 class="mb-1 fw-bold">
                    Program
                </h5>

                <small class="text-muted">
                    Configure the program, benefit, eligibility requirements,
                    and schedule.
                </small>

            </div>


            <div class="card-body p-4">

                @if($isEdit ?? false)
                    @php
                        $globalRule = $program->eligibilityRule;
                        $newBenefitIndex = $program->activeBenefits->count();
                        $cropTypes = ['Rice', 'Corn', 'Vegetables', 'Fruits', 'Coconut', 'Other'];
                    @endphp

                    <form
                        method="POST"
                        action="{{ route('admin.programs.update', $program) }}"
                        id="programForm"
                    >
                        @csrf
                        @method('PUT')

                        @if($errors->any())
                            <div
                                class="alert alert-danger"
                            >
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h5 class="fw-bold mb-4">Program Details</h5>

                        <div class="mb-3">
                            <label
                                for="name"
                                class="form-label fw-semibold"
                            >
                                Program Name
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                value="{{ old('name', $program->name) }}"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea
                                name="description"
                                id="description"
                                class="form-control"
                                rows="4"
                                required
                            >{{ old('description', $program->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="eligibility_criteria" class="form-label fw-semibold">Eligibility Criteria</label>
                            <textarea
                                name="eligibility_criteria"
                                id="eligibility_criteria"
                                class="form-control"
                                rows="3"
                            >{{ old('eligibility_criteria', $program->eligibility_criteria) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_at" class="form-label fw-semibold">Start Date &amp; Time</label>
                                <input
                                    type="datetime-local"
                                    name="start_at"
                                    id="start_at"
                                    class="form-control"
                                    value="{{ old('start_at', $program->start_at?->format('Y-m-d\TH:i')) }}"
                                >
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_at" class="form-label fw-semibold">End Date &amp; Time</label>
                                <input
                                    type="datetime-local"
                                    name="end_at"
                                    id="end_at"
                                    class="form-control"
                                    value="{{ old('end_at', $program->end_at?->format('Y-m-d\TH:i')) }}"
                                >
                            </div>
                        </div>

                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check mb-4">
                            <input
                                type="checkbox"
                                name="is_active"
                                id="is_active"
                                value="1"
                                class="form-check-input"
                                @checked(old('is_active', $program->is_active))
                            >
                            <label for="is_active" class="form-check-label">Program is active</label>
                        </div>

                        <hr>
                        <h5 class="fw-bold mb-3">Benefits</h5>
                        <p class="text-muted">Existing benefit IDs are preserved. Select Archive only to explicitly remove a benefit from active use.</p>
                        <div class="alert alert-info">
                            Program-wide rules apply to every benefit. Benefit-specific rules are combined with them. New benefits remain unavailable until eligibility is explicitly configured.
                        </div>

                        @foreach($program->activeBenefits as $index => $benefit)
                            @php
                                $benefitRule = $benefit->eligibilityRules->first();
                                $rulePrefix = "benefit_rules.{$index}";
                            @endphp
                            <div class="card mb-3">
                                <div class="card-body">
                                    <input type="hidden" name="benefits[{{ $index }}][id]" value="{{ $benefit->id }}">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Benefit #{{ $benefit->id }} Name</label>
                                        <input
                                            type="text"
                                            name="benefits[{{ $index }}][name]"
                                            class="form-control"
                                            value="{{ old("benefits.{$index}.name", $benefit->name) }}"
                                            required
                                        >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Description</label>
                                        <textarea
                                            name="benefits[{{ $index }}][description]"
                                            class="form-control"
                                            rows="2"
                                        >{{ old("benefits.{$index}.description", $benefit->description) }}</textarea>
                                    </div>
                                    @if(!$benefit->eligibility_configured_at)
                                        <input type="hidden" name="benefits[{{ $index }}][eligibility_configured]" value="0">
                                        <div class="form-check mb-3">
                                            <input
                                                type="checkbox"
                                                name="benefits[{{ $index }}][eligibility_configured]"
                                                value="1"
                                                class="form-check-input"
                                                id="configure_benefit_{{ $benefit->id }}"
                                                @checked(old("benefits.{$index}.eligibility_configured"))
                                            >
                                            <label class="form-check-label" for="configure_benefit_{{ $benefit->id }}">
                                                Confirm eligibility configuration (including no benefit-specific restrictions)
                                            </label>
                                        </div>
                                    @else
                                        <div class="small text-success mb-3">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Eligibility configured
                                        </div>
                                    @endif
                                    <input type="hidden" name="benefits[{{ $index }}][remove]" value="0">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            name="benefits[{{ $index }}][remove]"
                                            value="1"
                                            class="form-check-input"
                                            id="remove_benefit_{{ $benefit->id }}"
                                            @checked(old("benefits.{$index}.remove"))
                                        >
                                        <label class="form-check-label text-danger" for="remove_benefit_{{ $benefit->id }}">
                                            Archive this benefit
                                        </label>
                                    </div>

                                    <hr>
                                    <h6 class="fw-semibold">
                                        Benefit-specific eligibility
                                    </h6>
                                    <input
                                        type="hidden"
                                        name="benefit_rules[{{ $index }}][program_benefit_id]"
                                        value="{{ $benefit->id }}"
                                    >
                                    <div class="mb-3">
                                        <label class="form-label">
                                            Allowed Crop Types
                                        </label>
                                        @php
                                            $selectedBenefitCrops = old("{$rulePrefix}.crop_types", $benefitRule?->crop_types ?? []);
                                        @endphp
                                        <input
                                            type="hidden"
                                            name="benefit_rules[{{ $index }}][crop_types][]"
                                            value=""
                                        >
                                        <select
                                            name="benefit_rules[{{ $index }}][crop_types][]"
                                            class="form-select"
                                            multiple
                                        >
                                            @foreach($cropTypes as $cropType)
                                                <option
                                                    value="{{ $cropType }}"
                                                    @selected(is_array($selectedBenefitCrops) && in_array($cropType, $selectedBenefitCrops, true))
                                                >
                                                    {{ $cropType }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">
                                            Leave unselected for no crop restriction.
                                        </small>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Minimum Land Size (hectares)
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="benefit_rules[{{ $index }}][min_land_size]"
                                                class="form-control"
                                                value="{{ old("{$rulePrefix}.min_land_size", $benefitRule?->min_land_size) }}"
                                            >
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Maximum Land Size (hectares)
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="benefit_rules[{{ $index }}][max_land_size]"
                                                class="form-control"
                                                value="{{ old("{$rulePrefix}.max_land_size", $benefitRule?->max_land_size) }}"
                                            >
                                        </div>
                                    </div>
                                    @foreach(['requires_rsbsa' => 'RSBSA Registration', 'requires_association' => 'Farmers Association Membership', 'requires_4ps' => '4Ps Beneficiary'] as $field => $label)
                                        <input
                                            type="hidden"
                                            name="benefit_rules[{{ $index }}][{{ $field }}]"
                                            value="0"
                                        >
                                        <div class="form-check mb-2">
                                            <input
                                                type="checkbox"
                                                name="benefit_rules[{{ $index }}][{{ $field }}]"
                                                value="1"
                                                class="form-check-input"
                                                id="{{ $field }}_{{ $benefit->id }}"
                                                @checked(old("{$rulePrefix}.{$field}", $benefitRule?->{$field}))
                                            >
                                            <label
                                                class="form-check-label"
                                                for="{{ $field }}_{{ $benefit->id }}"
                                            >
                                                {{ $label }} Required
                                            </label>
                                        </div>
                                    @endforeach
                                    <input
                                        type="hidden"
                                        name="benefit_rules[{{ $index }}][clear]"
                                        value="0"
                                    >
                                    <div class="form-check mt-3">
                                        <input
                                            type="checkbox"
                                            name="benefit_rules[{{ $index }}][clear]"
                                            value="1"
                                            class="form-check-input"
                                            id="clear_rule_{{ $benefit->id }}"
                                            @checked(old("{$rulePrefix}.clear"))
                                        >
                                        <label
                                            class="form-check-label text-danger"
                                            for="clear_rule_{{ $benefit->id }}"
                                        >
                                            Explicitly clear this benefit's eligibility rule
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @foreach($program->benefits->whereNotNull('archived_at') as $benefit)
                            <div
                                class="alert alert-secondary"
                            >
                                <strong>Archived benefit #{{ $benefit->id }}:</strong> {{ $benefit->name }}
                                <span class="ms-2">This benefit is retained for historical records.</span>
                            </div>
                        @endforeach

                        <div id="newBenefits">
                            @foreach(old('benefits', []) as $oldBenefit)
                                @if(is_array($oldBenefit) && empty($oldBenefit['id']))
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="fw-semibold">New benefit</h6>
                                            <p class="text-muted">Eligibility may be configured with no benefit-specific restrictions now, or later in another edit.</p>
                                            <div class="mb-3">
                                                <label class="form-label">Benefit Name</label>
                                                <input
                                                    type="text"
                                                    name="benefits[{{ $newBenefitIndex }}][name]"
                                                    class="form-control"
                                                    value="{{ $oldBenefit['name'] ?? '' }}"
                                                    required
                                                >
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea
                                                    name="benefits[{{ $newBenefitIndex }}][description]"
                                                    class="form-control"
                                                    rows="2"
                                                >{{ $oldBenefit['description'] ?? '' }}</textarea>
                                            </div>
                                                <input
                                                    type="hidden"
                                                    name="benefits[{{ $newBenefitIndex }}][eligibility_configured]"
                                                    value="0"
                                                >
                                                <div class="form-check mb-3">
                                                    <input
                                                        type="checkbox"
                                                        name="benefits[{{ $newBenefitIndex }}][eligibility_configured]"
                                                        value="1"
                                                        class="form-check-input"
                                                        @checked(!empty($oldBenefit['eligibility_configured']))
                                                    >
                                                    <label class="form-check-label">
                                                        Confirm eligibility is configured with no benefit-specific restrictions
                                                    </label>
                                                </div>
                                                <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger remove-new-benefit"
                                            >
                                                Remove new benefit
                                            </button>
                                        </div>
                                    </div>
                                    @php
                                        $newBenefitIndex++;
                                    @endphp
                                @endif
                            @endforeach
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline-success mb-4"
                            id="addBenefit"
                        >
                            Add benefit
                        </button>

                        <hr>
                        <h5 class="fw-bold mb-3">Program-wide eligibility</h5>
                        <p class="text-muted">These requirements apply in addition to the selected benefit's requirements.</p>
                        <div class="mb-3">
                            <label class="form-label">Allowed Crop Types</label>
                            @php
                                $selectedGlobalCrops = old('global_eligibility.crop_types', $globalRule?->crop_types ?? []);
                            @endphp
                            <input type="hidden" name="global_eligibility[crop_types][]" value="">
                            <select name="global_eligibility[crop_types][]" class="form-select" multiple>
                                @foreach($cropTypes as $cropType)
                                    <option value="{{ $cropType }}" @selected(is_array($selectedGlobalCrops) && in_array($cropType, $selectedGlobalCrops, true))>{{ $cropType }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave unselected for no crop restriction.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Minimum Land Size (hectares)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="global_eligibility[min_land_size]"
                                    class="form-control"
                                    value="{{ old('global_eligibility.min_land_size', $globalRule?->min_land_size) }}"
                                >
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Maximum Land Size (hectares)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="global_eligibility[max_land_size]"
                                    class="form-control"
                                    value="{{ old('global_eligibility.max_land_size', $globalRule?->max_land_size) }}"
                                >
                            </div>
                        </div>
                        @foreach(['requires_rsbsa' => 'RSBSA Registration', 'requires_association' => 'Farmers Association Membership', 'requires_4ps' => '4Ps Beneficiary'] as $field => $label)
                            <input type="hidden" name="global_eligibility[{{ $field }}]" value="0">
                            <div class="form-check mb-2">
                                <input
                                    type="checkbox"
                                    name="global_eligibility[{{ $field }}]"
                                    value="1"
                                    class="form-check-input"
                                    id="global_{{ $field }}"
                                    @checked(old("global_eligibility.{$field}", $globalRule?->{$field}))
                                >
                                <label class="form-check-label" for="global_{{ $field }}">{{ $label }} Required</label>
                            </div>
                        @endforeach
                        <input type="hidden" name="clear_global_eligibility" value="0">
                        <div class="form-check mt-3 mb-4">
                            <input
                                type="checkbox"
                                name="clear_global_eligibility"
                                value="1"
                                class="form-check-input"
                                id="clear_global_eligibility"
                                @checked(old('clear_global_eligibility'))
                            >
                            <label class="form-check-label text-danger" for="clear_global_eligibility">Explicitly clear the program-wide eligibility rule</label>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-fams"
                        >
                            Save Program
                        </button>
                        <a href="{{ route('admin.programs.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </form>

                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const container = document.getElementById('newBenefits');
                        const addButton = document.getElementById('addBenefit');
                        let newBenefitIndex = {{ $newBenefitIndex }};

                        container.addEventListener('click', function (event) {
                            const removeButton = event.target.closest('.remove-new-benefit');
                            if (removeButton) {
                                removeButton.closest('.card').remove();
                            }
                        });

                        addButton.addEventListener('click', function () {
                            const index = newBenefitIndex++;
                            const card = document.createElement('div');
                            card.className = 'card mb-3';
                            card.innerHTML = `
                                <div class="card-body">
                                    <h6 class="fw-semibold">New benefit</h6>
                                    <p class="text-muted">Confirm an unrestricted eligibility setup now, or leave this unchecked and configure rules in another edit.</p>
                                    <div class="mb-3">
                                        <label class="form-label">Benefit Name</label>
                                        <input
                                            type="text"
                                            name="benefits[${index}][name]"
                                            class="form-control"
                                            required
                                        >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea
                                            name="benefits[${index}][description]"
                                            class="form-control"
                                            rows="2"
                                        ></textarea>
                                    </div>
                                    <input
                                        type="hidden"
                                        name="benefits[${index}][eligibility_configured]"
                                        value="0"
                                    >
                                    <div class="form-check mb-3">
                                        <input
                                            type="checkbox"
                                            name="benefits[${index}][eligibility_configured]"
                                            value="1"
                                            class="form-check-input"
                                            id="configure_new_benefit_${index}"
                                        >
                                        <label class="form-check-label" for="configure_new_benefit_${index}">
                                            Confirm eligibility is configured with no benefit-specific restrictions
                                        </label>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger remove-new-benefit"
                                    >
                                        Remove new benefit
                                    </button>
                                </div>`;
                            container.appendChild(card);
                        });
                    });
                    </script>
                @else


                {{-- ========================================================
                     STEP INDICATOR
                     ======================================================== --}}
                <div class="d-flex justify-content-between mb-5">

                    <div class="text-center flex-fill">

                        <div
                            class="step-circle active mx-auto"
                            id="stepCircle1"
                        >
                            1
                        </div>

                        <small class="d-block mt-2">
                            Program & Benefit
                        </small>

                    </div>


                    <div class="text-center flex-fill">

                        <div
                            class="step-circle mx-auto"
                            id="stepCircle2"
                        >
                            2
                        </div>

                        <small class="d-block mt-2">
                            Eligibility Rules
                        </small>

                    </div>


                    <div class="text-center flex-fill">

                        <div
                            class="step-circle mx-auto"
                            id="stepCircle3"
                        >
                            3
                        </div>

                        <small class="d-block mt-2">
                            Review & Save
                        </small>

                    </div>

                </div>


                {{-- ========================================================
                     FORM
                     ======================================================== --}}
                <form
                    method="POST"
                    action="{{ route('admin.programs.store') }}"
                    id="programForm"
                >

                    @csrf


                    {{-- ====================================================
                         STEP 1
                         ==================================================== --}}
                    <div
                        class="step-content"
                        id="step1"
                    >

                        <h5 class="fw-bold mb-4">
                            Program & Benefit
                        </h5>


                        {{-- Program --}}
                        <div class="mb-4">

                            <label
                                for="program_id"
                                class="form-label fw-semibold"
                            >
                                Program
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="program_id"
                                id="program_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select Program
                                </option>

                                @foreach($availablePrograms as $program)

                                    <option value="{{ $program->id }}">
                                        {{ $program->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Benefit --}}
                        <div class="mb-4">

                            <label
                                for="program_benefit_id"
                                class="form-label fw-semibold"
                            >
                                Benefit
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="program_benefit_id"
                                id="program_benefit_id"
                                class="form-select"
                                required
                                disabled
                            >

                                <option value="">
                                    Select Program First
                                </option>

                            </select>

                            <small class="text-muted">
                                Available benefits depend on the selected program.
                            </small>

                        </div>


                        {{-- Description --}}
                        <div class="mb-4">

                            <label
                                for="description"
                                class="form-label fw-semibold"
                            >
                                Description
                                <span class="text-danger">*</span>
                            </label>

                            <textarea
                                name="description"
                                id="description"
                                class="form-control"
                                rows="4"
                                required
                                placeholder="Enter program description"
                            ></textarea>

                        </div>


                        {{-- Schedule --}}
                        <div class="row">

                            <div class="col-md-6 mb-4">

                                <label
                                    for="start_at"
                                    class="form-label fw-semibold"
                                >
                                    Start Date & Time
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="datetime-local"
                                    name="start_at"
                                    id="start_at"
                                    class="form-control"
                                    required
                                >

                            </div>


                            <div class="col-md-6 mb-4">

                                <label
                                    for="end_at"
                                    class="form-label fw-semibold"
                                >
                                    End Date & Time
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="datetime-local"
                                    name="end_at"
                                    id="end_at"
                                    class="form-control"
                                    required
                                >

                            </div>

                        </div>


                        <button
                            type="button"
                            class="btn btn-fams w-100"
                            id="nextToStep2"
                        >
                            Next
                        </button>

                    </div>


                    {{-- ====================================================
                         STEP 2
                         ==================================================== --}}
                    <div
                        class="step-content d-none"
                        id="step2"
                    >

                        <h5 class="fw-bold mb-4">
                            Eligibility Rules
                        </h5>


                        <div class="alert alert-light border mb-4">

                            <small>
                                Set the requirements that a farmer must meet
                                to qualify for the selected benefit.
                            </small>

                        </div>


                        {{-- Crop Types --}}
                        <div class="mb-4">

                            <label
                                for="crop_types"
                                class="form-label fw-semibold"
                            >
                                Allowed Crop Types
                            </label>

                            <select
                                name="eligibility[crop_types][]"
                                id="crop_types"
                                class="form-select"
                                multiple
                            >

                                <option value="Rice">
                                    Rice
                                </option>

                                <option value="Corn">
                                    Corn
                                </option>

                                <option value="Vegetables">
                                    Vegetables
                                </option>

                                <option value="Fruits">
                                    Fruits
                                </option>

                                <option value="Coconut">
                                    Coconut
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>

                            <small class="text-muted">
                                Hold Ctrl while clicking to select multiple crop types.
                            </small>

                        </div>


                        {{-- Land Size --}}
                        <div class="row">

                            <div class="col-md-6 mb-4">

                                <label
                                    for="min_land_size"
                                    class="form-label fw-semibold"
                                >
                                    Minimum Land Size
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        name="eligibility[min_land_size]"
                                        id="min_land_size"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                    >

                                    <span class="input-group-text">
                                        hectares
                                    </span>

                                </div>

                            </div>


                            <div class="col-md-6 mb-4">

                                <label
                                    for="max_land_size"
                                    class="form-label fw-semibold"
                                >
                                    Maximum Land Size
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        name="eligibility[max_land_size]"
                                        id="max_land_size"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                    >

                                    <span class="input-group-text">
                                        hectares
                                    </span>

                                </div>

                            </div>

                        </div>


                        {{-- RSBSA --}}
                        <div class="form-check mb-3">

                            <input
                                type="checkbox"
                                name="eligibility[requires_rsbsa]"
                                value="1"
                                class="form-check-input"
                                id="requires_rsbsa"
                            >

                            <label
                                class="form-check-label"
                                for="requires_rsbsa"
                            >
                                RSBSA Registration Required
                            </label>

                        </div>


                        {{-- Association --}}
                        <div class="form-check mb-3">

                            <input
                                type="checkbox"
                                name="eligibility[requires_association]"
                                value="1"
                                class="form-check-input"
                                id="requires_association"
                            >

                            <label
                                class="form-check-label"
                                for="requires_association"
                            >
                                Farmers Association Membership Required
                            </label>

                        </div>


                        {{-- 4Ps --}}
                        <div class="form-check mb-4">

                            <input
                                type="checkbox"
                                name="eligibility[requires_4ps]"
                                value="1"
                                class="form-check-input"
                                id="requires_4ps"
                            >

                            <label
                                class="form-check-label"
                                for="requires_4ps"
                            >
                                4Ps Beneficiary Required
                            </label>

                        </div>


                        <div class="d-flex gap-2">

                            <button
                                type="button"
                                class="btn btn-outline-secondary flex-fill"
                                id="backToStep1"
                            >
                                Back
                            </button>

                            <button
                                type="button"
                                class="btn btn-fams flex-fill"
                                id="nextToStep3"
                            >
                                Review
                            </button>

                        </div>

                    </div>


                    {{-- ====================================================
                         STEP 3
                         ==================================================== --}}
                    <div
                        class="step-content d-none"
                        id="step3"
                    >

                        <h5 class="fw-bold mb-4">
                            Review & Save
                        </h5>


                        <div class="card bg-light border-0 mb-4">

                            <div class="card-body">

                                <div class="mb-3">
                                    <strong>Program:</strong>
                                    <div id="reviewProgram" class="mt-1">
                                        —
                                    </div>
                                </div>


                                <div class="mb-3">
                                    <strong>Benefit:</strong>
                                    <div id="reviewBenefit" class="mt-1">
                                        —
                                    </div>
                                </div>


                                <div class="mb-3">
                                    <strong>Description:</strong>
                                    <div id="reviewDescription" class="mt-1">
                                        —
                                    </div>
                                </div>


                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <strong>Start:</strong>

                                        <div
                                            id="reviewStart"
                                            class="mt-1"
                                        >
                                            —
                                        </div>

                                    </div>


                                    <div class="col-md-6 mb-3">

                                        <strong>End:</strong>

                                        <div
                                            id="reviewEnd"
                                            class="mt-1"
                                        >
                                            —
                                        </div>

                                    </div>

                                </div>


                                <div class="mb-3">

                                    <strong>Crop Types:</strong>

                                    <div
                                        id="reviewCrops"
                                        class="mt-1"
                                    >
                                        —
                                    </div>

                                </div>


                                <div class="mb-3">

                                    <strong>Land Size:</strong>

                                    <div
                                        id="reviewLand"
                                        class="mt-1"
                                    >
                                        —
                                    </div>

                                </div>


                                <div>

                                    <strong>Requirements:</strong>

                                    <div
                                        id="reviewRequirements"
                                        class="mt-1"
                                    >
                                        —
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="d-flex gap-2">

                            <button
                                type="button"
                                class="btn btn-outline-secondary flex-fill"
                                id="backToStep2"
                            >
                                Back
                            </button>

                            <button
                                type="submit"
                                class="btn btn-fams flex-fill"
                            >
                                Save Program
                            </button>

                        </div>

                    </div>

                </form>
                @endif

            </div>

        </div>

    </div>
</div>


{{-- ================================================================
     STEPPER JAVASCRIPT
     ================================================================ --}}
<script>

@if(!($isEdit ?? false))
document.addEventListener('DOMContentLoaded', function () {

    const programSelect =
        document.getElementById('program_id');

    const benefitSelect =
        document.getElementById('program_benefit_id');

    const step1 =
        document.getElementById('step1');

    const step2 =
        document.getElementById('step2');

    const step3 =
        document.getElementById('step3');

    const circle1 =
        document.getElementById('stepCircle1');

    const circle2 =
        document.getElementById('stepCircle2');

    const circle3 =
        document.getElementById('stepCircle3');


    /*
    |--------------------------------------------------------------------------
    | Program Benefits
    |--------------------------------------------------------------------------
    */

    const programBenefits = @json(

        $availablePrograms->mapWithKeys(function ($program) {

            return [

                $program->id => $program->activeBenefits
                    ->map(function ($benefit) {

                        return [
                            'id' => $benefit->id,
                            'name' => $benefit->name,
                        ];

                    })
                    ->values()

            ];

        })

    );


    /*
    |--------------------------------------------------------------------------
    | Program Selection
    |--------------------------------------------------------------------------
    */

    programSelect.addEventListener('change', function () {

        const programId = this.value;

        benefitSelect.innerHTML =
            '<option value="">Select Benefit</option>';

        benefitSelect.disabled = true;


        if (!programId) {
            return;
        }


        const benefits =
            programBenefits[programId] || [];


        benefits.forEach(function (benefit) {

            const option =
                document.createElement('option');

            option.value =
                benefit.id;

            option.textContent =
                benefit.name;

            benefitSelect.appendChild(option);

        });


        /*
        |--------------------------------------------------------------------------
        | One Benefit
        |--------------------------------------------------------------------------
        */

        if (benefits.length === 1) {

            benefitSelect.value =
                benefits[0].id;

            benefitSelect.disabled =
                false;

        }


        /*
        |--------------------------------------------------------------------------
        | Multiple Benefits
        |--------------------------------------------------------------------------
        */

        else if (benefits.length > 1) {

            benefitSelect.disabled =
                false;

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Step Navigation
    |--------------------------------------------------------------------------
    */

    function showStep(stepNumber) {

        step1.classList.add('d-none');
        step2.classList.add('d-none');
        step3.classList.add('d-none');

        circle1.classList.remove('active');
        circle2.classList.remove('active');
        circle3.classList.remove('active');


        if (stepNumber === 1) {

            step1.classList.remove('d-none');
            circle1.classList.add('active');

        }


        if (stepNumber === 2) {

            step2.classList.remove('d-none');
            circle2.classList.add('active');

        }


        if (stepNumber === 3) {

            step3.classList.remove('d-none');
            circle3.classList.add('active');

            updateReview();

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Step 1 → Step 2
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('nextToStep2')
        .addEventListener('click', function () {

            if (!programSelect.value) {

                alert('Please select a program.');
                return;

            }


            if (!benefitSelect.value) {

                alert('Please select a benefit.');
                return;

            }


            const description =
                document
                    .getElementById('description')
                    .value
                    .trim();

            if (!description) {

                alert('Please enter a description.');
                return;

            }


            const startAt =
                document.getElementById('start_at').value;

            if (!startAt) {

                alert('Please select a start date and time.');
                return;

            }


            const endAt =
                document.getElementById('end_at').value;

            if (!endAt) {

                alert('Please select an end date and time.');
                return;

            }


            if (startAt > endAt) {

                alert(
                    'End date/time must be after the start date/time.'
                );

                return;

            }


            showStep(2);

        });


    /*
    |--------------------------------------------------------------------------
    | Step 2 → Step 3
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('nextToStep3')
        .addEventListener('click', function () {

            const minLand =
                document.getElementById('min_land_size').value;

            const maxLand =
                document.getElementById('max_land_size').value;


            if (
                minLand &&
                maxLand &&
                parseFloat(maxLand) < parseFloat(minLand)
            ) {

                alert(
                    'Maximum land size cannot be smaller than minimum land size.'
                );

                return;

            }


            showStep(3);

        });


    /*
    |--------------------------------------------------------------------------
    | Back to Step 1
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('backToStep1')
        .addEventListener('click', function () {

            showStep(1);

        });


    /*
    |--------------------------------------------------------------------------
    | Back to Step 2
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('backToStep2')
        .addEventListener('click', function () {

            showStep(2);

        });


    /*
    |--------------------------------------------------------------------------
    | Review
    |--------------------------------------------------------------------------
    */

    function updateReview() {

        const selectedProgram =
            programSelect.options[
                programSelect.selectedIndex
            ];

        const selectedBenefit =
            benefitSelect.options[
                benefitSelect.selectedIndex
            ];


        document.getElementById('reviewProgram').textContent =
            selectedProgram
                ? selectedProgram.text
                : '—';


        document.getElementById('reviewBenefit').textContent =
            selectedBenefit
                ? selectedBenefit.text
                : '—';


        document.getElementById('reviewDescription').textContent =
            document.getElementById('description').value
            || '—';


        document.getElementById('reviewStart').textContent =
            formatDateTime(
                document.getElementById('start_at').value
            );


        document.getElementById('reviewEnd').textContent =
            formatDateTime(
                document.getElementById('end_at').value
            );


        /*
        |--------------------------------------------------------------------------
        | Crop Types
        |--------------------------------------------------------------------------
        */

        const selectedCrops =
            Array.from(
                document.getElementById('crop_types').selectedOptions
            ).map(function (option) {

                return option.text;

            });


        document.getElementById('reviewCrops').textContent =
            selectedCrops.length
                ? selectedCrops.join(', ')
                : 'No crop restriction';


        /*
        |--------------------------------------------------------------------------
        | Land Size
        |--------------------------------------------------------------------------
        */

        const minLand =
            document.getElementById('min_land_size').value;

        const maxLand =
            document.getElementById('max_land_size').value;


        let landText = 'No land size restriction';


        if (minLand && maxLand) {

            landText =
                minLand + ' - ' + maxLand + ' hectares';

        } else if (minLand) {

            landText =
                'Minimum ' + minLand + ' hectares';

        } else if (maxLand) {

            landText =
                'Maximum ' + maxLand + ' hectares';

        }


        document.getElementById('reviewLand').textContent =
            landText;


        /*
        |--------------------------------------------------------------------------
        | Requirements
        |--------------------------------------------------------------------------
        */

        const requirements = [];


        if (
            document.getElementById('requires_rsbsa').checked
        ) {

            requirements.push('RSBSA Required');

        }


        if (
            document.getElementById('requires_association').checked
        ) {

            requirements.push(
                'Farmers Association Required'
            );

        }


        if (
            document.getElementById('requires_4ps').checked
        ) {

            requirements.push('4Ps Required');

        }


        document.getElementById('reviewRequirements').textContent =
            requirements.length
                ? requirements.join(', ')
                : 'No additional membership requirements';

    }


    /*
    |--------------------------------------------------------------------------
    | Date Formatting
    |--------------------------------------------------------------------------
    */

    function formatDateTime(value) {

        if (!value) {
            return '—';
        }


        const date =
            new Date(value);


        if (Number.isNaN(date.getTime())) {
            return value;
        }


        return date.toLocaleString(
            undefined,
            {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            }
        );

    }

});
@endif

</script>


{{-- ================================================================
     STEPPER CSS
     ================================================================ --}}
<style>

.step-circle {

    width: 38px;
    height: 38px;

    border-radius: 50%;

    background: #e9ecef;

    color: #6c757d;

    display: flex;

    align-items: center;
    justify-content: center;

    font-weight: 600;

}

.step-circle.active {

    background: #198754;

    color: white;

}

</style>

@endsection