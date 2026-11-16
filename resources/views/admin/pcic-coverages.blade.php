
@extends('layouts.admin')

@section('page-title', 'PCIC Insurance Coverage')
@section('page-subtitle', 'Manage and review farmer insurance records')

@section('main')
<div class="fams-card mb-4">
    <div class="fams-card-header">
        <h6 class="mb-1">
            <i class="bi bi-shield-check me-2 text-fams"></i>
            Register PCIC Insurance Coverage
        </h6>
        <small class="text-muted">
            Record policy information from supporting PCIC documents.
        </small>
    </div>

    <div class="p-3">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pcic-coverages.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Farmer ID</label>
                    <input type="number" name="farmer_id"
                           class="form-control" min="1"
                           value="{{ old('farmer_id') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Policy Number</label>
                    <input type="text" name="policy_number"
                           class="form-control" maxlength="100"
                           value="{{ old('policy_number') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Crop Type</label>
                    <input type="text" name="crop_type"
                           class="form-control" maxlength="100"
                           value="{{ old('crop_type') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Area Insured (hectares)</label>
                    <input type="number" name="area_insured"
                           class="form-control" min="0.01" step="0.01"
                           value="{{ old('area_insured') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Coverage Start Date</label>
                    <input type="date" name="coverage_start_date"
                           class="form-control"
                           value="{{ old('coverage_start_date') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Coverage End Date</label>
                    <input type="date" name="coverage_end_date"
                           class="form-control"
                           value="{{ old('coverage_end_date') }}" required>
                </div>
            </div>

            <button type="submit" class="btn btn-fams mt-3">
                Save as Pending Verification
            </button>
        </form>
    </div>
</div>

<div class="fams-card">
    <div class="fams-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="mb-1">Registered Insurance Policies</h6>
            <small class="text-muted">
                Internal verification is not official PCIC approval.
            </small>
        </div>

        <form method="GET"
              action="{{ route('admin.pcic-coverages.index') }}"
              class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach(['pending', 'verified', 'rejected'] as $status)
                    <option value="{{ $status }}"
                        @selected(request('status') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-fams" type="submit">
                Filter
            </button>
        </form>
    </div>

    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Policy Number</th>
                        <th>Farmer ID</th>
                        <th>Crop</th>
                        <th>Area (ha)</th>
                        <th>Coverage Period</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($coverages as $coverage)
                        <tr>
                            <td>{{ $coverage->policy_number }}</td>
                            <td>{{ $coverage->farmer_id }}</td>
                            <td>{{ $coverage->crop_type }}</td>
                            <td>{{ $coverage->area_insured }}</td>
                            <td>
                                {{ $coverage->coverage_start_date?->format('M d, Y') }}
                                –
                                {{ $coverage->coverage_end_date?->format('M d, Y') }}
                            </td>
                            <td>
                                @php
                                    $badgeClass = match($coverage->verification_status) {
                                        'verified' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        default => 'bg-warning text-dark',
                                    };
                                @endphp

                                <span class="badge {{ $badgeClass }}">
                                    {{ ucfirst($coverage->verification_status) }}
                                </span>
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#coverage-{{ $coverage->id }}">
                                    Details
                                </button>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" class="p-0 border-0">
                                <div class="collapse"
                                     id="coverage-{{ $coverage->id }}">
                                    <div class="p-3 m-2 border rounded">
                                        <h6>Policy Review</h6>

                                        @if($coverage->verification_status === 'pending')
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <form method="POST"
                                                          action="{{ route('admin.pcic-coverages.verify', $coverage) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <div class="form-check mb-2">
                                                            <input type="checkbox"
                                                                   name="verification_confirmed"
                                                                   value="1"
                                                                   class="form-check-input"
                                                                   id="coverage-confirm-{{ $coverage->id }}"
                                                                   required>
                                                            <label class="form-check-label"
                                                                   for="coverage-confirm-{{ $coverage->id }}">
                                                                I checked the policy against supporting PCIC documentation.
                                                            </label>
                                                        </div>

                                                        <button type="submit"
                                                                class="btn btn-sm btn-success">
                                                            Verify Coverage
                                                        </button>
                                                    </form>
                                                </div>

                                                <div class="col-md-6">
                                                    <form method="POST"
                                                          action="{{ route('admin.pcic-coverages.reject', $coverage) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <label class="form-label">
                                                            Rejection Reason
                                                        </label>
                                                        <textarea name="rejection_reason"
                                                                  class="form-control mb-2"
                                                                  rows="3"
                                                                  minlength="10"
                                                                  maxlength="2000"
                                                                  required></textarea>

                                                        <button type="submit"
                                                                class="btn btn-sm btn-danger">
                                                            Reject Coverage
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <p class="mb-1">
                                                <strong>Status:</strong>
                                                {{ ucfirst($coverage->verification_status) }}
                                            </p>
                                            <p class="mb-0">
                                                <strong>Rejection reason:</strong>
                                                {{ $coverage->rejection_reason ?? 'Not applicable' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                class="text-center text-muted py-4">
                                No insurance policies found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $coverages->links() }}
        </div>
    </div>
</div>
@endsection
