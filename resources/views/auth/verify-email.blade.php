@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
<div class="auth-page">

    {{-- LEFT BRAND PANEL --}}
    <div class="auth-brand-panel">
        <div class="auth-brand-content">

            <div class="auth-brand-icon">
                <i class="bi bi-envelope-check-fill"></i>
            </div>

            <h2>Verify Your Email</h2>

            <p>
                You're almost ready to use FAMS. Please verify your
                email address to activate your farmer account.
            </p>

            <ul class="auth-brand-features">
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    Access farmer assistance programs
                </li>

                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    View your eligibility and recommendations
                </li>

                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    Receive important MAO announcements
                </li>
            </ul>

        </div>
    </div>


    {{-- RIGHT FORM PANEL --}}
    <div class="auth-form-panel">

        <div class="auth-form-wrap auth-form">

            {{-- EMAIL ICON --}}
            <div class="text-center mb-4">

                <div
                    style="
                        width: 70px;
                        height: 70px;
                        margin: 0 auto 20px;
                        border-radius: 50%;
                        background: #e8f5e9;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    "
                >
                    <i
                        class="bi bi-envelope-check-fill"
                        style="
                            font-size: 32px;
                            color: #198754;
                        "
                    ></i>
                </div>

                <h3>Check your email</h3>

                <p class="subtitle">
                    Verify your email address to continue
                </p>

            </div>


            {{-- MESSAGE --}}
            <div
                class="alert alert-light border mb-4"
                style="line-height: 1.7;"
            >
                <div class="d-flex">

                    <i
                        class="bi bi-info-circle-fill text-success me-2 mt-1"
                    ></i>

                    <div>
                        Thanks for signing up!

                        Before you can access your FAMS farmer account,
                        please verify your email address by clicking
                        the verification link we sent to your email.
                    </div>

                </div>
            </div>


            {{-- RESENT MESSAGE --}}
            @if (session('status') == 'verification-link-sent')

                <div class="alert alert-success">

                    <i class="bi bi-check-circle-fill me-1"></i>

                    A new verification link has been sent to your
                    email address.

                </div>

            @endif


            {{-- RESEND EMAIL --}}
            <form
                method="POST"
                action="{{ route('verification.send') }}"
                class="mb-3"
            >
                @csrf

                <button
                    type="submit"
                    class="btn btn-fams w-100 py-2"
                >
                    <i class="bi bi-envelope-arrow-up me-1"></i>
                    Resend Verification Email
                </button>

            </form>


            {{-- LOGOUT --}}
            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="btn btn-outline-secondary w-100 py-2"
                >
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Log Out
                </button>

            </form>


            {{-- HELP TEXT --}}
            <div class="text-center mt-4">

                <small class="text-muted">
                    Didn't receive the email?
                    Check your <strong>Spam</strong> or
                    <strong>Junk</strong> folder.
                </small>

            </div>

        </div>

    </div>

</div>
@endsection