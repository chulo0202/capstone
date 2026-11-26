@php($tag = isset($href) ? 'a' : 'div')
<{{ $tag }} @isset($href) href="{{ $href }}" @endisset
    class="feed-row d-flex align-items-start gap-3 p-3 border rounded-3 text-decoration-none text-body">
    <span class="feed-icon flex-shrink-0"><i class="bi {{ $itemIcon }}" aria-hidden="true"></i></span>
    <span class="flex-grow-1 text-break">
        @isset($badge)<span class="badge {{ $badgeStyle }} mb-1">{{ $badge }}</span>@endisset
        <strong class="d-block">{{ $title }}</strong>
        <span class="d-block small text-muted" style="white-space: pre-line">{{ $description }}</span>
        @isset($action)<span class="d-block mt-1 small fw-semibold text-success">{{ $action }}</span>@endisset
    </span>
    @isset($href)<i class="bi bi-chevron-right text-muted align-self-center" aria-hidden="true"></i>@endisset
</{{ $tag }}>

@isset($attachmentHref)
    <a href="{{ $attachmentHref }}" class="btn btn-sm btn-outline-success align-self-start">
        <i class="bi bi-download me-1" aria-hidden="true"></i> Download decision attachment
    </a>
@endisset
