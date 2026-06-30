@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="auth-page">
    <div class="auth-brand-panel">
        <div class="auth-brand-content">
            <div class="auth-brand-icon"><i class="bi bi-person-plus-fill"></i></div>
            <h2>Join FAMS Today</h2>
            <p>Register as a farmer to access assistance programs, track your eligibility, receive advisories, and get your unique QR code for benefit distribution.</p>
            <ul class="auth-brand-features">
                <li><i class="bi bi-check-circle-fill"></i> View recommended programs</li>
                <li><i class="bi bi-check-circle-fill"></i> Upload valid ID & manage profile</li>
                <li><i class="bi bi-check-circle-fill"></i> Receive MAO announcements</li>
            </ul>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-wrap auth-form">
            <h3>Create account</h3>
            <p class="subtitle">Register as a farmer</p>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Juan Dela Cruz" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@example.com" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimum 8 characters" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter password" required>
                </div>
                <button type="submit" class="btn btn-fams w-100 py-2">Create Account</button>
            </form>

            <p class="text-center mt-4 mb-0 small text-muted">
                Already registered?
                <a href="{{ route('login') }}" class="auth-link">Sign in</a>
            </p>
        </div>
    </div>
</div>
@endsection
