@extends('layouts.admin')

@section('page-title', $training->title)
@section('page-subtitle', 'Training and Technology Demonstrations')

@section('main')
<a href="{{ route('admin.distribution-batches.index') }}" class="btn btn-fams-back mb-3">Back to Schedules</a>
@if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<section class="card shadow-sm mb-3">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Training Schedule</h2>
            <span class="badge {{ $training->status === 'published' ? 'text-bg-success' : 'text-bg-light' }}">{{ ucfirst($training->status) }}</span>
        </div>
        @include('partials.training-details')
        <p class="mt-3 mb-0"><strong>{{ $confirmed }} of {{ $training->slots }}</strong> slots confirmed</p>
        @if ($training->status === 'draft' && $training->isUpcoming())
            <form method="POST" action="{{ route('admin.trainings.publish', $training) }}" class="mt-3">
                @csrf
                <button class="btn btn-fams">Publish Training and Open Registration</button>
            </form>
        @endif
    </div>
</section>
<section class="card shadow-sm">
    <div class="card-header bg-white"><h2 class="h6 mb-0">Farmer Registrations</h2></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">Farmer</th><th scope="col">Contact and Farm</th><th scope="col">Status</th><th scope="col">Review</th></tr></thead>
            <tbody>
                @forelse ($registrations as $registration)
                    <tr>
                        <td class="fw-semibold">{{ $registration->farmer->full_name }}</td>
                        <td>{{ $registration->farmer->contact_number }}<div class="small text-muted">{{ $registration->farmer->barangay }} · {{ $registration->farmer->crop_type }}</div></td>
                        <td><span class="badge {{ match ($registration->status) { 'confirmed' => 'text-bg-success', 'rejected' => 'text-bg-danger', default => 'text-bg-warning' } }}">{{ ucfirst($registration->status) }}</span></td>
                        <td>
                            @if ($registration->status === 'pending' && $training->isUpcoming())
                                <div class="d-flex flex-column align-items-start gap-2">
                                    <form method="POST" action="{{ route('admin.trainings.review', [$training, $registration]) }}">
                                        @csrf
                                        <button name="status" value="confirmed" class="btn btn-sm btn-fams" @disabled($confirmed >= $training->slots)>Confirm Slot</button>
                                    </form>
                                    <details>
                                        <summary class="text-danger">Reject Registration</summary>
                                        <form method="POST" class="mt-2" action="{{ route('admin.trainings.review', [$training, $registration]) }}">
                                            @csrf
                                            <label class="form-label" for="reason-{{ $registration->id }}">Reason for rejection</label>
                                            <textarea id="reason-{{ $registration->id }}" class="form-control mb-2" name="reason" maxlength="1000" required></textarea>
                                            <button name="status" value="rejected" class="btn btn-sm btn-outline-danger">Submit Rejection</button>
                                        </form>
                                    </details>
                                </div>
                            @else
                                <span class="text-muted">{{ $registration->reason ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No registrations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($registrations->hasPages())<div class="card-footer">{{ $registrations->links() }}</div>@endif
</section>
@endsection
