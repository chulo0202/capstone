@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="auth-page">
    <div class="auth-brand-panel">
        <div class="auth-brand-content">
            <div class="auth-brand-icon"><i class="bi bi-tree-fill"></i></div>
            <h2>Farmer Assistance Management System</h2>
            <p>Empowering the Municipal Agriculture Office to deliver timely assistance, track eligibility, and support farmers across the community.</p>
            <ul class="auth-brand-features">
                <li><i class="bi bi-check-circle-fill"></i> Program eligibility tracking</li>
                <li><i class="bi bi-check-circle-fill"></i> QR-based benefit distribution</li>
                <li><i class="bi bi-check-circle-fill"></i> Weather advisories & announcements</li>
            </ul>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-wrap auth-form">
            <h3>Welcome back</h3>
            <p class="subtitle">Sign in to your FAMS account</p>

            @if(session('status'))
                <div class="alert fams-alert fams-alert-success mb-3">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" placeholder="Enter your password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
                    @endif
                </div>
                <button type="submit" class="btn btn-fams w-100 py-2">Sign In</button>
            </form>

            <p class="text-center mt-4 mb-0 small text-muted">
                Don't have an account?
                <a href="{{ route('register') }}" class="auth-link">Register as Farmer</a>
            </p>
        </div>
    </div>
</div>
@endsection
