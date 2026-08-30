@extends('layouts.farmer')

@section('page-title', 'Program Recommendations')

@section('main')

@if(!$farmer || !$farmer->profile_completed)

<div class="alert alert-warning">
    Complete your
    <a href="{{ route('farmer.profile') }}">profile</a>
    to get program recommendations.
</div>

@else

<div class="table-responsive">

    <table class="table table-hover bg-white shadow-sm rounded">

        <thead class="table-light">

            <tr>
                <th>Program</th>
                <th>Description</th>
                <th>Status</th>
                <th>Missing Requirements</th>
                <th>Application</th>
            </tr>

        </thead>

        <tbody>

            @forelse($recommendations as $rec)

            @php

                $programId = $rec->program->id;

                $application = $applications->get($programId);

                $distribution = $distributions->get($programId);

            @endphp

            <tr>

                {{-- PROGRAM --}}
                <td>
                    <strong>
                        {{ $rec->program->name }}
                    </strong>
                </td>


                {{-- DESCRIPTION --}}
                <td>
                    <small>
                        {{ Str::limit($rec->program->description, 80) }}
                    </small>
                </td>


                {{-- ELIGIBILITY --}}
                <td>

                    <span class="badge bg-{{
                        $rec->eligibility_status === 'eligible'
                            ? 'success'
                            : ($rec->eligibility_status === 'partially_eligible'
                                ? 'warning text-dark'
                                : 'danger')
                    }}">

                        {{ str_replace('_', ' ', ucfirst($rec->eligibility_status)) }}

                    </span>

                </td>


                {{-- MISSING REQUIREMENTS --}}
                <td>

                    <small>

                        {{
                            $rec->missing_requirements
                                ? implode('; ', $rec->missing_requirements)
                                : 'None'
                        }}

                    </small>

                </td>


                {{-- APPLICATION / DISTRIBUTION STATUS --}}
                <td>

                    {{-- BENEFIT ALREADY RECEIVED --}}
                    @if($distribution)

                        <span class="badge bg-success">

                            <i class="bi bi-check-circle-fill me-1"></i>
                            Benefit Received

                        </span>


                    {{-- APPLICATION EXISTS --}}
                    @elseif($application)

                        {{-- PENDING --}}
                        @if($application->status === 'pending')

                            <span class="badge bg-warning text-dark">

                                Pending Approval

                            </span>


                        {{-- APPROVED --}}
                        @elseif($application->status === 'approved')

                            <span class="badge bg-primary">

                                Approved

                            </span>

                            <div class="small text-muted mt-1">

                                Ready for distribution

                            </div>


                        {{-- REJECTED --}}
                        @elseif($application->status === 'rejected')

                            <span class="badge bg-danger">

                                Rejected

                            </span>


                        {{-- OTHER STATUS --}}
                        @else

                            <span class="badge bg-secondary">

                                {{ ucfirst($application->status) }}

                            </span>

                        @endif


                    {{-- NO APPLICATION YET --}}
                    @else

                        <form
                            method="POST"
                            action="{{ route('farmer.applications.store', $rec->program) }}"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="btn btn-sm btn-fams"
                            >

                                Apply

                            </button>

                        </form>

                    @endif

                </td>

            </tr>

            @empty

            <tr>

                <td colspan="5" class="text-center text-muted py-4">

                    No recommendations available

                </td>

            </tr>

            @endforelse

        </tbody>

    </table>

</div>

@endif

@endsection