@extends('layouts.admin')

@section('page-title', 'Verify & Release Benefit')

@section('main')
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white"><h6 class="mb-0">Farmer Verified</h6></div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $farmer->full_name }}</dd>
      <dt class="col-sm-3">Barangay</dt><dd class="col-sm-9">{{ $farmer->barangay }}</dd>
      <dt class="col-sm-3">Crop</dt><dd class="col-sm-9">{{ $farmer->crop_type }}</dd>
      <dt class="col-sm-3">Contact</dt><dd class="col-sm-9">{{ $farmer->contact_number }}</dd>
    </dl>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white"><h6 class="mb-0">Program Eligibility & Release</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Program</th><th>Status</th><th>Missing</th><th>Action</th></tr></thead>
        <tbody>
          @foreach($programs as $item)
          <tr>
            <td>{{ $item['program']->name }}</td>
            <td>
              <span class="badge bg-{{ $item['status'] === 'eligible' ? 'success' : ($item['status'] === 'partially_eligible' ? 'warning' : 'danger') }}">
                {{ str_replace('_', ' ', ucfirst($item['status'])) }}
              </span>
            </td>
            <td><small>{{ $item['missing'] ? implode('; ', $item['missing']) : 'None' }}</small></td>
            <td>
              @if($item['already_released'])
                <span class="badge bg-secondary">Already Released</span>
              @elseif($item['status'] === 'eligible')
                <form method="POST" action="{{ route('admin.distributions.release') }}" class="d-inline">
                  @csrf
                  <input type="hidden" name="farmer_id" value="{{ $farmer->id }}">
                  <input type="hidden" name="program_id" value="{{ $item['program']->id }}">
                  <button class="btn btn-sm btn-fams">Release Benefit</button>
                </form>
              @else
                <span class="text-muted small">Not eligible</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
