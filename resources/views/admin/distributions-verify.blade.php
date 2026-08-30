@extends('layouts.admin')

@section('page-title', 'Verify & Release Benefit')

@section('main')

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">
        <h6 class="mb-0">Farmer Verified</h6>
    </div>

    <div class="card-body">
        <dl class="row mb-0">

            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">
                {{ $farmer->full_name }}
            </dd>

            <dt class="col-sm-3">Barangay</dt>
            <dd class="col-sm-9">
                {{ $farmer->barangay }}
            </dd>

            <dt class="col-sm-3">Crop</dt>
            <dd class="col-sm-9">
                {{ $farmer->crop_type }}
            </dd>

            <dt class="col-sm-3">Contact</dt>
            <dd class="col-sm-9">
                {{ $farmer->contact_number }}
            </dd>

        </dl>
    </div>
</div>


<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">
        <h6 class="mb-0">
            Program Eligibility & Release
        </h6>
    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Program</th>
                        <th>Eligibility</th>
                        <th>Application</th>
                        <th>Missing Requirements</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($programs as $item)

                        <tr>

                            {{-- PROGRAM --}}
                            <td>
                                <strong>
                                    {{ $item['program']->name }}
                                </strong>
                            </td>


                            {{-- ELIGIBILITY --}}
                            <td>

                                @if($item['status'] === 'eligible')

                                    <span class="badge bg-success">
                                        Eligible
                                    </span>

                                @elseif($item['status'] === 'partially_eligible')

                                    <span class="badge bg-warning text-dark">
                                        Partially Eligible
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Not Eligible
                                    </span>

                                @endif

                            </td>


                            {{-- APPLICATION --}}
                            <td>

                                @if(!$item['application'])

                                    <span class="badge bg-secondary">
                                        Not Applied
                                    </span>

                                @elseif($item['application_status'] === 'pending')

                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>

                                @elseif($item['application_status'] === 'approved')

                                    <span class="badge bg-success">
                                        Approved
                                    </span>

                                @elseif($item['application_status'] === 'rejected')

                                    <span class="badge bg-danger">
                                        Rejected
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        {{ ucfirst($item['application_status']) }}
                                    </span>

                                @endif

                            </td>


                            {{-- MISSING REQUIREMENTS --}}
                            <td>

                                @if($item['missing'])

                                    <small>
                                        {{ implode('; ', $item['missing']) }}
                                    </small>

                                @else

                                    <small class="text-success">
                                        None
                                    </small>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td>

                                {{-- ALREADY RELEASED --}}
                                @if($item['already_released'])

                                    <span class="badge bg-secondary">
                                        Released
                                    </span>


                                {{-- ELIGIBLE + APPROVED --}}
                                @elseif(
                                    $item['status'] === 'eligible'
                                    && $item['application_status'] === 'approved'
                                )

                                    <form
                                        method="POST"
                                        action="{{ route('admin.distributions.release') }}"
                                        class="d-inline"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="farmer_id"
                                            value="{{ $farmer->id }}"
                                        >

                                        <input
                                            type="hidden"
                                            name="program_id"
                                            value="{{ $item['program']->id }}"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-fams"
                                            onclick="return confirm('Release this benefit to this farmer?')"
                                        >
                                            <i class="bi bi-box-seam me-1"></i>
                                            Release Benefit
                                        </button>

                                    </form>


                                {{-- ELIGIBLE BUT NOT APPROVED --}}
                                @elseif($item['status'] === 'eligible'
                                    && $item['application_status'] === 'pending'
                                )

                                    <span class="badge bg-warning text-dark">
                                        Waiting for Approval
                                    </span>


                                {{-- ELIGIBLE BUT NOT APPLIED --}}
                                @elseif($item['status'] === 'eligible'
                                    && !$item['application']
                                )

                                    <span class="text-muted small">
                                        Not Applied
                                    </span>


                                {{-- REJECTED APPLICATION --}}
                                @elseif($item['application_status'] === 'rejected')

                                    <span class="text-danger small">
                                        Application Rejected
                                    </span>


                                {{-- PARTIALLY / NOT ELIGIBLE --}}
                                @else

                                    <span class="text-muted small">
                                        Not eligible
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No active programs available.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection