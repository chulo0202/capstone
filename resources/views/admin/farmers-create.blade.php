@extends('layouts.admin')

@section('page-title', 'Add Farmer')

@section('main')
<div class="card border-0 shadow-sm">
    <div class="card-body">

        <form method="POST"
              action="{{ route('admin.farmers.store') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="row g-4">
                <div class="col-lg-8"><div class="row g-3">

                {{-- ACCOUNT INFORMATION --}}
                <div class="col-12">
                    <h6 class="text-fams border-bottom pb-2">
                        <i class="bi bi-person-circle me-2"></i>
                        Account Information
                    </h6>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Account Name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" required>
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" required>
                    @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="password" name="password" autocomplete="new-password"
                           class="form-control" required>
                    @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                    @include('partials.farmer-password-help')
                </div>

                {{-- FARMER INFORMATION --}}
                <div class="col-12 mt-4">
                    <h6 class="text-fams border-bottom pb-2">
                        <i class="bi bi-person-badge me-2"></i>
                        Farmer Information
                    </h6>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" name="full_name" class="form-control"
                           value="{{ old('full_name') }}" required>
                    @error('full_name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Contact Number <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" name="contact_number" inputmode="numeric" pattern="[0-9]{11}" maxlength="11"
                           class="form-control"
                           value="{{ old('contact_number') }}"
                           placeholder="09XXXXXXXXX" required>
                    @error('contact_number')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Address <span class="text-danger" aria-hidden="true">*</span></label>
                    <textarea name="address" class="form-control"
                              rows="2" required>{{ old('address') }}</textarea>
                    @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="farmer-province">Province</label>
                    <select class="form-select" id="farmer-province">
                        <option>Sorsogon</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="farmer-municipality">Municipality</label>
                    <select class="form-select" id="farmer-municipality">
                        <option>Bulan</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Barangay <span class="text-danger" aria-hidden="true">*</span></label>
                    <select name="barangay" id="barangay"
                            class="form-select" required>
                        <option value="">Select Barangay</option>

                        @php
                            $barangays = [
                                'A. Bonifacio',
                                'Abad Santos',
                                'Aguinaldo',
                                'Antipolo',
                                'Beguin',
                                'Bical',
                                'Bonga',
                                'Butag',
                                'Cadandanan',
                                'Calomagon',
                                'Calpi',
                                'Cocok-Cabitan',
                                'Daganas',
                                'Danao',
                                'Dolos',
                                'E. Quirino',
                                'Fabrica',
                                'Gate',
                                'Benigno S. Aquino',
                                'Inararan',
                                'J. Gerona',
                                'J.P. Laurel',
                                'Jamorawon',
                                'Lajong',
                                'Libertad',
                                'Magsaysay',
                                'Managanaga',
                                'Marinab',
                                'Namo',
                                'Nasuje',
                                'Montecalvario',
                                'N. Roque',
                                'Obrero',
                                'Osmeña',
                                'Otavi',
                                'Padre Diaz',
                                'Palale',
                                'Quezon',
                                'R. Gerona',
                                'Recto',
                                'M. Roxas',
                                'Sagrada',
                                'San Francisco',
                                'San Isidro',
                                'San Juan Bag-o',
                                'San Juan Daan',
                                'San Rafael',
                                'San Ramon',
                                'San Vicente',
                                'Santa Remedios',
                                'Santa Teresita',
                                'Sigad',
                                'Somagongsong',
                                'G. Del Pilar',
                                'Taromata',
                                'Zone I Pob.',
                                'Zone II Pob.',
                                'Zone III Pob.',
                                'Zone IV Pob.',
                                'Zone V Pob.',
                                'Zone VI Pob.',
                                'Zone VII Pob.',
                                'Zone VIII Pob.',
                            ];
                        @endphp

                        @foreach($barangays as $barangayOption)
                            <option value="{{ $barangayOption }}"
                                {{ old('barangay') === $barangayOption ? 'selected' : '' }}>
                                {{ $barangayOption }}
                            </option>
                        @endforeach
                    </select>
                    @error('barangay')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Birthdate</label>
                    <input type="date" name="birthdate" max="{{ today()->subDay()->toDateString() }}" class="form-control"
                           value="{{ old('birthdate') }}" required>
                    @error('birthdate')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                {{-- FARM INFORMATION --}}
                <div class="col-12 mt-4">
                    <h6 class="text-fams border-bottom pb-2">
                        <i class="bi bi-tree me-2"></i>
                        Farm Information
                    </h6>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Crop Type <span class="text-danger" aria-hidden="true">*</span></label>
                    <select name="crop_type" class="form-select" required>
                        <option value="">Select Crop</option>

                        @foreach(['Rice','Corn','Vegetables','Fruits','Coconut','Other'] as $crop)
                            <option value="{{ $crop }}"
                                {{ old('crop_type') === $crop ? 'selected' : '' }}>
                                {{ $crop }}
                            </option>
                        @endforeach
                    </select>
                    @error('crop_type')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Land Size (hectares) <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="number" name="land_size" class="form-control"
                           value="{{ old('land_size') }}"
                           step="0.01" min="0"
                           placeholder="e.g. 0.50" required>
                    @error('land_size')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">RSBSA Number <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" name="rsbsa_number" class="form-control"
                           value="{{ old('rsbsa_number') }}"
                           placeholder="Enter RSBSA Number" required>
                    @error('rsbsa_number')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="association_membership"
                               value="1" class="form-check-input"
                               id="association"
                               {{ old('association_membership') ? 'checked' : '' }}>
                        <label class="form-check-label" for="association">
                            Association Member
                        </label>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">4Ps Membership</label>
                    <select name="four_ps_membership" class="form-select">
                        <option value="0"
                            {{ old('four_ps_membership', 0) == 0 ? 'selected' : '' }}>
                            No
                        </option>
                        <option value="1"
                            {{ old('four_ps_membership', 0) == 1 ? 'selected' : '' }}>
                            Yes
                        </option>
                    </select>
                    @error('four_ps_membership')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                {{-- ASSOCIATION DROPDOWN --}}
                <div class="col-md-6" id="association-container"
                     style="display: none;">
                    <label class="form-label">Farmer Association</label>
                    <select name="farmer_association"
                            id="farmer_association"
                            class="form-select"
                            disabled>
                        <option value="">Select Farmer Association</option>
                        @foreach(config('farmer_associations', []) as $association)
                            <option value="{{ $association }}"
                                {{ old('farmer_association') === $association ? 'selected' : '' }}>
                                {{ $association }}
                            </option>
                        @endforeach
                    </select>
                    @error('farmer_association')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                </div></div>

                <aside class="col-lg-4">
                    <h6 class="text-fams border-bottom pb-2">Upload Documents</h6>
                    @foreach (['valid_id' => 'Valid ID', 'farmer_photo' => 'Farmer Photo'] as $field => $label)
                        <div class="mb-3 rounded border p-3" data-document-preview>
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <input type="file" id="{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror"
                                accept="{{ $field === 'valid_id' ? '.jpg,.jpeg,.png,.pdf' : '.jpg,.jpeg,.png' }}">
                            <small class="form-text">Choose a file or drop it here.</small>
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if ($field === 'valid_id')
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2"
                                    data-national-id-ocr="{{ route('admin.farmers.national-id.ocr') }}">
                                    Read National ID
                                </button>
                                <div class="form-text" data-ocr-status role="status">Use a clear JPG or PNG of the ID front. Crop close to the card, keep it straight, and avoid glare.</div>
                            @endif
                            <a class="d-none mt-2" target="_blank" rel="noopener">Preview document</a>
                            <img class="d-none img-fluid rounded mt-2" alt="{{ $label }} preview">
                        </div>
                    @endforeach
                </aside>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-fams">
                    <i class="bi bi-check-lg me-1"></i>
                    Save Farmer
                </button>

                <a href="{{ route('admin.farmers.index') }}"
                   class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-document-preview]').forEach(function (preview) {
        const input = preview.querySelector('input');
        const image = preview.querySelector('img');
        const link = preview.querySelector('a');
        let previewUrl;

        preview.addEventListener('dragover', (event) => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
        });
        preview.addEventListener('drop', (event) => {
            event.preventDefault();
            const files = event.dataTransfer.files;
            if (files.length !== 1) return;
            input.files = files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        input.addEventListener('change', function () {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            const file = input.files[0];
            previewUrl = file ? URL.createObjectURL(file) : null;
            const isImage = Boolean(file && file.type.startsWith('image/'));

            link.classList.toggle('d-none', !file || isImage);
            image.classList.toggle('d-none', !isImage);

            link.removeAttribute('href');
            image.removeAttribute('src');
            if (isImage) image.src = previewUrl;
            if (file && !isImage) link.href = previewUrl;
        });
    });

    const ocr = document.querySelector('[data-national-id-ocr]');
    const idInput = document.getElementById('valid_id');
    const ocrStatus = document.querySelector('[data-ocr-status]');

    ocr.addEventListener('click', async function () {
        const file = idInput.files[0];
        if (!file) {
            ocrStatus.textContent = 'Choose a National ID image first.';
            return;
        }

        ocr.disabled = true;
        ocrStatus.textContent = 'Reading National ID…';
        const body = new FormData();
        body.append('valid_id', file);

        try {
            const response = await fetch(ocr.dataset.nationalIdOcr, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': ocr.form.elements._token.value,
                },
                body,
            });
            const result = await response.json();

            if (idInput.files[0] !== file) {
                ocrStatus.textContent = 'The file changed. Read the selected ID again.';
                return;
            }

            if (!response.ok) {
                ocrStatus.textContent = result.message || 'Enter the details manually.';
                return;
            }

            if (result.full_name) {
                if (!ocr.form.elements.full_name.value) ocr.form.elements.full_name.value = result.full_name;
                if (!ocr.form.elements.name.value) ocr.form.elements.name.value = result.full_name;
            }
            if (result.birthdate && !ocr.form.elements.birthdate.value) {
                ocr.form.elements.birthdate.value = result.birthdate;
            }

            ocrStatus.textContent = result.full_name && result.birthdate
                ? 'Review the name and birthdate before saving.'
                : result.full_name || result.birthdate
                    ? 'Only some details were read. Review the filled fields and enter the missing information.'
                    : 'No name or birthdate was read. Try a closer, straight photo with less glare, or enter the details manually.';
        } catch {
            ocrStatus.textContent = 'The ID could not be read. Enter the details manually.';
        } finally {
            ocr.disabled = false;
        }
    });

    const membershipCheckbox = document.getElementById('association');
    const associationContainer = document.getElementById('association-container');
    const associationSelect = document.getElementById('farmer_association');

    function toggleAssociationField() {
        const isMember = membershipCheckbox.checked;

        associationContainer.style.display = isMember ? '' : 'none';
        associationSelect.disabled = !isMember;
        associationSelect.required = isMember;
    }

    membershipCheckbox.addEventListener('change', toggleAssociationField);

    toggleAssociationField();
});
</script>
@endsection