@extends('layouts.farmer')

@section('page-title', 'Eligibility Status')

@section('main')

@if(!$farmer || !$farmer->profile_completed)

    <div class="alert alert-warning">
        Complete your
        <a href="{{ route('farmer.profile') }}">profile</a>
        to view eligibility status.
    </div>

@else

    @php
        $eligibleCount = collect($results)
            ->where('status', 'eligible')
            ->count();

        $partialCount = collect($results)
            ->where('status', 'partially_eligible')
            ->count();

        $notEligibleCount = collect($results)
            ->where('status', 'not_eligible')
            ->count();
    @endphp

    {{-- Page Description --}}
    <div class="mb-4">
        <h5 class="mb-1">Program Eligibility</h5>

        <p class="text-muted mb-0">
            Check your eligibility status for available agricultural assistance programs.
        </p>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">

        {{-- Eligible --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <div class="small text-muted mb-1">
                                Eligible
                            </div>

                            <h3 class="mb-0 text-success">
                                {{ $eligibleCount }}
                            </h3>

                            <div class="small text-muted">
                                Program(s)
                            </div>

                        </div>

                        <div
                            class="rounded-circle bg-success bg-opacity-10
                                   d-flex align-items-center justify-content-center"
                            style="width: 48px; height: 48px;"
                        >

                            <i class="bi bi-check-circle text-success fs-4"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Partially Eligible --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <div class="small text-muted mb-1">
                                Partially Eligible
                            </div>

                            <h3 class="mb-0 text-warning">
                                {{ $partialCount }}
                            </h3>

                            <div class="small text-muted">
                                Program(s)
                            </div>

                        </div>

                        <div
                            class="rounded-circle bg-warning bg-opacity-10
                                   d-flex align-items-center justify-content-center"
                            style="width: 48px; height: 48px;"
                        >

                            <i class="bi bi-exclamation-circle text-warning fs-4"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Not Eligible --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <div class="small text-muted mb-1">
                                Not Eligible
                            </div>

                            <h3 class="mb-0 text-danger">
                                {{ $notEligibleCount }}
                            </h3>

                            <div class="small text-muted">
                                Program(s)
                            </div>

                        </div>

                        <div
                            class="rounded-circle bg-danger bg-opacity-10
                                   d-flex align-items-center justify-content-center"
                            style="width: 48px; height: 48px;"
                        >

                            <i class="bi bi-x-circle text-danger fs-4"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Eligibility Table --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <h6 class="mb-0">
                <i class="bi bi-clipboard-check me-2"></i>
                Eligibility Results
            </h6>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th class="px-4 py-3">
                                Program / Benefit
                            </th>

                            <th class="py-3">
                                Description
                            </th>

                            <th class="py-3">
                                Program Period
                            </th>

                            <th class="py-3 text-center">
                                Status
                            </th>

                            <th class="py-3">
                                Requirements
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($results as $item)

                            <tr>

                                {{-- Program --}}
                                <td class="px-4">

                                    <div class="fw-semibold">
                                        {{ $item['program']->name }}
                                    </div>
                                    <div class="small text-muted">
                                        {{ $item['benefit']->name }}
                                    </div>

                                </td>


                                {{-- Description --}}
                                <td>

                                    <div
                                        class="text-muted small"
                                        style="max-width: 300px;"
                                    >
                                        {{ Str::limit($item['program']->description, 100) }}
                                    </div>

                                </td>


                                {{-- Program Period --}}
                                <td>

                                    <div class="small">

                                        <div class="mb-1">
                                            <span class="text-muted">
                                                Starts:
                                            </span>

                                            <span class="fw-semibold">
                                                {{ $item['program']->start_at->format('F d, Y') }}
                                            </span>
                                        </div>

                                        <div>
                                            <span class="text-muted">
                                                Ends:
                                            </span>

                                            <span class="fw-semibold">
                                                {{ $item['program']->end_at->format('F d, Y') }}
                                            </span>
                                        </div>

                                    </div>

                                </td>


                                {{-- Status --}}
                                <td class="text-center">

                                    @if($item['status'] === 'eligible')

                                        <span class="badge bg-success">

                                            <i class="bi bi-check-circle me-1"></i>

                                            Eligible

                                        </span>

                                    @elseif($item['status'] === 'partially_eligible')

                                        <span class="badge bg-warning text-dark">

                                            <i class="bi bi-exclamation-circle me-1"></i>

                                            Partially Eligible

                                        </span>

                                    @else

                                        <span class="badge bg-danger">

                                            <i class="bi bi-x-circle me-1"></i>

                                            Not Eligible

                                        </span>

                                    @endif

                                </td>


                                {{-- Requirements --}}
                                <td>

                                    <div class="small mb-2">
                                        <span class="fw-semibold">Program-wide rules:</span>
                                        @if($item['global']['missing_requirements'])
                                            <ul class="mb-0 mt-1 ps-3 text-danger">
                                                @foreach($item['global']['missing_requirements'] as $missing)
                                                    <li>{{ $missing }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-success">Met</span>
                                        @endif
                                    </div>

                                    <div class="small">
                                        <span class="fw-semibold">Benefit-specific rules:</span>
                                        @if($item['benefit_evaluation']['missing_requirements'])
                                            <ul class="mb-0 mt-1 ps-3 text-danger">
                                                @foreach($item['benefit_evaluation']['missing_requirements'] as $missing)
                                                    <li>{{ $missing }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-success">Met</span>
                                        @endif
                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-5"
                                >

                                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>

                                    No eligibility results available.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endif

@endsection