@extends('layouts.farmer')

@section('page-title', 'My QR Code')
@section('page-subtitle', 'Present this code at the MAO office for benefit verification')

@section('main')
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="fams-card text-center">
      <div class="fams-card-body p-4 p-md-5">
        <div class="fams-user-avatar mx-auto mb-3" style="width:56px;height:56px;font-size:1.25rem;">
          {{ strtoupper(substr($farmer->full_name, 0, 1)) }}
        </div>
        <h5 class="fw-bold mb-1">{{ $farmer->full_name }}</h5>
        <p class="text-muted small mb-4">Farmer ID #{{ str_pad($farmer->id, 5, '0', STR_PAD_LEFT) }}</p>

        @if($farmer->qr_code_path && file_exists(public_path('storage/'.$farmer->qr_code_path)))
        <div class="p-3 bg-white border rounded-3 d-inline-block mb-4" style="box-shadow:var(--fams-shadow);">
          <img src="{{ asset('storage/'.$farmer->qr_code_path) }}" alt="QR Code" class="img-fluid" style="max-width:240px;">
        </div>
        @else
        <div class="alert fams-alert fams-alert-warning mb-4">
          QR code not generated. Re-save your profile with GD extension enabled.
        </div>
        @endif

        <p class="text-muted small mb-4">Scan this code during benefit distribution to verify your identity and eligibility.</p>

        @if($farmer->qr_code_path)
        <a href="{{ route('farmer.qr-code.download') }}" class="btn btn-fams">
          <i class="bi bi-download me-1"></i>Download QR Code
        </a>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
