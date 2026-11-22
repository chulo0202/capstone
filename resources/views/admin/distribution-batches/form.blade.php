@extends('layouts.admin')

@section('page-title', 'Edit Distribution Schedule')

@section('main')
@if ($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif
<form class="card card-body" method="POST" id="distribution-batch-form"
    action="{{ route('admin.distribution-batches.update', $batch) }}">
    @csrf
    @method('PATCH')
    @include('admin.distribution-batches.schedule-fields')
    <div><button class="btn btn-fams">Save Schedule</button></div>
</form>
<script>
    const scheduleForm = document.getElementById('distribution-batch-form');
    const start = scheduleForm.elements.start_time;
    const end = scheduleForm.elements.end_time;

    function validateTimes() {
        end.setCustomValidity(start.value && end.value && start.value >= end.value
            ? 'End time must be later than start time.' : '');
    }

    start.addEventListener('input', validateTimes);
    end.addEventListener('input', validateTimes);
    validateTimes();
</script>
@endsection
