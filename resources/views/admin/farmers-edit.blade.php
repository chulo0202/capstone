@extends('layouts.admin')

@section('page-title', 'Edit Farmer')
@section('page-subtitle', 'Update farmer personal and farm information')

@section('main')
<div class="fams-card">
    <div class="fams-card-header">
        <h6>
            <i class="bi bi-person-badge me-2 text-fams"></i>
            Farmer Information
        </h6>
    </div>

    <div class="fams-card-body">

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                'Nasuje',
                'Montecalvario',
                'N. Roque',
                'Namo',
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

            $selectedBarangay = old('barangay', $farmer->barangay);
            $selectedAssociation = old(
                'farmer_association',
                $farmer->farmer_association
            );
        @endphp

        <form method="POST"
              action="{{ route('admin.farmers.update', $farmer) }}"
              enctype="multipart/form-data">

            @csrf
            @method('PUT')

            <div class="row g-4 align-items-stretch">

                {{-- FULL NAME --}}
                <div class="col-md-6">
                    <label class="form-label" for="farmer-full-name">Full Name</label>
                    <output id="farmer-full-name" class="form-control bg-light" aria-readonly="true">
                        {{ $farmer->full_name }}
                    </output>
                </div>

                {{-- EMAIL --}}
                <div class="col-md-6">
                    <label class="form-label" for="farmer-email">Email</label>
                    <output id="farmer-email" class="form-control bg-light" aria-readonly="true">
                        {{ $farmer->user?->email ?? '—' }}
                    </output>
                </div>

                {{-- CONTACT NUMBER --}}
                <div class="col-md-6">
                    <label class="form-label" for="contact-number">Contact Number</label>
                    <input type="text" name="contact_number"
                           id="contact-number"
                           class="form-control"
                           value="{{ old('contact_number', $farmer->contact_number) }}"
                           placeholder="09XXXXXXXXX"
                           required>
                </div>

                {{-- ADDRESS --}}
                <div class="col-12">
                    <label class="form-label" for="farmer-address">Address</label>
                    <textarea name="address" id="farmer-address" class="form-control"
                              rows="2"
                              required>{{ old('address', $farmer->address) }}</textarea>
                </div>

                {{-- BARANGAY --}}
                <div class="col-md-4">
                    <label class="form-label" for="barangay">Barangay</label>
                    <select name="barangay" id="barangay"
                            class="form-select" required>
                        <option value="">Select Barangay</option>

                        @foreach($barangays as $barangayOption)
                            <option value="{{ $barangayOption }}"
                                {{ $selectedBarangay === $barangayOption ? 'selected' : '' }}>
                                {{ $barangayOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- BIRTHDATE --}}
                <div class="col-md-4">
                    <label class="form-label" for="birthdate">Birthdate</label>
                    <input type="date" name="birthdate"
                           id="birthdate"
                           class="form-control"
                           value="{{ old('birthdate', $farmer->birthdate?->format('Y-m-d')) }}"
                           required>
                </div>

                {{-- CROP TYPE --}}
                <div class="col-md-4">
                    <label class="form-label" for="crop-type">Crop Type</label>
                    <output id="crop-type" class="form-control bg-light" aria-readonly="true">
                        {{ $farmer->crop_type }}
                    </output>
                </div>

                {{-- LAND SIZE --}}
                <div class="col-md-4">
                    <label class="form-label" for="land-size">Land Size (hectares)</label>
                    <input type="number" step="0.01" min="0.01"
                           name="land_size" id="land-size" class="form-control"
                           value="{{ old('land_size', $farmer->land_size) }}"
                           required>
                </div>

                {{-- RSBSA NUMBER --}}
                <div class="col-md-4">
                    <label class="form-label" for="rsbsa-number">RSBSA Number</label>
                    <output id="rsbsa-number" class="form-control bg-light" aria-readonly="true">
                        {{ $farmer->rsbsa_number ?? 'Not provided' }}
                    </output>
                </div>

                {{-- RSBSA STATUS --}}
                <div class="col-md-4">
                    <label class="form-label" for="rsbsa-status">RSBSA Registered Status</label>
                    <output id="rsbsa-status" class="form-control bg-light" aria-readonly="true">
                        {{ $farmer->rsbsa_status ? 'Registered' : 'Not registered' }}
                    </output>
                </div>

                {{-- ASSOCIATION MEMBERSHIP --}}
                <div class="col-12">
                    <div class="form-check py-2">
                        <input type="checkbox"
                               name="association_membership"
                               value="1"
                               class="form-check-input"
                               id="association_membership"
                               {{ old('association_membership', $farmer->association_membership) ? 'checked' : '' }}>
                        <label class="form-check-label text-nowrap"
                               for="association_membership">
                            Association Member
                        </label>
                    </div>
                </div>

                {{-- FARMER ASSOCIATION --}}
                <div class="col-12"
                     id="association-container"
                     style="display: none;">
                    <label class="form-label" for="farmer_association">Farmer Association</label>
                    <select name="farmer_association"
                            id="farmer_association"
                            class="form-select"
                            disabled>
                        <option value="">Select Farmer Association</option>
                        @foreach(config('farmer_associations', []) as $association)
                            <option value="{{ $association }}"
                                {{ $selectedAssociation === $association ? 'selected' : '' }}>
                                {{ $association }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 4PS --}}
                <div class="col-12 col-md-6">
                    <label class="form-label" for="four-ps-membership">4Ps Membership</label>
                    <select name="four_ps_membership" id="four-ps-membership" class="form-select">
                        <option value="0"
                            {{ old('four_ps_membership', $farmer->four_ps_membership ? 1 : 0) == 0 ? 'selected' : '' }}>
                            No
                        </option>
                        <option value="1"
                            {{ old('four_ps_membership', $farmer->four_ps_membership ? 1 : 0) == 1 ? 'selected' : '' }}>
                            Yes
                        </option>
                    </select>
                </div>

                {{-- FARMER PHOTO --}}
                <div class="col-12">
                    <label class="form-label mb-2" for="farmer-photo">Farmer Photo</label>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center bg-success text-white fw-semibold"
                             style="width: 72px; height: 72px;">
                            @if($farmer->farmer_photo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($farmer->farmer_photo_path) }}"
                                     alt="Current Farmer Photo"
                                     class="w-100 h-100 object-fit-cover">
                            @else
                                {{ strtoupper(substr($farmer->full_name, 0, 1)) }}
                            @endif
                        </div>
                        <span class="small text-muted">
                            {{ $farmer->farmer_photo_path ? 'Current profile photo' : 'No photo uploaded yet' }}
                        </span>
                    </div>
                    <input type="file" name="farmer_photo"
                           id="farmer-photo"
                           class="form-control mt-2"
                           accept=".jpg,.jpeg,.png">
                </div>

                {{-- VALID ID --}}
                <div class="col-12">
                    <label class="form-label" for="valid-id">Valid ID</label>
                    <input type="file" name="valid_id"
                           id="valid-id"
                           class="form-control mt-2"
                           accept=".jpg,.jpeg,.png,.pdf">

                    @if($farmer->valid_id_path)
                        <small class="text-muted d-block mt-2">
                            Current Valid ID:
                            <a href="{{ route('admin.farmers.valid-id', $farmer) }}"
                               target="_blank">
                                View ID
                            </a>
                        </small>
                    @endif
                </div>

                {{-- PROFILE STATUS --}}
                <div class="col-md-6">
                    <label class="form-label" for="profile-completed">Profile Status</label>
                    <select name="profile_completed" id="profile-completed" class="form-select">
                        <option value="0"
                            {{ old('profile_completed', $farmer->profile_completed ? 1 : 0) == 0 ? 'selected' : '' }}>
                            Pending
                        </option>
                        <option value="1"
                            {{ old('profile_completed', $farmer->profile_completed ? 1 : 0) == 1 ? 'selected' : '' }}>
                            Verified
                        </option>
                    </select>
                </div>

            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-fams">
                    <i class="bi bi-check-lg me-1"></i>
                    Save Changes
                </button>

                <a href="{{ route('admin.farmers.index') }}"
                   class="btn btn-secondary ms-2">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const membershipCheckbox = document.getElementById('association_membership');
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