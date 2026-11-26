@extends('layouts.farmer')

@section('page-title', 'Training Sessions')
@section('page-subtitle', 'Register for Training and Technology Demonstrations')

@section('main')
<div class="row g-3 mb-3">
    @forelse ($trainings as $training)
        @php($registration = $training->registrations->first())
        <div class="col-xl-6">
            <section class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5">{{ $training->title }}</h2>
                    @include('partials.training-details')
                    <p class="small text-muted mb-2">{{ max(0, $training->slots - $training->confirmed_count) }} slots available</p>
                    @if ($registration)
                        <p class="mb-0"><strong>Registration:</strong> {{ ucfirst($registration->status) }}</p>
                        @if ($registration->reason)<p class="mb-0 text-muted">{{ $registration->reason }}</p>@endif
                    @elseif (! $training->isUpcoming())
                        <span class="text-muted">Registration closed</span>
                    @elseif ($training->confirmed_count >= $training->slots)
                        <span class="text-muted">All slots filled</span>
                    @elseif ($farmer?->profile_completed && $farmer->is_active)
                        <form method="POST" action="{{ route('farmer.trainings.store', $training) }}">
                            @csrf
                            <button class="btn btn-fams">Register for Training</button>
                        </form>
                    @else
                        <a class="btn btn-outline-success" href="{{ route('farmer.profile', ['edit' => 1]) }}">Complete Your Profile</a>
                    @endif
                </div>
            </section>
        </div>
    @empty
        <div class="col"><p class="card card-body text-muted">No upcoming training sessions.</p></div>
    @endforelse
</div>
{{ $trainings->links() }}
@endsection
