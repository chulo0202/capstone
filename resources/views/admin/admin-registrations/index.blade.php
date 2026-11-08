@extends('layouts.admin')

@section('page-title', 'Admin Console')
@section('page-subtitle', 'Review and manage administrator registration requests')

@section('main')

{{-- Admin Statistics --}}
<div class="row g-3 mb-4">

    {{-- Total Admins --}}
    <div class="col-sm-6 col-xl-3">
        <div class="fams-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fams-stat-label">Total Admins</div>

                    <div class="fams-stat-value">
                        {{ $totalAdmins }}
                    </div>

                    <div class="fams-stat-sub">
                        Registered administrators
                    </div>
                </div>

                <div class="fams-stat-icon blue">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Approved Admins --}}
    <div class="col-sm-6 col-xl-3">
        <div class="fams-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fams-stat-label">Approved Admins</div>

                    <div class="fams-stat-value">
                        {{ $approvedAdmins }}
                    </div>

                    <div class="fams-stat-sub">
                        Active administrators
                    </div>
                </div>

                <div class="fams-stat-icon green">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Admins --}}
    <div class="col-sm-6 col-xl-3">
        <div class="fams-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fams-stat-label">Pending Requests</div>

                    <div class="fams-stat-value">
                        {{ $pendingCount }}
                    </div>

                    <div class="fams-stat-sub">
                        Awaiting approval
                    </div>
                </div>

                <div class="fams-stat-icon amber">
                    <i class="bi bi-person-exclamation"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Declined Admins --}}
    <div class="col-sm-6 col-xl-3">
        <div class="fams-stat">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fams-stat-label">Declined</div>

                    <div class="fams-stat-value">
                        {{ $declinedAdmins }}
                    </div>

                    <div class="fams-stat-sub">
                        Declined registrations
                    </div>
                </div>

                <div class="fams-stat-icon teal">
                    <i class="bi bi-person-x-fill"></i>
                </div>
            </div>
        </div>
    </div>

</div>


{{-- Success Message --}}
@if (session('success'))
    <div class="alert fams-alert fams-alert-success mb-4">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
    </div>
@endif


{{-- Pending Admin Registrations --}}
<div class="fams-card">

    <div class="fams-card-header">
        <div>
            <h6>
                <i class="bi bi-person-gear me-2 text-fams"></i>
                Pending Admin Registrations
            </h6>

            <div class="small text-muted mt-1">
                These accounts are waiting for approval from an existing administrator.
            </div>
        </div>
    </div>

    <div class="fams-card-body p-0">

        @if ($pendingAdmins->isEmpty())

            {{-- Empty State --}}
            <div class="fams-empty py-5">

                <i class="bi bi-person-check"></i>

                <div class="mt-2">
                    No pending admin registrations.
                </div>

                <div class="small text-muted mt-1">
                    New admin registration requests will appear here.
                </div>

            </div>

        @else

            {{-- Pending Admin Table --}}
            <div class="table-responsive">

                <table class="table fams-table mb-0">

                    <thead>
                        <tr>
                            <th>Administrator</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($pendingAdmins as $admin)

                            <tr>

                                {{-- Administrator --}}
                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        <div
                                            class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                            style="
                                                width: 40px;
                                                height: 40px;
                                                background: #e8f5e9;
                                                color: #198754;
                                            "
                                        >
                                            {{ strtoupper(substr($admin->name, 0, 1)) }}
                                        </div>

                                        <div>

                                            <strong>
                                                {{ $admin->name }}
                                            </strong>

                                            <div class="small text-muted">
                                                Administrator
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- Email --}}
                                <td>
                                    <span class="text-muted">
                                        {{ $admin->email }}
                                    </span>
                                </td>


                                {{-- Registered --}}
                                <td>

                                    <div>
                                        {{ $admin->created_at->format('M d, Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $admin->created_at->format('h:i A') }}
                                    </div>

                                </td>


                                {{-- Actions --}}
                                <td>

                                    <div class="d-flex justify-content-end gap-2">

                                        {{-- Approve --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.admin-registrations.approve', $admin) }}"
                                        >

                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="btn btn-success btn-sm"
                                            >
                                                <i class="bi bi-check-lg me-1"></i>
                                                Approve
                                            </button>

                                        </form>


                                        {{-- Decline --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.admin-registrations.decline', $admin) }}"
                                        >

                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="btn btn-outline-danger btn-sm"
                                            >
                                                <i class="bi bi-x-lg me-1"></i>
                                                Decline
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif

    </div>

</div>

@endsection