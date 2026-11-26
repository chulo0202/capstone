@extends('layouts.admin')

@section('page-title', 'Create Schedule')
@section('page-subtitle', 'Choose what you want to schedule')

@section('main')
<a href="{{ route('admin.distribution-batches.index') }}" class="btn btn-fams-back mb-3"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Schedules</a>
<div class="row g-3">
    @foreach ([
        ['Distribution', 'Schedule a distribution for Seed Subsidies or Fertilizer Subsidies.', 'bi-box-seam', 'admin.distribution-batches.create', 'Create Distribution'],
        ['Training and Technology Demonstrations', 'Schedule a training session for selected farmers.', 'bi-calendar-event', 'admin.trainings.create', 'Create Training'],
    ] as [$name, $description, $icon, $routeName, $buttonLabel])
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <i class="bi {{ $icon }} text-success fs-3 mb-2" aria-hidden="true"></i>
                    <h2 class="h5">{{ $name }}</h2>
                    <p class="text-muted">{{ $description }}</p>
                    <a class="btn btn-fams mt-auto align-self-start" href="{{ route($routeName) }}">{{ $buttonLabel }}</a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
