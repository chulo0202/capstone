@extends('layouts.admin')

@section('page-title', 'Settings')

@section('main')
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.settings.update') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Profile Name</label><input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled></div>
        <div class="col-md-6"><label class="form-label">New Password</label><input type="password" name="password" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">OpenWeather API Key</label><input type="text" class="form-control" value="{{ config('openweather.api_key') }}" disabled></div>
        <div class="col-md-6"><label class="form-label">Semaphore API Key</label><input type="text" class="form-control" value="{{ config('semaphore.api_key') }}" disabled></div>
      </div>
      <button class="btn btn-fams mt-4">Save Settings</button>
    </form>
  </div>
</div>
@endsection
