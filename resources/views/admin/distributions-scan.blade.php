@extends('layouts.admin')

@section('page-title', 'Scan QR Code')

@section('main')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">QR Code Scanner</h6></div>
      <div class="card-body">
        <p class="text-muted">Scan a farmer's QR code or paste the QR data below to verify eligibility and release benefits.</p>
        <div id="reader" class="mb-3" style="max-width:400px;"></div>
        <form method="POST" action="{{ route('admin.distributions.verify') }}" id="verifyForm">
          @csrf
          <div class="mb-3">
            <label class="form-label">QR Code Data</label>
            <textarea name="qr_data" id="qrData" class="form-control" rows="4" placeholder='{"farmer_id":1,"token":"...","name":"..."}' required></textarea>
          </div>
          <button type="submit" class="btn btn-fams">Verify Farmer</button>
          <a href="{{ route('admin.distributions.index') }}" class="btn btn-outline-secondary">Back</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function onScanSuccess(decodedText) {
  document.getElementById('qrData').value = decodedText;
}
if (typeof Html5QrcodeScanner !== 'undefined') {
  const scanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 });
  scanner.render(onScanSuccess);
}
</script>
@endpush
