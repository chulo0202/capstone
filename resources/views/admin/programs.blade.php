@extends('layouts.admin')

@section('page-title', 'Program Management')

@section('main')
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Add Program</h6></div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.programs.store') }}">
          @csrf
          <div class="mb-2">
            <label class="form-label">Program Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" required></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label">Eligibility Criteria</label>
            <textarea name="eligibility_criteria" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked>
            <label class="form-check-label" for="is_active">Active</label>
          </div>
          <button class="btn btn-fams w-100">Add Program</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Programs List</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              @forelse($programs as $program)
              <tr>
                <td>
                  <strong>{{ $program->name }}</strong>
                  <br><small class="text-muted">{{ Str::limit($program->description, 60) }}</small>
                </td>
                <td><span class="badge bg-{{ $program->is_active ? 'success' : 'secondary' }}">{{ $program->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit{{ $program->id }}">Edit</button>
                  <form action="{{ route('admin.programs.toggle', $program) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning">Toggle</button></form>
                  <form action="{{ route('admin.programs.destroy', $program) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </td>
              </tr>
              <div class="modal fade" id="edit{{ $program->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form method="POST" action="{{ route('admin.programs.update', $program) }}" class="modal-content">
                    @csrf @method('PUT')
                    <div class="modal-header"><h5 class="modal-title">Edit Program</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                      <div class="mb-2"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="{{ $program->name }}" required></div>
                      <div class="mb-2"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" required>{{ $program->description }}</textarea></div>
                      <div class="mb-2"><label class="form-label">Eligibility Criteria</label><textarea name="eligibility_criteria" class="form-control" rows="2">{{ $program->eligibility_criteria }}</textarea></div>
                      <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $program->is_active ? 'checked' : '' }}><label class="form-check-label">Active</label></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-fams">Save Changes</button></div>
                  </form>
                </div>
              </div>
              @empty
              <tr><td colspan="3" class="text-center text-muted py-4">No programs yet</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if($programs->hasPages())<div class="card-footer bg-white">{{ $programs->links() }}</div>@endif
    </div>
  </div>
</div>
@endsection
