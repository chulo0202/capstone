@extends('layouts.admin')

@section('page-title', 'Farmer Details')

@section('main')
<div class="mb-3"><a href="{{ route('admin.farmers.index') }}" class="btn btn-sm btn-outline-secondary">&larr; Back</a></div>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Profile Information</h6></div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Full Name</dt><dd class="col-sm-8">{{ $farmer->full_name }}</dd>
          <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $farmer->address }}</dd>
          <dt class="col-sm-4">Barangay</dt><dd class="col-sm-8">{{ $farmer->barangay }}</dd>
          <dt class="col-sm-4">Contact</dt><dd class="col-sm-8">{{ $farmer->contact_number }}</dd>
          <dt class="col-sm-4">Crop Type</dt><dd class="col-sm-8">{{ $farmer->crop_type }}</dd>
          <dt class="col-sm-4">Land Size</dt><dd class="col-sm-8">{{ $farmer->land_size }} ha</dd>
          <dt class="col-sm-4">RSBSA</dt><dd class="col-sm-8">{{ $farmer->rsbsa_status ? 'Yes ('.$farmer->rsbsa_number.')' : 'No' }}</dd>
          <dt class="col-sm-4">Association</dt><dd class="col-sm-8">{{ $farmer->association_membership ? 'Member' : 'Non-member' }}</dd>
        </dl>
        @if($farmer->qr_code_path)
        <img src="{{ asset('storage/'.$farmer->qr_code_path) }}" alt="QR Code" class="mt-3" style="max-width:150px;">
        @endif
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white"><h6 class="mb-0">Program Recommendations</h6></div>
      <ul class="list-group list-group-flush">
        @forelse($farmer->recommendations as $rec)
        <li class="list-group-item d-flex justify-content-between">
          <span>{{ $rec->program->name }}</span>
          <span class="badge bg-{{ $rec->eligibility_status === 'eligible' ? 'success' : ($rec->eligibility_status === 'partially_eligible' ? 'warning' : 'danger') }}">
            {{ str_replace('_', ' ', ucfirst($rec->eligibility_status)) }}
          </span>
        </li>
        @empty
        <li class="list-group-item text-muted">No recommendations</li>
        @endforelse
      </ul>
    </div>
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Distribution History</h6></div>
      <ul class="list-group list-group-flush">
        @forelse($farmer->distributions as $dist)
        <li class="list-group-item">{{ $dist->program->name }} - {{ $dist->distributed_at->format('M d, Y') }}</li>
        @empty
        <li class="list-group-item text-muted">No distributions</li>
        @endforelse
      </ul>
    </div>
  </div>
</div>
@endsection
