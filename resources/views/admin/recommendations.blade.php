@extends('layouts.admin')

@section('page-title', 'Program / Benefit Engine')

@section('main')

<div class="fams-card">

    {{-- HEADER --}}
    <div class="fams-card-header recommendation-header">
        <div>
            <h6 class="mb-1">
                <i class="bi bi-lightbulb me-2 text-fams"></i>
                Recommendation Engine
            </h6>

            <small class="text-muted">
                Review farmer program recommendations and eligibility status.
            </small>
        </div>

        <div class="recommendation-count">
            <i class="bi bi-list-check me-1"></i>
            {{ $recommendations->total() }} Recommendations
        </div>
    </div>


    {{-- TABLE --}}
    <div class="fams-card-body p-0">

        <div class="table-responsive recommendation-table-wrapper">

            <table class="table fams-table recommendation-table mb-0">

                <thead>
                    <tr>
                        <th class="farmer-column">
                            <i class="bi bi-person me-1"></i>
                            Farmer
                        </th>

                        <th class="program-column">
                            <i class="bi bi-box-seam me-1"></i>
                            Program / Benefit
                        </th>

                        <th class="status-column">
                            <i class="bi bi-shield-check me-1"></i>
                            Status
                        </th>

                        <th class="reason-column">
                            <i class="bi bi-info-circle me-1"></i>
                            Reason
                        </th>
                    </tr>
                </thead>


                <tbody>

                    @forelse($recommendations as $recommendation)

                        <tr>

                            {{-- FARMER --}}
                            <td class="farmer-cell">

                                <div class="farmer-name">
                                    <i class="bi bi-person-circle me-2"></i>
                                    {{ $recommendation->farmer->full_name }}
                                </div>

                            </td>


                            {{-- PROGRAM --}}
                            <td class="program-cell">

                                <div class="recommendation-program">
                                    <i class="bi bi-box-seam me-2"></i>

                                    <span>
                                        {{ $recommendation->program->name }}
                                    </span>
                                </div>
                                <div class="small text-muted ms-4">
                                    {{ $recommendation->benefit?->name ?? 'Legacy recommendation' }}
                                </div>

                            </td>


                            {{-- STATUS --}}
                            <td class="status-cell">

                                @if($recommendation->eligibility_status === 'eligible')

                                    <span class="badge bg-success recommendation-badge">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Eligible
                                    </span>

                                @elseif($recommendation->eligibility_status === 'partially_eligible')

                                    <span class="badge bg-warning text-dark recommendation-badge">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        Partially Eligible
                                    </span>

                                @else

                                    <span class="badge bg-danger recommendation-badge">
                                        <i class="bi bi-x-circle me-1"></i>
                                        Not Eligible
                                    </span>

                                @endif

                            </td>


                            {{-- REASON --}}
                            <td class="reason-cell">

                                @if($recommendation->missing_requirements)

                                    <div class="recommendation-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>

                                        <span>
                                            {{ implode(', ', $recommendation->missing_requirements) }}
                                        </span>
                                    </div>

                                @else

                                    <div class="recommendation-success">
                                        <i class="bi bi-check2-circle me-2"></i>

                                        <span>
                                            All requirements met
                                        </span>
                                    </div>

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>
                            <td colspan="4" class="text-center py-5">

                                <div class="recommendation-empty">

                                    <i class="bi bi-lightbulb"></i>

                                    <div class="fw-semibold">
                                        No Recommendations Available
                                    </div>

                                    <small>
                                        There are currently no farmer recommendations to display.
                                    </small>

                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- PAGINATION --}}
    @if($recommendations->hasPages())

        <div class="recommendation-footer">
            {{ $recommendations->links() }}
        </div>

    @endif

</div>

@endsection