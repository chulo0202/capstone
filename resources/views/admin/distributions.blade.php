@extends('layouts.admin')

@section('page-title', 'Distribution Monitoring')

@section('main')

<div class="d-flex justify-content-between align-items-center mb-3">

    <h5 class="mb-0">
        Distribution Logs
    </h5>

    <a href="{{ route('admin.distribution-batches.index') }}" class="btn btn-fams">Distribution Preparation</a>

</div>

<div class="card card-body mb-3">
    <form method="POST" action="{{ route('admin.distributions.verify') }}">
        @csrf
        <label class="form-label" for="distribution-qr">Farmer QR code or token</label>
        <div class="input-group">
            <input class="form-control" name="qr_data" id="distribution-qr" required>
            <button class="btn btn-outline-secondary">Verify</button>
        </div>
    </form>
    @isset($farmer)
        <h2 class="h6 mt-3">{{ $farmer->full_name }}</h2>
        @forelse ($applications as $application)
            @if ($assignment = $application->activeDistributionAssignment)
                <div class="mb-2">
                    {{ $application->program?->name }} · {{ $application->benefit?->name }}
                    <a href="{{ route('admin.distribution-batches.show', $assignment->batch) }}">Open assigned batch</a>
                </div>
            @else
                <form method="POST" action="{{ route('admin.distributions.release') }}" class="mb-2">
                    @csrf
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                    <input type="hidden" name="farmer_id" value="{{ $farmer->id }}">
                    <span class="me-2">{{ $application->program?->name }} · {{ $application->benefit?->name }}</span>
                    <label class="me-2"><input type="checkbox" name="confirm_release" value="1" required> Confirm handover</label>
                    <button class="btn btn-sm btn-fams">Release</button>
                </form>
            @endif
        @empty
            <p class="mb-0">No approved benefit applications found.</p>
        @endforelse
    @endisset
</div>

<div class="card border-0 shadow-sm">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">

                    <tr>
                        <th>Farmer</th>
                        <th>Program</th>
                        <th>Benefit</th>
                        <th>Date</th>
                        <th>Batch</th>
                        <th>Released By</th>
                        <th>Notes</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($distributions as $dist)

                        <tr>

                            <td>
                                {{ $dist->farmer->full_name }}
                            </td>

                            <td>
                                {{ $dist->program->name }}
                            </td>

                            <td>
                                {{ $dist->benefit?->name ?? 'Legacy distribution' }}
                            </td>

                            <td>
                                {{ $dist->distributed_at->format('M d, Y h:i A') }}
                            </td>

                            <td>
                                @if ($dist->distribution_batch_id)
                                    <a href="{{ route('admin.distribution-batches.show', $dist->distribution_batch_id) }}">#{{ $dist->distribution_batch_id }}</a>
                                @else
                                    Direct
                                @endif
                            </td>

                            <td>
                                {{ $dist->releasedBy->name }}
                            </td>

                            <td>
                                {{ $dist->notes ?? '-' }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="text-center text-muted py-4"
                            >
                                No distribution records
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if($distributions->hasPages())

        <div class="card-footer bg-white">
            {{ $distributions->links() }}
        </div>

    @endif

</div>

@endsection