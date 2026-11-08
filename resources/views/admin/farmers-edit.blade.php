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

        <form method="POST"
              action="{{ route('admin.farmers.update', $farmer) }}"
              enctype="multipart/form-data">

            @csrf
            @method('PUT')

            <div class="row g-3">

                {{-- FULL NAME --}}
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input
                        type="text"
                        name="full_name"
                        class="form-control"
                        value="{{ old('full_name', $farmer->full_name) }}"
                        required
                    >
                </div>

                {{-- CONTACT NUMBER --}}
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input
                        type="text"
                        name="contact_number"
                        class="form-control"
                        value="{{ old('contact_number', $farmer->contact_number) }}"
                        placeholder="09XXXXXXXXX"
                        required
                    >
                </div>

                {{-- ADDRESS --}}
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea
                        name="address"
                        class="form-control"
                        rows="2"
                        required
                    >{{ old('address', $farmer->address) }}</textarea>
                </div>

                {{-- BARANGAY --}}
                <div class="col-md-4">
                    <label class="form-label">Barangay</label>
                    <input
                        type="text"
                        name="barangay"
                        class="form-control"
                        value="{{ old('barangay', $farmer->barangay) }}"
                        required
                    >
                </div>

                {{-- BIRTHDATE --}}
                <div class="col-md-4">
                    <label class="form-label">Birthdate</label>
                    <input
                        type="date"
                        name="birthdate"
                        class="form-control"
                        value="{{ old('birthdate', $farmer->birthdate?->format('Y-m-d')) }}"
                        required
                    >
                </div>

                {{-- CROP TYPE --}}
                <div class="col-md-4">
                    <label class="form-label">Crop Type</label>
                    <select name="crop_type" class="form-select" required>
                        @foreach(['Rice','Corn','Vegetables','Fruits','Coconut','Other'] as $crop)
                            <option
                                value="{{ $crop }}"
                                {{ old('crop_type', $farmer->crop_type) === $crop ? 'selected' : '' }}
                            >
                                {{ $crop }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- LAND SIZE --}}
                <div class="col-md-4">
                    <label class="form-label">Land Size (hectares)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="land_size"
                        class="form-control"
                        value="{{ old('land_size', $farmer->land_size) }}"
                        required
                    >
                </div>

                {{-- RSBSA NUMBER --}}
                <div class="col-md-4">
                    <label class="form-label">RSBSA Number</label>
                    <input
                        type="text"
                        name="rsbsa_number"
                        class="form-control"
                        value="{{ old('rsbsa_number', $farmer->rsbsa_number) }}"
                        placeholder="Optional"
                    >
                </div>

                {{-- RSBSA STATUS --}}
                <div class="col-md-4 d-flex align-items-end pb-2">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            name="rsbsa_status"
                            value="1"
                            class="form-check-input"
                            id="rsbsa_status"
                            {{ old('rsbsa_status', $farmer->rsbsa_status) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="rsbsa_status">
                            RSBSA Registered
                        </label>
                    </div>
                </div>

                {{-- ASSOCIATION MEMBERSHIP --}}
                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input
                            type="checkbox"
                            name="association_membership"
                            value="1"
                            class="form-check-input"
                            id="association_membership"
                            {{ old('association_membership', $farmer->association_membership) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="association_membership">
                            Association Member
                        </label>
                    </div>
                </div>

                {{-- 4PS --}}
                <div class="col-md-4">
                    <label class="form-label">4Ps Membership</label>

                    <select name="four_ps_membership" class="form-select">
                        <option
                            value="0"
                            {{ old('four_ps_membership', $farmer->four_ps_membership ? 1 : 0) == 0 ? 'selected' : '' }}
                        >
                            No
                        </option>

                        <option
                            value="1"
                            {{ old('four_ps_membership', $farmer->four_ps_membership ? 1 : 0) == 1 ? 'selected' : '' }}
                        >
                            Yes
                        </option>
                    </select>
                </div>

                {{-- FARMER ASSOCIATION --}}
                <div class="col-md-4">
                    <label class="form-label">Farmer Association</label>

                    <input
                        type="text"
                        name="farmer_association"
                        class="form-control"
                        value="{{ old('farmer_association', $farmer->farmer_association) }}"
                        placeholder="Association name"
                    >
                </div>

                {{-- VALID ID --}}
                <div class="col-md-6">
                    <label class="form-label">Valid ID</label>

                    <input
                        type="file"
                        name="valid_id"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.pdf"
                    >

                    @if($farmer->valid_id_path)
                        <small class="text-muted d-block mt-2">
                            Current Valid ID:
                            <a
                                href="{{ route('admin.farmers.valid-id', $farmer) }}"
                                target="_blank"
                            >
                                View ID
                            </a>
                        </small>
                    @endif
                </div>

                {{-- FARMER PHOTO --}}
                <div class="col-md-6">
                    <label class="form-label">Farmer Photo</label>

                    <input
                        type="file"
                        name="farmer_photo"
                        class="form-control"
                        accept=".jpg,.jpeg,.png"
                    >

                    @if($farmer->farmer_photo_path)
                        <small class="text-muted d-block mt-2">
                            Current farmer photo is available.
                        </small>
                    @endif
                </div>

                {{-- PROFILE STATUS --}}
                <div class="col-md-6">
                    <label class="form-label">Profile Status</label>

                    <select name="profile_completed" class="form-select">
                        <option
                            value="0"
                            {{ old('profile_completed', $farmer->profile_completed ? 1 : 0) == 0 ? 'selected' : '' }}
                        >
                            Pending
                        </option>

                        <option
                            value="1"
                            {{ old('profile_completed', $farmer->profile_completed ? 1 : 0) == 1 ? 'selected' : '' }}
                        >
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

                <a
                    href="{{ route('admin.farmers.index') }}"
                    class="btn btn-secondary ms-2"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>
</div>
@endsection