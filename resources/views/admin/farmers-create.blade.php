@extends('layouts.admin')

@section('page-title', 'Add Farmer')

@section('main')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.farmers.store') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Account Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Barangay</label><input type="text" name="barangay" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Crop Type</label><input type="text" name="crop_type" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Contact Number</label><input type="text" name="contact_number" class="form-control"></div>
      </div>
      <button class="btn btn-fams mt-4">Save Farmer</button>
    </form>
  </div>
</div>
@endsection
