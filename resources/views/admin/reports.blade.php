@extends('layouts.admin')

@section('page-title', 'Reports')

@section('main')
<div class="row g-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6>Total Farmers</h6>
        <h3>{{ $totalFarmers }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6>Total Programs</h6>
        <h3>{{ $totalPrograms }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6>Total Distributions</h6>
        <h3>{{ $totalDistributions }}</h3>
      </div>
    </div>
  </div>
</div>
<div class="mt-4">
  <a href="{{ route('admin.reports.pdf') }}" class="btn btn-fams">Export PDF</a>
  <a href="{{ route('admin.reports.excel') }}" class="btn btn-outline-secondary">Export Excel</a>
</div>
@endsection
