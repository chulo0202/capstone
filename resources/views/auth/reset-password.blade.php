@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
<div class="auth-page">
    <div class="auth-brand-panel">
        <div class="auth-brand-content">
            <div class="auth-brand-icon"><i class="bi bi-key-fill"></i></div>
            <h2>Set New Password</h2>
            <p>Choose a strong password to keep your FAMS account secure.</p>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-wrap auth-form">
            <h3>Reset password</h3>
            <p class="subtitle">Enter your new credentials</p>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $request->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-fams w-100 py-2">Reset Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
