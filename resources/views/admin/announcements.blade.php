@extends('layouts.admin')

@section('page-title', 'Announcement Management')

@section('main')
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Create Announcement</h6></div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.announcements.store') }}">
          @csrf
          <div class="mb-2"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="4" required></textarea></div>
          <div class="mb-2"><label class="form-label">Publish Date</label><input type="datetime-local" name="publish_date" class="form-control" required></div>
          <div class="form-check mb-2"><input type="checkbox" name="sms_enabled" value="1" class="form-check-input" id="sms"><label class="form-check-label" for="sms">SMS Enabled</label></div>
          <div class="form-check mb-3"><input type="checkbox" name="is_published" value="1" class="form-check-input" id="pub"><label class="form-check-label" for="pub">Publish Now</label></div>
          <button class="btn btn-fams w-100">Create</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white"><h6 class="mb-0">Announcements</h6></div>
      <div class="list-group list-group-flush">
        @forelse($announcements as $announcement)
        <div class="list-group-item">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>{{ $announcement->title }}</strong>
              <span class="badge bg-{{ $announcement->is_published ? 'success' : 'secondary' }} ms-1">{{ $announcement->is_published ? 'Published' : 'Draft' }}</span>
              @if($announcement->sms_enabled)<span class="badge bg-info ms-1">SMS</span>@endif
              <p class="mb-1 small">{{ Str::limit($announcement->content, 100) }}</p>
              <small class="text-muted">{{ $announcement->publish_date->format('M d, Y h:i A') }}</small>
            </div>
            <div>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ann{{ $announcement->id }}">Edit</button>
              <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
            </div>
          </div>
        </div>
        <div class="modal fade" id="ann{{ $announcement->id }}" tabindex="-1">
          <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="modal-content">
              @csrf @method('PUT')
              <div class="modal-header"><h5 class="modal-title">Edit Announcement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <div class="mb-2"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="{{ $announcement->title }}" required></div>
                <div class="mb-2"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="4" required>{{ $announcement->content }}</textarea></div>
                <div class="mb-2"><label class="form-label">Publish Date</label><input type="datetime-local" name="publish_date" class="form-control" value="{{ $announcement->publish_date->format('Y-m-d\TH:i') }}" required></div>
                <div class="form-check mb-2"><input type="checkbox" name="sms_enabled" value="1" class="form-check-input" {{ $announcement->sms_enabled ? 'checked' : '' }}><label class="form-check-label">SMS Enabled</label></div>
                <div class="form-check"><input type="checkbox" name="is_published" value="1" class="form-check-input" {{ $announcement->is_published ? 'checked' : '' }}><label class="form-check-label">Published</label></div>
              </div>
              <div class="modal-footer"><button class="btn btn-fams">Save</button></div>
            </form>
          </div>
        </div>
        @empty
        <div class="list-group-item text-muted">No announcements</div>
        @endforelse
      </div>
      @if($announcements->hasPages())<div class="card-footer bg-white">{{ $announcements->links() }}</div>@endif
    </div>
  </div>
</div>
@endsection
