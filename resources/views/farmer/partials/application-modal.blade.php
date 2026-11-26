
{{-- Farmer Program Application Modal --}}
@php($profileHectares = (float) ($farmer?->land_size ?? 0))

<div class="modal fade fams-application-modal"
     id="programApplicationModal"
     tabindex="-1"
     aria-labelledby="programApplicationModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title"
                    id="programApplicationModalLabel">
                    Program Application
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <form id="programApplicationForm"
                method="POST" novalidate
                enctype="multipart/form-data"
                class="d-flex flex-column flex-grow-1"
                style="min-height: 0;">

                @csrf

                <input type="hidden"
                       name="program_benefit_id"
                       id="modalBenefitId">

                <div class="modal-body">
                    <div class="alert alert-danger" id="modalApplicationErrors" role="alert" tabindex="-1" hidden></div>

                    <p class="text-muted small"
                       id="modalBenefitDescription"></p>
                    <p class="small text-muted"><span class="text-danger">*</span> Required field</p>

                    {{-- Agricultural Loans --}}
                    <div class="application-fields d-none"
                         data-form-type="agricultural_loan">

                        <h6 class="fw-bold mb-3">
                            Agricultural Loan Application
                        </h6>

                        <div class="mb-3">
                            <label class="form-label" for="apply-loan_amount">
                                Loan Amount (PHP) <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="number"
                                   name="loan_amount" id="apply-loan_amount"
                                   min="0.01"
                                   step="0.01"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="apply-loan_purpose">Loan Purpose <span class="text-danger" aria-hidden="true">*</span></label>
                            <textarea name="loan_purpose" id="apply-loan_purpose"
                                      class="form-control"
                                      rows="2" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="apply-project_location">Project Location <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text"
                                   name="project_location" id="apply-project_location"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="apply-number_of_hectares">Number of Hectares <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="number"
                                   name="number_of_hectares" id="apply-number_of_hectares"
                                   max="{{ min(10000, $profileHectares) }}" aria-describedby="loan-area-limit"
                                   min="0.01"
                                   step="0.01"
                                   class="form-control" required>
                            <div id="loan-area-limit" class="form-text">Maximum from My Profile: {{ $profileHectares }} hectares.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="apply-project_description">Project Description <span class="text-danger" aria-hidden="true">*</span></label>
                            <textarea name="project_description" id="apply-project_description"
                                      class="form-control"
                                      rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="apply-agribusiness_entity">Agribusiness Entity <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text"
                                   name="agribusiness_entity" id="apply-agribusiness_entity"
                                   class="form-control" required>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="sworn_statement"
                                   value="1"
                                   id="loanSwornStatement" required>
                            <label class="form-check-label"
                                   for="loanSwornStatement">
                                I certify that the information provided
                                is true and correct. <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="privacy_consent"
                                   value="1"
                                   id="loanPrivacyConsent" required>
                            <label class="form-check-label"
                                   for="loanPrivacyConsent">
                                I consent to the processing of my
                                application information. <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                        </div>
                    </div>

                    {{-- PCIC New Application and Claim --}}
                    <div class="application-fields d-none"
                         data-form-type="pcic_new_application pcic_claim">

                        <div class="pcic-new-only">
                        <h6 class="fw-bold mb-3">
                            PCIC Crop Information
                        </h6>
                        <dl class="row">
                            <dt class="col-sm-4">Farmer</dt>
                            <dd class="col-sm-8">{{ $farmer?->full_name }}</dd>
                            <dt class="col-sm-4">Crop</dt>
                            <dd class="col-sm-8">{{ $farmer?->crop_type }}</dd>
                            <dt class="col-sm-4">Barangay</dt>
                            <dd class="col-sm-8">{{ $farmer?->barangay }}</dd>
                        </dl>

                        <div class="mb-3">
                            <label class="form-label" for="apply-variety_planted">Variety Planted <span class="text-danger" aria-hidden="true">*</span></label>
                            <select name="variety_planted" id="apply-variety_planted"
                                    class="form-select" required>
                                <option value="">Select variety</option>
                                <option value="Inbred">Inbred</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="apply-actual_planting_date">
                                Actual Date of Planting <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="date"
                                   name="actual_planting_date" id="apply-actual_planting_date"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="apply-expected_harvest_date">
                                Expected Date of Harvest <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="date"
                                   name="expected_harvest_date" id="apply-expected_harvest_date"
                                   class="form-control" required>
                        </div>

                        <p id="pcic-total-area-limit" class="form-text" data-profile-hectares="{{ $profileHectares }}">
                            Maximum for all lots combined: {{ $profileHectares }} hectares from My Profile.
                        </p>
                        @for ($lot = 0; $lot < 3; $lot++)
                            <fieldset class="border rounded-3 p-3 mb-3 {{ $lot > 0 ? 'd-none' : '' }}" data-pcic-lot>
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold">Lot {{ $lot + 1 }}</legend>
                                <div class="mb-3">
                                    <label class="form-label" for="pcic-lot-{{ $lot }}-area">
                                        Area to be Insured (hectares) <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <input type="number" id="pcic-lot-{{ $lot }}-area"
                                           name="lots[{{ $lot }}][area_to_insure]" min="0.01" max="{{ min(10000, $profileHectares) }}" step="0.01"
                                           aria-describedby="pcic-total-area-limit"
                                           class="form-control" required @disabled($lot > 0)>
                                </div>

                                <h6 class="fw-semibold">Adjacent Lots</h6>
                                <p class="form-text">Enter the name of the adjacent lot owner, neighboring farm, or identifiable landmark on each side of the insured area.</p>
                                @foreach (['north', 'south', 'east', 'west'] as $direction)
                                    <div class="mb-3">
                                        <label class="form-label" for="pcic-lot-{{ $lot }}-{{ $direction }}">
                                            {{ ucfirst($direction) }} <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <input type="text" id="pcic-lot-{{ $lot }}-{{ $direction }}"
                                               name="lots[{{ $lot }}][adjacent_{{ $direction }}]" maxlength="255"
                                               placeholder="e.g. irrigation canal or neighboring rice field"
                                               class="form-control" required @disabled($lot > 0)>
                                    </div>
                                @endforeach
                            </fieldset>
                        @endfor

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-success" data-add-pcic-lot>Add Another Lot</button>
                            <button type="button" class="btn btn-outline-secondary d-none" data-remove-pcic-lot>Remove Last Lot</button>
                        </div>

                        </div>

                        {{-- Claim-specific fields --}}
                        <div class="pcic-claim-only">

                            <h6 class="fw-bold mt-4 mb-3">
                                Claim for Indemnity
                            </h6>

                            <div class="mb-3">
                                <label class="form-label" for="apply-date_of_loss">
                                    Date of Loss <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="date"
                                    name="date_of_loss" id="apply-date_of_loss"
                                    min="{{ today('Asia/Manila')->subDays(7)->toDateString() }}"
                                    max="{{ today('Asia/Manila')->toDateString() }}"
                                    aria-describedby="date-of-loss-help"
                                    class="form-control"
                                    required
                                >
                                <div id="date-of-loss-help" class="form-text">
                                    Select today or a date up to 7 days ago.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="apply-cause_of_loss">Cause of Loss <span class="text-danger" aria-hidden="true">*</span></label>
                                <select name="cause_of_loss" id="apply-cause_of_loss"
                                        class="form-select" required>
                                    <option value="">Select cause</option>
                                    <option value="Typhoon">Typhoon</option>
                                    <option value="Flood">Flood</option>
                                    <option value="Drought">Drought</option>
                                    <option value="Pest">Pest</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="apply-area_damaged">
                                    Area Damaged (hectares) <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="number"
                                       name="area_damaged" id="apply-area_damaged"
                                       max="{{ min(10000, $profileHectares) }}" aria-describedby="claim-area-limit"
                                       min="0.01"
                                       step="0.01"
                                       class="form-control" required>
                                <div id="claim-area-limit" class="form-text">Maximum from My Profile: {{ $profileHectares }} hectares.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="apply-damage_photo">
                                    Photos of Damaged Crop <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="file"
                                       name="damage_photo[]" id="apply-damage_photo" multiple
                                       aria-describedby="damage-photo-help"
                                       accept="image/jpeg,image/png,image/webp"
                                       class="form-control" required>
                                <div id="damage-photo-help" class="form-text">
                                    Upload 1 to 3 photos in JPG, PNG, or WebP format. Maximum 5 MB per photo.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Seed and Fertilizer Subsidies --}}
                    <div class="application-fields d-none"
                         data-form-type="claim_intent">

                        <h6 class="fw-bold mb-3">
                            Confirm Intent to Claim
                        </h6>

                        <p>
                            Please confirm that you intend to claim
                            the selected agricultural subsidy.
                        </p>

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="confirm_intent"
                                   value="1"
                                   id="confirmClaimIntent" required>
                            <label class="form-check-label"
                                   for="confirmClaimIntent">
                                I confirm my intent to claim this benefit. <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                        </div>
                    </div>

                    {{-- Other Service Programs --}}
                    <div class="application-fields d-none"
                         data-form-type="service_request">

                        <h6 class="fw-bold mb-3">
                            Request Service
                        </h6>

                        <div class="mb-3">
                            <label class="form-label" for="service-description">
                                Service Request Description <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <textarea name="service_description"
                                      id="service-description"
                                      class="form-control"
                                      minlength="10"
                                      maxlength="2000"
                                      aria-describedby="service-description-help"
                                      rows="3"
                                      required></textarea>
                            <div id="service-description-help" class="form-text">Enter at least 10 characters.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="preferred-service-date">
                                Preferred Service Date <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="date"
                                   name="preferred_service_date"
                                   id="preferred-service-date"
                                   min="{{ today()->toDateString() }}"
                                   class="form-control"
                                   required>
                        </div>
                    </div>

                    <div class="alert alert-info small mt-3 mb-0">
                        Submission is subject to eligibility
                        verification and administrative review.
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-fams"
                            id="modalSubmitButton"
                            disabled>
                        Submit Application
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
