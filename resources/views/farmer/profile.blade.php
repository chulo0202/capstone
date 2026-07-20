@extends('layouts.farmer')

@section('page-title', 'My Profile')
@section('page-subtitle', 'Manage your personal and farm information')

@section('main')
<div class="fams-card">
  <div class="fams-card-header">
    <h6><i class="bi bi-person-badge me-2 text-fams"></i>Farmer Profile</h6>
  </div>
  <div class="fams-card-body">
    <form method="POST" action="{{ route('farmer.profile.update') }}" enctype="multipart/form-data">
      @csrf @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $farmer?->full_name ?? auth()->user()->name) }}" required>
          @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
          <label class="form-label">Contact Number</label>
          <input type="text" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $farmer?->contact_number) }}" placeholder="09XXXXXXXXX" required>
          @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $farmer?->address) }}</textarea>
          @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Barangay</label>
          <input type="text" name="barangay" class="form-control @error('barangay') is-invalid @enderror" value="{{ old('barangay', $farmer?->barangay) }}" required>
          @error('barangay')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Birthdate</label>
          <input type="date" name="birthdate" class="form-control @error('birthdate') is-invalid @enderror" value="{{ old('birthdate', $farmer?->birthdate?->format('Y-m-d')) }}" required>
          @error('birthdate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Crop Type</label>
          <select name="crop_type" class="form-select @error('crop_type') is-invalid @enderror" required>
            @foreach(['Rice','Corn','Vegetables','Fruits','Coconut','Other'] as $crop)
            <option value="{{ $crop }}" {{ old('crop_type', $farmer?->crop_type) === $crop ? 'selected' : '' }}>{{ $crop }}</option>
            @endforeach
          </select>
          @error('crop_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Land Size (hectares)</label>
          <input type="number" step="0.01" name="land_size" class="form-control @error('land_size') is-invalid @enderror" value="{{ old('land_size', $farmer?->land_size) }}" required>
          @error('land_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">RSBSA Number</label>
          <input type="text" name="rsbsa_number" class="form-control" value="{{ old('rsbsa_number', $farmer?->rsbsa_number) }}" placeholder="Optional">
        </div>
        <div class="col-md-4 d-flex align-items-end gap-4 pb-2">
          <div class="form-check">
            <input type="checkbox" name="rsbsa_status" value="1" class="form-check-input" id="rsbsa" {{ old('rsbsa_status', $farmer?->rsbsa_status) ? 'checked' : '' }}>
            <label class="form-check-label" for="rsbsa">RSBSA Registered</label>
          </div>
          <div class="form-check">
            <input type="checkbox" name="association_membership" value="1" class="form-check-input" id="assoc" {{ old('association_membership', $farmer?->association_membership) ? 'checked' : '' }}>
            <label class="form-check-label" for="assoc">Association Member</label>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">4Ps Membership</label>
          <select name="4ps_membership" class="form-select">
            <option value="0" {{ old('4ps_membership', $farmer?->four_ps_membership ? 1 : 0) == 0 ? 'selected' : '' }}>No</option>
            <option value="1" {{ old('4ps_membership', $farmer?->four_ps_membership ? 1 : 0) == 1 ? 'selected' : '' }}>Yes</option>
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
          <small class="text-muted mt-1 d-block">Current file: <a href="{{ asset('storage/'.$farmer->valid_id_path) }}" target="_blank" class="auth-link">View uploaded ID</a></small>
          @endif
        </div>
        <div class="col-12">
          <label class="form-label">Farmer Photo</label>
          <input type="file" name="farmer_photo" class="form-control @error('farmer_photo') is-invalid @enderror" accept=".jpg,.jpeg,.png">
          @error('farmer_photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          @if($farmer?->farmer_photo_path)
          <small class="text-muted mt-1 d-block">Current photo available</small>
          @endif
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
      <div class="mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-fams"><i class="bi bi-check-lg me-1"></i>Save Profile</button>
      </div>
    </form>
  </div>
</div>
@endsection
