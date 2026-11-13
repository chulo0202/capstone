@extends('layouts.admin')

@section('page-title', 'Add Farmer')

@section('main')
<div class="card border-0 shadow-sm">
    <div class="card-body">

        <form method="POST"
              action="{{ route('admin.farmers.store') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="row g-3">

                {{-- ACCOUNT INFORMATION --}}
                <div class="col-12">
                    <h6 class="text-fams border-bottom pb-2">
                        <i class="bi bi-person-circle me-2"></i>
                        Account Information
                    </h6>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Account Name</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password"
                           class="form-control" required>
                </div>

                {{-- FARMER INFORMATION --}}
                <div class="col-12 mt-4">
                    <h6 class="text-fams border-bottom pb-2">
                        <i class="bi bi-person-badge me-2"></i>
                        Farmer Information
                    </h6>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           value="{{ old('full_name') }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number"
                           class="form-control"
                           value="{{ old('contact_number') }}"
                           placeholder="09XXXXXXXXX" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"
                              rows="2" required>{{ old('address') }}</textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Barangay</label>
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
                </div>

                <div class="col-md-4">
                    <label class="form-label">Birthdate</label>
                    <input type="date" name="birthdate" class="form-control"
                           value="{{ old('birthdate') }}" required>
                </div>

                {{-- FARM INFORMATION --}}
                <div class="col-12 mt-4">
                    <h6 class="text-fams border-bottom pb-2">
                        <i class="bi bi-tree me-2"></i>
                        Farm Information
                    </h6>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Crop Type</label>
                    <select name="crop_type" class="form-select" required>
                        <option value="">Select Crop</option>

                        @foreach(['Rice','Corn','Vegetables','Fruits','Coconut','Other'] as $crop)
                            <option value="{{ $crop }}"
                                {{ old('crop_type') === $crop ? 'selected' : '' }}>
                                {{ $crop }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Land Size (hectares)</label>
                    <input type="number" name="land_size" class="form-control"
                           value="{{ old('land_size') }}"
                           step="0.01" min="0"
                           placeholder="e.g. 0.50" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">RSBSA Number</label>
                    <input type="text" name="rsbsa_number" class="form-control"
                           value="{{ old('rsbsa_number') }}"
                           placeholder="Enter RSBSA Number" required>
                </div>

                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="rsbsa_status"
                               value="1" class="form-check-input"
                               id="rsbsa"
                               {{ old('rsbsa_status') ? 'checked' : '' }}>
                        <label class="form-check-label" for="rsbsa">
                            RSBSA Registered
                        </label>
                    </div>
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
                </div>

                {{-- DOCUMENTS --}}
                <div class="col-12 mt-4">
                    <h6 class="text-fams border-bottom pb-2">
                        <i class="bi bi-file-earmark me-2"></i>
                        Documents
                    </h6>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Valid ID</label>
                    <input type="file" name="valid_id" class="form-control"
                           accept=".jpg,.jpeg,.png,.pdf">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Farmer Photo</label>
                    <input type="file" name="farmer_photo" class="form-control"
                           accept=".jpg,.jpeg,.png">
                </div>

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