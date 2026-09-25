@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="auth-page">
    <div class="auth-brand-panel">
        <div class="auth-brand-content">
            <div class="auth-brand-icon">
                <i class="bi bi-person-plus-fill"></i>
            </div>

            <h2>Join FAMS Today</h2>

            <p>
                Register as a farmer to access assistance programs,
                track your eligibility, receive advisories, and get
                your unique QR code for benefit distribution.
            </p>

            <ul class="auth-brand-features">
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    View recommended programs
                </li>

                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    Upload valid ID & manage profile
                </li>

                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    Receive MAO announcements
                </li>
            </ul>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-wrap auth-form">

            <h3>Create account</h3>
            <p class="subtitle">Register as a farmer</p>

            {{-- REGISTRATION FORM --}}
            <form
                method="POST"
                action="{{ route('register') }}"
                id="registrationForm"
            >
                @csrf

                {{-- STEP 1: RSBSA VERIFICATION --}}
                <div id="rsbsaStep">

                    <div class="mb-4">
                        <label class="form-label">
                            RSBSA Number
                        </label>

                        <input
                            type="text"
                            id="rsbsa_number"
                            class="form-control @error('rsbsa_number') is-invalid @enderror"
                            value="{{ old('rsbsa_number') }}"
                            placeholder="Enter your RSBSA Number"
                            required
                        >

                        @error('rsbsa_number')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="form-text">
                            Enter your registered RSBSA number to continue.
                        </div>
                    </div>

                    <div id="rsbsaMessage"></div>

                    <button
                        type="button"
                        id="checkRsbsaBtn"
                        class="btn btn-fams w-100 py-2"
                    >
                        <i class="bi bi-search"></i>
                        Check RSBSA Number
                    </button>

                </div>


                {{-- STEP 2: ACCOUNT INFORMATION --}}
                <div id="accountStep" style="display: none;">

                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        RSBSA record found. Please complete your
                        account information.
                    </div>


                    {{-- RSBSA NUMBER --}}
                    <div class="mb-3">
                        <label class="form-label">
                            RSBSA Number
                        </label>

                        <input
                            type="text"
                            name="rsbsa_number"
                            id="verified_rsbsa_number"
                            class="form-control"
                            readonly
                        >
                    </div>


                    {{-- FULL NAME --}}
                    <div class="mb-3">
                        <label class="form-label">
                            Full name
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="rsbsa_full_name"
                            class="form-control"
                            readonly
                        >
                    </div>


                    {{-- BARANGAY --}}
                    <div class="mb-3">
                        <label class="form-label">
                            Barangay
                        </label>

                        <input
                            type="text"
                            id="rsbsa_barangay"
                            class="form-control"
                            readonly
                        >
                    </div>


                    {{-- CROP TYPE --}}
                    <div class="mb-3">
                        <label class="form-label">
                            Crop type
                        </label>

                        <input
                            type="text"
                            id="rsbsa_crop_type"
                            class="form-control"
                            readonly
                        >
                    </div>


                    {{-- LAND SIZE --}}
                    <div class="mb-3">
                        <label class="form-label">
                            Land size
                        </label>

                        <div class="input-group">
                            <input
                                type="text"
                                id="rsbsa_land_size"
                                class="form-control"
                                readonly
                            >

                            <span class="input-group-text">
                                hectares
                            </span>
                        </div>
                    </div>


                    {{-- EMAIL --}}
                    <div class="mb-3">
                        <label class="form-label">
                            Email address
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="you@gmail.com"
                            required
                        >

                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>


                    {{-- PASSWORD --}}
                    <div class="mb-3">
                        <label class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Minimum 8 characters"
                            required
                        >

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>


                    {{-- CONFIRM PASSWORD --}}
                    <div class="mb-4">
                        <label class="form-label">
                            Confirm password
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="Re-enter password"
                            required
                        >
                    </div>


                    {{-- CREATE ACCOUNT --}}
                    <button
                        type="submit"
                        class="btn btn-fams w-100 py-2"
                        id="createAccountBtn"
                    >
                        Create Account
                    </button>


                    {{-- BACK --}}
                    <button
                        type="button"
                        class="btn btn-outline-secondary w-100 py-2 mt-2"
                        id="backToRsbsaBtn"
                    >
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </button>

                </div>

            </form>


            <p class="text-center mt-4 mb-0 small text-muted">
                Already registered?

                <a
                    href="{{ route('login') }}"
                    class="auth-link"
                >
                    Sign in
                </a>
            </p>

        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const checkBtn = document.getElementById('checkRsbsaBtn');

    const rsbsaStep = document.getElementById('rsbsaStep');

    const accountStep = document.getElementById('accountStep');

    const rsbsaInput = document.getElementById('rsbsa_number');

    const rsbsaMessage = document.getElementById('rsbsaMessage');

    const backBtn = document.getElementById('backToRsbsaBtn');


    checkBtn.addEventListener('click', async function () {

        const rsbsaNumber = rsbsaInput.value.trim();

        rsbsaMessage.innerHTML = '';


        if (!rsbsaNumber) {

            rsbsaMessage.innerHTML = `
                <div class="alert alert-danger">
                    Please enter your RSBSA number.
                </div>
            `;

            rsbsaInput.focus();

            return;
        }


        checkBtn.disabled = true;

        checkBtn.innerHTML = `
            <span class="spinner-border spinner-border-sm"></span>
            Checking...
        `;


        try {

            const response = await fetch(
                "{{ route('register.check-rsbsa') }}",
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector(
                                'meta[name="csrf-token"]'
                            )
                            .getAttribute('content')
                    },

                    body: JSON.stringify({
                        rsbsa_number: rsbsaNumber
                    })
                }
            );


            const data = await response.json();


            if (!response.ok) {

                rsbsaMessage.innerHTML = `
                    <div class="alert alert-danger">
                        ${
                            data.message ??
                            'RSBSA number not found. Please verify your RSBSA number or contact the MAO.'
                        }
                    </div>
                `;

                return;
            }


            document.getElementById(
                'verified_rsbsa_number'
            ).value = data.rsbsa_number;


            document.getElementById(
                'rsbsa_full_name'
            ).value = data.full_name;


            document.getElementById(
                'rsbsa_barangay'
            ).value = data.barangay;


            document.getElementById(
                'rsbsa_crop_type'
            ).value = data.crop_type;


            document.getElementById(
                'rsbsa_land_size'
            ).value = data.land_size;


            rsbsaStep.style.display = 'none';

            accountStep.style.display = 'block';

        } catch (error) {

            rsbsaMessage.innerHTML = `
                <div class="alert alert-danger">
                    Unable to check RSBSA number.
                    Please try again.
                </div>
            `;

        } finally {

            checkBtn.disabled = false;

            checkBtn.innerHTML = `
                <i class="bi bi-search"></i>
                Check RSBSA Number
            `;
        }
    });


    backBtn.addEventListener('click', function () {

        accountStep.style.display = 'none';

        rsbsaStep.style.display = 'block';

    });

});
</script>

@endsection