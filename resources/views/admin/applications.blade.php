@extends('layouts.admin')

@section('page-title', 'Farmer Applications')
@section('page-subtitle', 'Review and manage farmer program applications')

@section('main')

<div class="fams-card">

    <div class="fams-card-header d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-1">
                <i class="bi bi-file-earmark-text-fill me-2 text-fams"></i>
                Farmer Applications
            </h6>
            <small class="text-muted">
                Review applications submitted by farmers
            </small>
        </div>

        <form method="GET" class="d-flex gap-2">

            <input
                type="text"
                name="search"
                class="form-control form-control-sm"
                placeholder="Search farmer or program..."
                value="{{ request('search') }}"
                style="min-width:220px;"
            >

            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>
                    Pending
                </option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>
                    Approved
                </option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>
                    Rejected
                </option>
            </select>

            <button type="submit" class="btn btn-sm btn-fams">
                <i class="bi bi-search"></i>
            </button>

        </form>
    </div>

    <div class="fams-card-body p-0">

        <div class="table-responsive">

            <table class="table fams-table mb-0">

                <thead>
                    <tr>
                        <th>Farmer</th>
                        <th>Program</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th>Reviewed</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($applications as $application)

                        <tr>

                            {{-- Farmer --}}
                            <td>
                                <strong>
                                    {{ $application->farmer?->full_name ?? 'Unknown Farmer' }}
                                </strong>

                                @if($application->farmer?->user)
                                    <div class="small text-muted">
                                        {{ $application->farmer->user->email }}
                                    </div>
                                @endif
                            </td>

                            {{-- Program --}}
                            <td>
                                {{ $application->program?->title ?? 'Unknown Program' }}
                            </td>

                            {{-- Applied Date --}}
                            <td>
                                {{ $application->applied_at?->format('M d, Y h:i A') ?? '—' }}
                            </td>

                            {{-- Status --}}
                            <td>

                                @if($application->status === 'approved')

                                    <span class="badge bg-success">
                                        Approved
                                    </span>

                                @elseif($application->status === 'rejected')

                                    <span class="badge bg-danger">
                                        Rejected
                                    </span>

                                @else

                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>

                                @endif

                            </td>

                            {{-- Reviewed Date --}}
                            <td>
                                {{ $application->reviewed_at?->format('M d, Y h:i A') ?? 'Not reviewed' }}
                            </td>

                            {{-- Actions --}}
                            <td>

                                @if($application->status === 'pending')

                                    <div class="d-flex gap-1">

                                        {{-- Approve --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.applications.approve', $application) }}"
                                            onsubmit="return confirm('Approve this application?')"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-success"
                                                title="Approve"
                                            >
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>

                                        {{-- Reject --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.applications.reject', $application) }}"
                                            onsubmit="return confirm('Reject this application?')"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Reject"
                                            >
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>

                                    </div>

                                @else

                                    <span class="text-muted small">
                                        No action
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6">

                                <div class="fams-empty">

                                    <i class="bi bi-file-earmark-text"></i>

                                    <div class="mt-2">
                                        No applications found.
                                    </div>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if($applications->hasPages())

        <div class="fams-card-body border-top py-3">

            {{ $applications->links() }}

        </div>

    @endif

</div>

@endsection