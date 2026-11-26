@extends('layouts.admin')

@section('page-title', 'Create Training Schedule')
@section('page-subtitle', 'Training and Technology Demonstrations')

@section('main')
<a href="{{ route('admin.schedules.options') }}" class="btn btn-fams-back mb-3">Back to Create Schedule</a>
<form method="POST" action="{{ route('admin.trainings.store') }}" class="card shadow-sm">
    @csrf
    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif
        <h2 class="h5 mb-3">Training Details</h2>
        <div class="row g-3">
            @foreach ([
                ['title', 'Training Title', 'text', 'col-md-6', 150],
                ['topic', 'Topic', 'text', 'col-md-6', 150],
                ['objectives', 'Description and Objectives', 'textarea', 'col-12', 3000],
                ['scheduled_date', 'Date', 'date', 'col-md-4', null],
                ['start_time', 'Start Time', 'time', 'col-md-4', null],
                ['end_time', 'End Time', 'time', 'col-md-4', null],
                ['venue', 'Venue', 'text', 'col-md-6', 255],
                ['trainer', 'Trainer / Resource Person', 'text', 'col-md-6', 255],
                ['slots', 'Number of Slots', 'number', 'col-md-4', null],
                ['instructions', 'Instructions (optional)', 'textarea', 'col-12', 3000],
            ] as [$field, $label, $input, $column, $length])
                <div class="{{ $column }}">
                    <label class="form-label" for="{{ $field }}">{{ $label }} @if ($field !== 'instructions')<span class="text-danger" aria-hidden="true">*</span>@endif</label>
                    @if ($input === 'textarea')
                        <textarea class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" maxlength="{{ $length }}" rows="3" @required($field !== 'instructions')>{{ old($field) }}</textarea>
                    @else
                        <input class="form-control @error($field) is-invalid @enderror" type="{{ $input }}" id="{{ $field }}" name="{{ $field }}" value="{{ old($field) }}" @if($length) maxlength="{{ $length }}" @endif @if($field === 'slots') min="1" max="10000" @endif @if($input === 'date') min="{{ today('Asia/Manila')->toDateString() }}" @endif required>
                    @endif
                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end">
        <button class="btn btn-fams">Save Training Draft</button>
    </div>
</form>
@endsection
