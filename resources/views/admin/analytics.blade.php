@extends('layouts.admin')

@section('page-title', 'Analytics Dashboard')

@section('main')
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-muted mb-1">Total Farmers</p><h3>{{ $totalFarmers }}</h3></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-muted mb-1">Total Programs</p><h3>{{ $totalPrograms }}</h3></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-muted mb-1">Active Programs</p><h3>{{ $activePrograms }}</h3></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><p class="text-muted mb-1">Beneficiaries</p><h3>{{ $totalBeneficiaries }}</h3></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Farmers per Barangay</h6></div>
      <div class="card-body"><canvas id="barangayChart" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Crop Distribution</h6></div>
      <div class="card-body"><canvas id="cropChart" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-lg-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Distributions per Program ({{ $totalDistributions }} total)</h6></div>
      <div class="card-body"><canvas id="distChart" height="120"></canvas></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const barangayData = @json($farmersPerBarangay);
const cropData = @json($cropDistribution);
const distData = @json($distributionsPerProgram);

new Chart(document.getElementById('barangayChart'), {
  type: 'bar',
  data: {
    labels: barangayData.map(d => d.barangay),
    datasets: [{ label: 'Farmers', data: barangayData.map(d => d.total), backgroundColor: '#2d6a4f' }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('cropChart'), {
  type: 'doughnut',
  data: {
    labels: cropData.map(d => d.crop_type),
    datasets: [{ data: cropData.map(d => d.total), backgroundColor: ['#2d6a4f','#40916c','#52b788','#74c69d','#95d5b2','#b7e4c7'] }]
  }
});

new Chart(document.getElementById('distChart'), {
  type: 'bar',
  data: {
    labels: distData.map(d => d.name),
    datasets: [{ label: 'Distributions', data: distData.map(d => d.distributions_count), backgroundColor: '#40916c' }]
  },
  options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
});
</script>
@endpush
