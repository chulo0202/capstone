@extends('layouts.admin')

@section('page-title', 'Scan QR Code')
@section('page-subtitle', 'Verify a farmer before checking eligibility and releasing benefits')

@section('main')

{{-- =========================================================
    PROCESS STEPS
========================================================= --}}

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">

        <div class="row text-center align-items-center">

            {{-- STEP 1 --}}
            <div class="col-4">
                <div class="d-flex align-items-center justify-content-center gap-2">

                    <span
                        class="rounded-circle bg-success text-white d-flex
                               align-items-center justify-content-center"
                    >
                        <i class="bi bi-qr-code-scan"></i>
                    </span>

                    <div class="text-start d-none d-md-block">

                        <small class="text-success fw-semibold">
                            STEP 1
                        </small>

                        <div class="fw-semibold">
                            Scan QR
                        </div>

                    </div>

                </div>
            </div>


            {{-- ARROW --}}
            <div class="col-1 text-muted">
                <i class="bi bi-chevron-right"></i>
            </div>


            {{-- STEP 2 --}}
            <div class="col-3">

                <div class="d-flex align-items-center justify-content-center gap-2">

                    <span
                        class="rounded-circle bg-light text-secondary d-flex
                               align-items-center justify-content-center"
                    >
                        <i class="bi bi-person-check"></i>
                    </span>

                    <div class="text-start d-none d-md-block">

                        <small class="text-muted fw-semibold">
                            STEP 2
                        </small>

                        <div class="fw-semibold text-muted">
                            Verify Farmer
                        </div>

                    </div>

                </div>

            </div>


            {{-- ARROW --}}
            <div class="col-1 text-muted">
                <i class="bi bi-chevron-right"></i>
            </div>


            {{-- STEP 3 --}}
            <div class="col-3">

                <div class="d-flex align-items-center justify-content-center gap-2">

                    <span
                        class="rounded-circle bg-light text-secondary d-flex
                               align-items-center justify-content-center">
                        <i class="bi bi-box-seam"></i>
                    </span>

                    <div class="text-start d-none d-md-block">

                        <small class="text-muted fw-semibold">
                            STEP 3
                        </small>

                        <div class="fw-semibold text-muted">
                            Release Benefit
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>


{{-- =========================================================
    QR SCANNER
========================================================= --}}

<div class="row g-4">

    {{-- LEFT: SCANNER --}}
    <div class="col-lg-7">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white py-3">

                <div class="d-flex align-items-center">

                    <div
                        class="rounded-circle bg-success bg-opacity-10
                               d-flex align-items-center justify-content-center me-3">

                        <i class="bi bi-qr-code-scan text-success fs-5"></i>

                    </div>

                    <div>

                        <h5 class="mb-0 fw-semibold">
                            QR Code Scanner
                        </h5>

                        <small class="text-muted">
                            Scan the farmer's FAMS QR code
                        </small>

                    </div>

                </div>

            </div>


            <div class="card-body">

                {{-- INSTRUCTIONS --}}
                <div class="alert alert-light border mb-4">

                    <div class="d-flex">

                        <i class="bi bi-info-circle text-success fs-5 me-2"></i>

                        <div class="small">

                            <strong>How to scan:</strong>

                            <ol class="mb-0 mt-1 ps-3">

                                <li>Allow camera access when requested.</li>

                                <li>Place the farmer's QR code inside the scanner.</li>

                                <li>Wait for the QR code to be detected.</li>

                                <li>Click <strong>Verify Farmer</strong>.</li>

                            </ol>

                        </div>

                    </div>

                </div>


                {{-- SCANNER AREA --}}
                <div
                    class="border rounded-3 p-3 bg-light mb-3"
                >

                    <div
                        id="reader"
                        class="bg-white rounded-3 overflow-hidden mx-auto">
                    </div>

                </div>


                {{-- SCANNER STATUS --}}
                <div class="text-center">

                    <small class="text-muted">

                        <i class="bi bi-camera me-1"></i>

                        Camera scanner will appear above

                    </small>

                </div>

            </div>

        </div>

    </div>


    {{-- RIGHT: QR DATA --}}
    <div class="col-lg-5">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white py-3">

                <div class="d-flex align-items-center">

                    <div
                        class="rounded-circle bg-success bg-opacity-10
                               d-flex align-items-center justify-content-center me-3">

                        <i class="bi bi-file-earmark-code text-success fs-5"></i>

                    </div>

                    <div>

                        <h5 class="mb-0 fw-semibold">
                            QR Code Data
                        </h5>

                        <small class="text-muted">
                            Scanned data will appear here
                        </small>

                    </div>

                </div>

            </div>


            <div class="card-body d-flex flex-column">

                <form
                    method="POST"
                    action="{{ route('admin.distributions.verify') }}"
                    id="verifyForm"
                    class="d-flex flex-column h-100"
                >

                    @csrf


                    {{-- QR DATA --}}
                    <div class="mb-3">

                        <label
                            for="qrData"
                            class="form-label fw-semibold"
                        >
                            QR Data
                        </label>

                        <textarea
                            name="qr_data"
                            id="qrData"
                            class="form-control"
                            rows="7"
                            placeholder='{"token":"..."}'
                            required
                        ></textarea>

                        <div class="form-text">

                            <i class="bi bi-shield-check me-1 text-success"></i>

                            FAMS QR codes use a secure token for farmer verification.

                        </div>

                    </div>


                    {{-- STATUS BOX --}}
                    <div
                        id="scanStatus"
                        class="alert alert-light border small mb-4"
                    >

                        <i class="bi bi-qr-code me-1"></i>

                        Waiting for QR code scan...

                    </div>


                    {{-- BUTTONS --}}
                    <div class="mt-auto">

                        <div class="d-grid gap-2">

                            <button
                                type="submit"
                                class="btn btn-fams btn-lg"
                            >

                                <i class="bi bi-person-check me-2"></i>

                                Verify Farmer

                            </button>


                            <button
                                type="button"
                                id="clearQr"
                                class="btn btn-outline-secondary"
                            >

                                <i class="bi bi-x-circle me-1"></i>

                                Clear QR Data

                            </button>


                            <a
                                href="{{ route('admin.distributions.index') }}"
                                class="btn btn-outline-secondary"
                            >

                                <i class="bi bi-arrow-left me-1"></i>

                                Back to Distributions

                            </a>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
    SECURITY NOTE
========================================================= --}}

<div class="alert alert-success border-0 shadow-sm mt-4">

    <div class="d-flex align-items-start">

        <i class="bi bi-shield-lock-fill fs-5 me-2"></i>

        <div>

            <strong>Secure Verification</strong>

            <div class="small mt-1">

                The QR code contains a secure verification token.
                Farmer information is retrieved securely from the system
                after successful verification.

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const qrData = document.getElementById('qrData');
    const scanStatus = document.getElementById('scanStatus');
    const clearQr = document.getElementById('clearQr');


    /*
    |--------------------------------------------------------------------------
    | QR SCAN SUCCESS
    |--------------------------------------------------------------------------
    */

    function onScanSuccess(decodedText) {

        qrData.value = decodedText;

        scanStatus.className =
            'alert alert-success border small mb-4';

        scanStatus.innerHTML =
            '<i class="bi bi-check-circle-fill me-1"></i>' +
            '<strong>QR code detected.</strong> ' +
            'The QR data is ready for verification.';


        /*
        |--------------------------------------------------------------------------
        | Optional visual feedback
        |--------------------------------------------------------------------------
        */

        qrData.classList.add('is-valid');

    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR QR DATA
    |--------------------------------------------------------------------------
    */

    if (clearQr) {

        clearQr.addEventListener('click', function () {

            qrData.value = '';

            qrData.classList.remove('is-valid');

            scanStatus.className =
                'alert alert-light border small mb-4';

            scanStatus.innerHTML =
                '<i class="bi bi-qr-code me-1"></i>' +
                'Waiting for QR code scan...';

        });

    }


    /*
    |--------------------------------------------------------------------------
    | START QR SCANNER
    |--------------------------------------------------------------------------
    */

    if (typeof Html5QrcodeScanner !== 'undefined') {

        const scanner = new Html5QrcodeScanner(
            'reader',
            {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                },
                rememberLastUsedCamera: true
            }
        );

        scanner.render(onScanSuccess);

    }

});

</script>

@endpush