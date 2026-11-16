
{{-- Farmer Program Application Modal --}}

<div class="modal fade"
     id="programApplicationModal"
     tabindex="-1"
     aria-labelledby="programApplicationModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                method="POST"
                enctype="multipart/form-data"
                class="d-flex flex-column flex-grow-1"
                style="min-height: 0;">

                @csrf

                <input type="hidden"
                       name="program_benefit_id"
                       id="modalBenefitId">

                <div class="modal-body">

                    <p class="text-muted small"
                       id="modalBenefitDescription"></p>

                    {{-- Agricultural Loans --}}
                    <div class="application-fields d-none"
                         data-form-type="agricultural_loan">

                        <h6 class="fw-bold mb-3">
                            Agricultural Loan Application
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">
                                Loan Amount (PHP)
                            </label>
                            <input type="number"
                                   name="loan_amount"
                                   min="0.01"
                                   step="0.01"
                                   class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Loan Purpose</label>
                            <textarea name="loan_purpose"
                                      class="form-control"
                                      rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Project Location</label>
                            <input type="text"
                                   name="project_location"
                                   class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Number of Hectares</label>
                            <input type="number"
                                   name="number_of_hectares"
                                   min="0.01"
                                   step="0.01"
                                   class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Project Description</label>
                            <textarea name="project_description"
                                      class="form-control"
                                      rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Agribusiness Entity</label>
                            <input type="text"
                                   name="agribusiness_entity"
                                   class="form-control">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="sworn_statement"
                                   value="1"
                                   id="loanSwornStatement">
                            <label class="form-check-label"
                                   for="loanSwornStatement">
                                I certify that the information provided
                                is true and correct.
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="privacy_consent"
                                   value="1"
                                   id="loanPrivacyConsent">
                            <label class="form-check-label"
                                   for="loanPrivacyConsent">
                                I consent to the processing of my
                                application information.
                            </label>
                        </div>
                    </div>

                    {{-- PCIC New Application and Claim --}}
                    <div class="application-fields d-none"
                         data-form-type="pcic_new_application pcic_claim">

                        <h6 class="fw-bold mb-3">
                            PCIC Crop Information
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">Variety Planted</label>
                            <select name="variety_planted"
                                    class="form-select">
                                <option value="">Select variety</option>
                                <option value="Inbred">Inbred</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>

                        <div class="mb-3 pcic-new-only">
                            <label class="form-label">
                                Area to be Insured (hectares)
                            </label>
                            <input type="number"
                                   name="area_to_insure"
                                   min="0.01"
                                   step="0.01"
                                   class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Actual Date of Planting
                            </label>
                            <input type="date"
                                   name="actual_planting_date"
                                   class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Expected Date of Harvest
                            </label>
                            <input type="date"
                                   name="expected_harvest_date"
                                   class="form-control">
                        </div>

                        <h6 class="fw-semibold mt-4">
                            Adjacent Lots
                        </h6>

                        @foreach (['north', 'south', 'east', 'west'] as $direction)
                            <div class="mb-3">
                                <label class="form-label">
                                    {{ ucfirst($direction) }}
                                </label>
                                <input type="text"
                                       name="adjacent_{{ $direction }}"
                                       class="form-control">
                            </div>
                        @endforeach

                        {{-- Claim-specific fields --}}
                        <div class="pcic-claim-only">

                            <h6 class="fw-bold mt-4 mb-3">
                                Claim for Indemnity
                            </h6>

                            <div class="mb-3">
                                <label class="form-label">
                                    Insurance Reference Number
                                </label>
                                <input type="text"
                                       name="insurance_reference"
                                       class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Date of Loss
                                </label>
                                <input
                                    type="date"
                                    name="date_of_loss"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cause of Loss</label>
                                <select name="cause_of_loss"
                                        class="form-select">
                                    <option value="">Select cause</option>
                                    <option value="Typhoon">Typhoon</option>
                                    <option value="Flood">Flood</option>
                                    <option value="Drought">Drought</option>
                                    <option value="Pest">Pest</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Area Damaged (hectares)
                                </label>
                                <input type="number"
                                       name="area_damaged"
                                       min="0.01"
                                       step="0.01"
                                       class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Degree of Damage (%)
                                </label>
                                <input type="number"
                                       name="degree_of_damage"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Photo of Damaged Crop
                                </label>
                                <input type="file"
                                       name="damage_photo"
                                       accept="image/jpeg,image/png,image/webp"
                                       class="form-control">
                                <div class="form-text">
                                    JPG, PNG or WebP. Maximum 5 MB.
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
                                   id="confirmClaimIntent">
                            <label class="form-check-label"
                                   for="confirmClaimIntent">
                                I confirm my intent to claim this benefit.
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
                            <label class="form-label">
                                Service Request Description
                            </label>
                            <textarea name="service_description"
                                      class="form-control"
                                      rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Preferred Service Date
                            </label>
                            <input type="date"
                                   name="preferred_service_date"
                                   class="form-control">
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
