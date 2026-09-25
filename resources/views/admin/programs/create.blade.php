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

            </div>

        </div>

    </div>
</div>


{{-- ================================================================
     STEPPER JAVASCRIPT
     ================================================================ --}}
<script>

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

                $program->id => $program->benefits
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