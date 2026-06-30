@extends('layouts.admin')

@section('page-title', 'Eligibility Rules')

@section('main')
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Set Eligibility Rules</h6></div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.eligibility.store') }}">
          @csrf
          <div class="mb-2">
            <label class="form-label">Program</label>
            <select name="program_id" class="form-select" required>
              <option value="">Select program</option>
              @foreach($programs as $program)
              <option value="{{ $program->id }}">{{ $program->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Allowed Crop Types (comma-separated)</label>
            <input type="text" name="crop_types_input" class="form-control" placeholder="Rice, Corn, Vegetables">
          </div>
          <div class="row">
            <div class="col-6 mb-2">
              <label class="form-label">Min Land (ha)</label>
              <input type="number" step="0.01" name="min_land_size" class="form-control">
            </div>
            <div class="col-6 mb-2">
              <label class="form-label">Max Land (ha)</label>
              <input type="number" step="0.01" name="max_land_size" class="form-control">
            </div>
          </div>
          <div class="form-check mb-2">
            <input type="checkbox" name="requires_rsbsa" value="1" class="form-check-input" id="rsbsa">
            <label class="form-check-label" for="rsbsa">Requires RSBSA</label>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" name="requires_association" value="1" class="form-check-input" id="assoc">
            <label class="form-check-label" for="assoc">Requires Association Membership</label>
          </div>
          <button class="btn btn-fams w-100">Save Rules</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Current Rules</h6></div>
      <div class="list-group list-group-flush">
        @forelse($programs->whereNotNull('eligibilityRule') as $program)
        <div class="list-group-item">
          <div class="d-flex justify-content-between">
            <strong>{{ $program->name }}</strong>
            <form action="{{ route('admin.eligibility.destroy', $program->eligibilityRule) }}" method="POST" onsubmit="return confirm('Delete rules?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
          </div>
          <small class="text-muted">
            Crops: {{ $program->eligibilityRule->crop_types ? implode(', ', $program->eligibilityRule->crop_types) : 'Any' }} |
            Land: {{ $program->eligibilityRule->min_land_size ?? '0' }}-{{ $program->eligibilityRule->max_land_size ?? '∞' }} ha |
            RSBSA: {{ $program->eligibilityRule->requires_rsbsa ? 'Yes' : 'No' }} |
            Association: {{ $program->eligibilityRule->requires_association ? 'Yes' : 'No' }}
          </small>
        </div>
        @empty
        <div class="list-group-item text-muted">No eligibility rules configured</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
