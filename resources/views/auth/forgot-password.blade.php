@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="auth-page">
    <div class="auth-brand-panel">
        <div class="auth-brand-content">
            <div class="auth-brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <h2>Account Recovery</h2>
            <p>Enter your registered email address and we'll send you a link to reset your password securely.</p>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-wrap auth-form">
            <h3>Forgot password?</h3>
            <p class="subtitle">We'll email you a reset link</p>

            @if(session('status'))
                <div class="alert fams-alert fams-alert-success mb-3">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-fams w-100 py-2">Send Reset Link</button>
            </form>

            <p class="text-center mt-4 mb-0">
                <a href="{{ route('login') }}" class="auth-link"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
            </p>
        </div>
    </div>
</div>
@endsection
