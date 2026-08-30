@extends('layouts.admin')

@section('page-title', 'Farmer Management')
@section('page-subtitle', 'View and manage registered farmer records')

@section('main')
<div class="fams-card">
  <div class="fams-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <h6 class="mb-0">
        <i class="bi bi-people-fill me-2 text-fams"></i>
        Registered Farmers
    </h6>

    <div class="d-flex gap-2 align-items-center">
        <form class="d-flex gap-2" method="GET">
            <input
                type="text"
                name="search"
                class="form-control form-control-sm"
                style="min-width:220px;"
                placeholder="Search name, barangay, crop..."
                value="{{ request('search') }}"
            >

            <button type="submit" class="btn btn-sm btn-fams">
                <i class="bi bi-search"></i>
            </button>
        </form>

        <a href="{{ route('admin.farmers.create') }}" class="btn btn-sm btn-fams">
            <i class="bi bi-person-plus-fill me-1"></i>
            Add Farmer
        </a>
    </div>
</div>
  <div class="fams-card-body p-0">
    <div class="table-responsive">
      <table class="table fams-table mb-0">
        <thead>
          <tr>
            <th>Name</th><th>Barangay</th><th>Crop</th><th>Land (ha)</th><th>RSBSA</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($farmers as $farmer)
          <tr>
            <td><strong>{{ $farmer->full_name }}</strong></td>
            <td>{{ $farmer->barangay }}</td>
            <td><span class="badge bg-light text-dark border">{{ $farmer->crop_type }}</span></td>
            <td>{{ $farmer->land_size }}</td>
            <td>
              <span class="badge fams-badge bg-{{ $farmer->rsbsa_status ? 'success' : 'secondary' }}">
                {{ $farmer->rsbsa_status ? 'Registered' : 'None' }}
              </span>
            </td>
            <td>
    {{-- View --}}
    <a
        href="{{ route('admin.farmers.show', $farmer) }}"
        class="btn btn-sm btn-fams-outline"
        title="View Farmer"
    >
        <i class="bi bi-eye"></i>
    </a>

    {{-- Edit --}}
    <a
        href="{{ route('admin.farmers.edit', $farmer) }}"
        class="btn btn-sm btn-fams-outline"
        title="Edit Farmer"
    >
        <i class="bi bi-pencil-square"></i>
    </a>

    {{-- Delete --}}
    <form
        action="{{ route('admin.farmers.destroy', $farmer) }}"
        method="POST"
        class="d-inline"
        onsubmit="return confirm('Delete this farmer?')"
    >
        @csrf
        @method('DELETE')

        <button
            type="submit"
            class="btn btn-sm btn-fams-outline text-danger"
            title="Delete Farmer"
        >
            <i class="bi bi-trash"></i>
        </button>
    </form>
</td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="fams-empty"><i class="bi bi-people"></i>No farmers registered</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($farmers->hasPages())
  <div class="fams-card-body border-top py-3">{{ $farmers->links() }}</div>
  @endif
</div>
@endsection
