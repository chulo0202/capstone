<div class="d-flex flex-wrap gap-2">
    @foreach ($incident->damagePhotoPaths() as $index => $path)
        <a href="{{ route('admin.pcic-incidents.damage-photo', ['incident' => $incident, 'photo' => $index + 1]) }}"
           target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-image me-1" aria-hidden="true"></i> View Damage Photo {{ $index + 1 }}
        </a>
    @endforeach
</div>
