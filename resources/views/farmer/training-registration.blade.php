@extends('layouts.farmer')

@section('page-title', $training->title)
@section('page-subtitle', 'Training Registration')

@section('main')
<a href="{{ route('farmer.notifications.index') }}" class="btn btn-fams-back mb-3">Back to Notifications</a>
<section class="card shadow-sm">
    <div class="card-body p-4">
        <h2 class="h5 mb-2">Registration {{ ucfirst($registration->status) }}</h2>
        @if ($registration->reason)<p class="text-danger">{{ $registration->reason }}</p>@endif
        @include('partials.training-details')
    </div>
</section>
@endsection
