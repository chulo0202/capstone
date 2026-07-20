@extends('layouts.admin')

@section('page-title', 'Edit Farmer')

@section('main')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.farmers.update', $farmer) }}">
      @csrf @method('PUT')
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="{{ $farmer->full_name }}" required></div>
        <div class="col-md-6"><label class="form-label">Barangay</label><input type="text" name="barangay" class="form-control" value="{{ $farmer->barangay }}" required></div>
        <div class="col-md-6"><label class="form-label">Crop Type</label><input type="text" name="crop_type" class="form-control" value="{{ $farmer->crop_type }}" required></div>
        <div class="col-md-6"><label class="form-label">Contact Number</label><input type="text" name="contact_number" class="form-control" value="{{ $farmer->contact_number }}"></div>
        <div class="col-md-6"><label class="form-label">Land Size</label><input type="number" step="0.01" name="land_size" class="form-control" value="{{ $farmer->land_size }}"></div>
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select name="profile_completed" class="form-select">
            <option value="0" {{ !$farmer->profile_completed ? 'selected' : '' }}>Pending</option>
            <option value="1" {{ $farmer->profile_completed ? 'selected' : '' }}>Verified</option>
          </select>
        </div>
      </div>
      <button class="btn btn-fams mt-4">Save Changes</button>
    </form>
  </div>
</div>
@endsection
