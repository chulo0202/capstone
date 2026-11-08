@extends('layouts.farmer')

@section('page-title', 'My Profile')
@section('page-subtitle', 'View your personal and farm information')

@section('main')

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
// ---- Derived display values (view-only logic, no schema/route changes) ----
$farmerName   = $farmer?->full_name ?? auth()->user()->name;
$initial      = strtoupper(substr($farmerName ?? 'F', 0, 1));
$hasPhoto     = (bool) ($farmer?->farmer_photo_path);
$hasValidId   = (bool) ($farmer?->valid_id_path);
$photoUrl = $hasPhoto
    ? \Illuminate\Support\Facades\Storage::disk('public')->url($farmer->farmer_photo_path)
    : null;

// Confirmed against app/Models/Farmer.php: the real, cast boolean
// attribute is "four_ps_membership" ($fillable + casts()). Note: the
// 4Ps <select> in the edit form below posts as "4ps_membership" — see
// ProfileController::update(), which currently assigns that submitted
// value to a "4ps_membership" array key that isn't in $fillable, so it
// never actually persists. Flagged separately; not something this view
// can fix on its own.
$fourPsMembership = (bool) ($farmer?->four_ps_membership);

// Profile is considered "complete enough" to show the read-only summary
// when we already have the core fields saved. Falls back gracefully if
// profile_completed hasn't been set on the record yet.
$isComplete = isset($farmer)
    ? ($farmer->profile_completed ?? (bool) ($farmer->contact_number && $farmer->address))
    : false;

// Reopen the edit form automatically if validation failed on submit,
// if the profile isn't complete yet, or if ?edit=1 was requested.
$startInEdit = $errors->any() || request()->boolean('edit') || ! $isComplete;

@endphp

<style>
    /* ---- FAMS Profile Page (scoped, additive styles) ---- */
    .fams-profile-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .5rem;
        margin-bottom: 1.25rem;
    }

    .btn-fams-edit {
        background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
        color: #fff;
        border: none;
        padding: .55rem 1.3rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: .9rem;
        box-shadow: 0 4px 12px rgba(46, 125, 50, .18);
        transition: all .2s ease;
    }
    .btn-fams-edit:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(46, 125, 50, .26); color: #fff; }

    .btn-fams-cancel {
        background: #fff;
        color: #55605a;
        border: 1.5px solid #dde3df;
        padding: .55rem 1.3rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: .9rem;
        transition: all .2s ease;
    }
    .btn-fams-cancel:hover { background: #f4f6f5; color: #2E7D32; border-color: #c9d6cd; }

    /* Overview card */
    .fams-overview-card {
        background: #fff;
        border: 1px solid #e7ece8;
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(27, 94, 32, .06);
        padding: 1.75rem;
        margin-bottom: 1.5rem;
    }

    .fams-avatar-wrap {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        flex-shrink: 0;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
        box-shadow: 0 4px 14px rgba(46, 125, 50, .25);
        border: 3px solid #fff;
        outline: 1px solid #e7ece8;
    }

    .fams-avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .fams-avatar-initial {
        color: #fff;
        font-size: 2.1rem;
        font-weight: 700;
    }

    .fams-overview-name { font-size: 1.25rem; font-weight: 700; color: #1B5E20; margin-bottom: 2px; }
    .fams-overview-role { font-size: .82rem; font-weight: 600; color: #4CAF50; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .85rem; }

    .fams-overview-meta { display: flex; flex-direction: column; gap: .35rem; margin-bottom: .9rem; }
    .fams-overview-meta span {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .88rem;
        color: #55605a;
    }
    .fams-overview-meta i { color: #2E7D32; font-size: .95rem; width: 16px; text-align: center; }

    .fams-badge-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .76rem;
        font-weight: 600;
        padding: .32rem .75rem;
        border-radius: 999px;
        margin-right: .4rem;
        margin-bottom: .3rem;
    }
    .fams-badge-on  { background: #E8F5E9; color: #2E7D32; }
    .fams-badge-off { background: #f1f2f0; color: #8a938d; }

    /* Info section cards */
    .fams-info-card {
        background: #fff;
        border: 1px solid #e7ece8;
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(27, 94, 32, .05);
        padding: 1.6rem 1.75rem;
        margin-bottom: 1.5rem;
    }

    .fams-info-card-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        font-size: .98rem;
        font-weight: 700;
        color: #1B5E20;
        margin-bottom: 1.2rem;
        padding-bottom: .85rem;
        border-bottom: 1px solid #eef2ef;
    }

    .fams-info-card-title i {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #E8F5E9;
        color: #2E7D32;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .fams-info-item { margin-bottom: 1.1rem; }
    .fams-info-label {
        display: block;
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #8a938d;
        margin-bottom: .2rem;
    }
    .fams-info-value {
        font-size: .95rem;
        font-weight: 600;
        color: #262b27;
        word-break: break-word;
    }
    .fams-info-value.text-muted-value { color: #a5aca7; font-weight: 500; font-style: italic; }

    /* Documents section */
    .fams-doc-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        padding: .95rem 1.1rem;
        background: #F7FAF7;
        border: 1px solid #eef2ef;
        border-radius: 14px;
        margin-bottom: 1rem;
    }
    .fams-doc-row:last-child { margin-bottom: 0; }

    .fams-doc-info { display: flex; align-items: center; gap: .75rem; }
    .fams-doc-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        background: #E8F5E9;
        color: #2E7D32;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .fams-doc-title { font-size: .9rem; font-weight: 600; color: #262b27; }
    .fams-doc-sub { font-size: .78rem; color: #8a938d; }

    .fams-doc-photo-thumb {
        width: 84px;
        height: 84px;
        border-radius: 14px;
        object-fit: cover;
        border: 1px solid #e7ece8;
        flex-shrink: 0;
    }

    .btn-view-id {
        background: #fff;
        border: 1.5px solid #2E7D32;
        color: #2E7D32;
        font-weight: 600;
        font-size: .82rem;
        padding: .4rem 1rem;
        border-radius: 9px;
        transition: all .2s ease;
    }
    .btn-view-id:hover { background: #2E7D32; color: #fff; }

    /* Edit-mode current photo preview */
    .fams-edit-photo-current {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: .9rem;
    }
    .fams-edit-photo-current .fams-avatar-wrap { width: 76px; height: 76px; }
    .fams-edit-photo-current .fams-avatar-initial { font-size: 1.6rem; }

    @media (max-width: 767.98px) {
        .fams-overview-card .d-flex-overview { flex-direction: column; text-align: center; align-items: center !important; }
        .fams-overview-meta span { justify-content: center; }
        .fams-profile-toolbar { justify-content: stretch; }
        .fams-profile-toolbar .btn-fams-edit,
        .fams-profile-toolbar .btn-fams-cancel { flex: 1; }
    }
</style>

<div class="fams-profile-toolbar">
    <button type="button" id="btnEditProfile" class="btn-fams-edit" @if($startInEdit) style="display:none;" @endif>
        <i class="bi bi-pencil-square me-1"></i> Edit Profile
    </button>
</div>

{{-- =========================================================
READ-ONLY VIEW
========================================================= --}}

<div id="profileViewMode" @if($startInEdit) style="display:none;" @endif>

{{-- Profile overview --}}
<div class="fams-overview-card">
    <div class="d-flex d-flex-overview align-items-center gap-4 flex-wrap">
        <div class="fams-avatar-wrap">
            @if($hasPhoto)
                <img src="{{ $photoUrl }}" alt="{{ $farmerName }}">
            @else
                <span class="fams-avatar-initial">{{ $initial }}</span>
            @endif
        </div>

        <div class="flex-grow-1">
            <div class="fams-overview-name">{{ $farmerName }}</div>
            <div class="fams-overview-role">Farmer</div>

            <div class="fams-overview-meta">
                <span><i class="bi bi-telephone"></i> {{ $farmer?->contact_number ?? 'Not provided' }}</span>
                <span><i class="bi bi-envelope"></i> {{ auth()->user()->email }}</span>
                <span><i class="bi bi-geo-alt"></i> {{ trim(collect([$farmer?->barangay, $farmer?->address])->filter()->join(', ')) ?: 'Not provided' }}</span>
            </div>

            <div>
                @if($farmer?->rsbsa_status)
                    <span class="fams-badge-status fams-badge-on"><i class="bi bi-patch-check-fill"></i> RSBSA Registered</span>
                @else
                    <span class="fams-badge-status fams-badge-off"><i class="bi bi-patch-check"></i> RSBSA Not Registered</span>
                @endif

                @if($farmer?->association_membership)
                    <span class="fams-badge-status fams-badge-on"><i class="bi bi-people-fill"></i> Association Member</span>
                @else
                    <span class="fams-badge-status fams-badge-off"><i class="bi bi-people"></i> No Association</span>
                @endif

                @if($fourPsMembership)
                    <span class="fams-badge-status fams-badge-on"><i class="bi bi-heart-fill"></i> 4Ps Member</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Personal Information --}}
    <div class="col-lg-6">
        <div class="fams-info-card">
            <div class="fams-info-card-title"><i class="bi bi-person-lines-fill"></i> Personal Information</div>

            <div class="row">
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">Full Name</span>
                    <span class="fams-info-value">{{ $farmerName }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">Contact Number</span>
                    <span class="fams-info-value">{{ $farmer?->contact_number ?? '—' }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">Email</span>
                    <span class="fams-info-value">{{ auth()->user()->email }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">Birthdate</span>
                    <span class="fams-info-value">{{ $farmer?->birthdate?->format('F j, Y') ?? '—' }}</span>
                </div>
                <div class="col-12 fams-info-item">
                    <span class="fams-info-label">Address</span>
                    <span class="fams-info-value">{{ $farmer?->address ?? '—' }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">Barangay</span>
                    <span class="fams-info-value">{{ $farmer?->barangay ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Farm Information --}}
    <div class="col-lg-6">
        <div class="fams-info-card">
            <div class="fams-info-card-title"><i class="bi bi-tree-fill"></i> Farm Information</div>

            <div class="row">
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">Crop Type</span>
                    <span class="fams-info-value">{{ $farmer?->crop_type ?? '—' }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">Land Size</span>
                    <span class="fams-info-value">{{ $farmer?->land_size !== null ? number_format($farmer->land_size, 2) . ' hectares' : '—' }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">RSBSA Number</span>
                    <span class="fams-info-value {{ $farmer?->rsbsa_number ? '' : 'text-muted-value' }}">{{ $farmer?->rsbsa_number ?? 'Not provided' }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">RSBSA Status</span>
                    <span class="fams-info-value">{{ $farmer?->rsbsa_status ? 'Registered' : 'Not Registered' }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">Association Membership</span>
                    <span class="fams-info-value">{{ $farmer?->association_membership ? 'Yes' : 'No' }}</span>
                </div>
                <div class="col-md-6 fams-info-item">
                    <span class="fams-info-label">4Ps Membership</span>
                    <span class="fams-info-value">{{ $fourPsMembership ? 'Yes' : 'No' }}</span>
                </div>
                <div class="col-12 fams-info-item">
                    <span class="fams-info-label">Farmer Association</span>
                    <span class="fams-info-value {{ $farmer?->farmer_association ? '' : 'text-muted-value' }}">{{ $farmer?->farmer_association ?? 'Not a member of any association' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Documents & Photo --}}
<div class="fams-info-card mb-0">
    <div class="fams-info-card-title"><i class="bi bi-folder2-open"></i> Documents &amp; Photo</div>

    <div class="fams-doc-row">
        <div class="fams-doc-info">
            <div class="fams-doc-icon"><i class="bi bi-card-image"></i></div>
            <div>
                <div class="fams-doc-title">Valid ID</div>
                <div class="fams-doc-sub">{{ $hasValidId ? 'Valid ID Uploaded' : 'No Valid ID Uploaded' }}</div>
            </div>
        </div>
        @if($hasValidId)
            <a href="{{ route('farmer.profile.valid-id') }}" target="_blank" rel="noopener" class="btn-view-id">
                <i class="bi bi-box-arrow-up-right me-1"></i> View Valid ID
            </a>
        @endif
    </div>

    <div class="fams-doc-row">
        <div class="fams-doc-info">
            <div class="fams-doc-icon"><i class="bi bi-person-square"></i></div>
            <div>
                <div class="fams-doc-title">Farmer Photo</div>
                <div class="fams-doc-sub">{{ $hasPhoto ? 'Photo on file' : 'No photo uploaded' }}</div>
            </div>
        </div>
        @if($hasPhoto)
            <img src="{{ $photoUrl }}" alt="Farmer Photo" class="fams-doc-photo-thumb">
        @endif
    </div>
</div>

</div>

{{-- =========================================================
EDIT MODE (existing form — fields, route, and validation
preserved exactly as-is)
========================================================= --}}

<div id="profileEditMode" @unless($startInEdit) style="display:none;" @endunless>
    <div class="fams-card">
        <div class="fams-card-header">
            <h6><i class="bi bi-person-badge me-2 text-fams"></i>Farmer Profile</h6>
        </div>
        <div class="fams-card-body">
            <form method="POST" action="{{ route('farmer.profile.update') }}" enctype="multipart/form-data" id="famsProfileEditForm">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $farmer?->full_name ?? auth()->user()->name) }}" required>
                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $farmer?->contact_number) }}" placeholder="09XXXXXXXXX" required>
                        @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $farmer?->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                   <div class="col-md-4">
    <label class="form-label">Barangay <span class="text-danger">*</span></label>

    <select name="barangay" class="form-select @error('barangay') is-invalid @enderror" required>
        <option value="">Select Barangay</option>

        @foreach([
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
            'Montecalvario',
            'Namo',
            'Nasuje',
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
            'Zone VIII Pob.'
        ] as $barangay)
            <option value="{{ $barangay }}"
                {{ old('barangay', $farmer?->barangay) === $barangay ? 'selected' : '' }}>
                {{ $barangay }}
            </option>
        @endforeach
    </select>

    @error('barangay')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
                    <div class="col-md-4">
                        <label class="form-label">Birthdate <span class="text-danger">*</span></label>
                        <input type="date" name="birthdate" class="form-control @error('birthdate') is-invalid @enderror" value="{{ old('birthdate', $farmer?->birthdate?->format('Y-m-d')) }}" required>
                        @error('birthdate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Crop Type <span class="text-danger">*</span></label>
                        <select name="crop_type" class="form-select @error('crop_type') is-invalid @enderror" required>
                            @foreach(['Rice','Corn','Vegetables','Fruits','Coconut','Other'] as $crop)
                            <option value="{{ $crop }}" {{ old('crop_type', $farmer?->crop_type) === $crop ? 'selected' : '' }}>{{ $crop }}</option>
                            @endforeach
                        </select>
                        @error('crop_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Land Size (hectares) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="land_size" class="form-control @error('land_size') is-invalid @enderror" value="{{ old('land_size', $farmer?->land_size) }}" required>
                        @error('land_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4" id="rsbsa-number-container">
    <label class="form-label">RSBSA Number</label>

    <input
        type="text"
        name="rsbsa_number"
        id="rsbsa_number"
        class="form-control @error('rsbsa_number') is-invalid @enderror"
        value="{{ old('rsbsa_number', $farmer?->rsbsa_number) }}"
        placeholder="Enter RSBSA Number"
    >

    @error('rsbsa_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
                    <div class="col-md-4 d-flex align-items-end gap-4 pb-2">
    <div class="form-check">
        <input
            type="checkbox"
            name="rsbsa_status"
            value="1"
            class="form-check-input @error('rsbsa_status') is-invalid @enderror"
            id="rsbsa"
            {{ old('rsbsa_status', $farmer?->rsbsa_status) ? 'checked' : '' }}
            required
        >

        <label class="form-check-label" for="rsbsa">
            RSBSA Registered <span class="text-danger">*</span>
        </label>

        @error('rsbsa_status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-check">
        <input
            type="checkbox"
            name="association_membership"
            value="1"
            class="form-check-input"
            id="assoc"
            {{ old('association_membership', $farmer?->association_membership) ? 'checked' : '' }}
        >

        <label class="form-check-label" for="assoc">
            Association Member
        </label>
    </div>
</div>
                    <div class="col-md-4">
                        <label class="form-label">4Ps Membership</label>
                        <select name="four_ps_membership" class="form-select">
                            <option value="0" {{ old('four_ps_membership', $fourPsMembership ? 1 : 0) == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('four_ps_membership', $fourPsMembership ? 1 : 0) == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Farmer Association</label>
                        <input type="text" name="farmer_association" class="form-control" value="{{ old('farmer_association', $farmer?->farmer_association) }}" placeholder="Association name">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Valid ID Upload</label>
                        <input type="file" name="valid_id" class="form-control @error('valid_id') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                        @error('valid_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($farmer?->valid_id_path)
                        <small class="text-muted mt-1 d-block">Current file: <a href="{{ route('farmer.profile.valid-id') }}" target="_blank" class="auth-link">View uploaded ID</a></small>
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label">Farmer Photo</label>

                    <div class="fams-edit-photo-current">
                        <div class="fams-avatar-wrap">
                            @if($hasPhoto)
                                <img src="{{ $photoUrl }}" alt="Current Farmer Photo">
                            @else
                                <span class="fams-avatar-initial">{{ $initial }}</span>
                            @endif
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">{{ $hasPhoto ? 'Current Profile Photo' : 'No photo uploaded yet' }}</p>
                        </div>
                    </div>

                    <input
                        type="file"
                        name="farmer_photo"
                        class="form-control @error('farmer_photo') is-invalid @enderror"
                        accept=".jpg,.jpeg,.png"
                    >

                    @error('farmer_photo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Change Password</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="password" name="password" class="form-control" placeholder="New password">
                        </div>
                        <div class="col-md-6">
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password">
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-fams"><i class="bi bi-check-lg me-1"></i>Save Profile</button>
                <button type="button" id="btnCancelEdit" class="btn-fams-cancel"><i class="bi bi-x-lg me-1"></i>Cancel</button>
            </div>
        </form>
    </div>
</div>

</div>

<script>
    (function () {
        var viewMode = document.getElementById('profileViewMode');
        var editMode = document.getElementById('profileEditMode');
        var btnEdit = document.getElementById('btnEditProfile');
        var btnCancel = document.getElementById('btnCancelEdit');
        var form = document.getElementById('famsProfileEditForm');

        // RSBSA fields
        var rsbsaCheckbox = document.getElementById('rsbsa');
        var rsbsaContainer = document.getElementById('rsbsa-number-container');
        var rsbsaNumber = document.getElementById('rsbsa_number');

        function showEdit() {
            if (viewMode) viewMode.style.display = 'none';
            if (editMode) editMode.style.display = 'block';
            if (btnEdit) btnEdit.style.display = 'none';

            toggleRsbsaNumber();
        }

        function showView() {
            if (editMode) editMode.style.display = 'none';
            if (viewMode) viewMode.style.display = 'block';
            if (btnEdit) btnEdit.style.display = 'inline-flex';
        }

        function toggleRsbsaNumber() {
            if (!rsbsaCheckbox || !rsbsaContainer) return;

            if (rsbsaCheckbox.checked) {
                rsbsaContainer.style.display = 'block';
            } else {
                rsbsaContainer.style.display = 'none';

                if (rsbsaNumber) {
                    rsbsaNumber.value = '';
                }
            }
        }

        if (btnEdit) {
            btnEdit.addEventListener('click', showEdit);
        }

        if (btnCancel) {
            btnCancel.addEventListener('click', function () {
                if (form) form.reset();

                toggleRsbsaNumber();
                showView();
            });
        }

        if (rsbsaCheckbox) {
            rsbsaCheckbox.addEventListener('change', toggleRsbsaNumber);
        }

        // Set initial state
        toggleRsbsaNumber();
    })();
</script>

@endsection