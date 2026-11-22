@extends('layouts.farmer')

@section('page-title', 'Notifications')
@section('page-subtitle', 'Distribution schedules, application updates, and messages')

@section('main')
@php
    $categories = [
        'distribution' => ['Distribution Updates', 'bi-box-seam', $schedules->count() + $distributions->count()],
        'pcic' => ['PCIC Updates', 'bi-shield-check', $pcicUpdates->count()],
        'announcement' => ['Announcements', 'bi-megaphone', $announcements->count()],
        'sms' => ['SMS History', 'bi-chat-left-text', $notifications->count()],
    ];
    $total = array_sum(array_column($categories, 2));
@endphp

<style>
    .farmer-notifications [hidden] { display: none !important; }
    .farmer-notifications .feed-row { min-width: 0; }
    .farmer-notifications .feed-row:hover { background: #f0f8f3; }
    .farmer-notifications .feed-row:focus-visible { outline: 2px solid #198754; outline-offset: 2px; }
    .farmer-notifications .feed-icon {
        width: 2.5rem; height: 2.5rem; display: grid; place-items: center;
        border-radius: .75rem; background: #e7f4eb; color: #17633f;
    }
</style>

<div class="farmer-notifications">
    <div class="d-flex gap-2 flex-wrap mb-3" role="group" aria-label="Filter notifications">
        @foreach (['all' => ['All', '', $total], ...$categories] as $key => [$label, $icon, $count])
            <button type="button" class="btn btn-sm rounded-pill px-3 {{ $key === 'all' ? 'btn-fams' : 'btn-outline-secondary' }}"
                data-notification-filter="{{ $key }}" aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}">
                {{ $key === 'distribution' ? 'Distribution' : ($key === 'pcic' ? 'PCIC' : ($key === 'sms' ? 'SMS' : $label)) }}
                <span class="badge text-bg-light ms-1">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    @foreach ($categories as $key => [$label, $icon, $count])
        <section class="card shadow-sm mb-3" data-notification-category="{{ $key }}" data-count="{{ $count }}"
            aria-labelledby="notification-{{ $key }}" @if (!$count && ($total || $key !== 'distribution')) hidden @endif>
            <div class="card-body p-3 p-md-4">
                <h2 class="h6 mb-3 d-flex align-items-center gap-2" id="notification-{{ $key }}">
                    <i class="bi {{ $icon }} text-success" aria-hidden="true"></i> {{ $label }}
                </h2>
                <div class="d-grid gap-2">
                    @if ($key === 'distribution')
                        @foreach ($schedules as $schedule)
                            @include('farmer.partials.notification-item', [
                                'href' => route('farmer.notifications.distribution-schedule', $schedule),
                                'itemIcon' => 'bi-calendar-event', 'title' => $schedule->batch->name,
                                'badge' => 'Scheduled', 'badgeStyle' => 'text-bg-primary',
                                'description' => $schedule->batch->benefit->name.' · '.$schedule->batch->scheduled_date->format('M j, Y'),
                                'action' => 'View schedule',
                            ])
                        @endforeach
                        @foreach ($distributions as $distribution)
                            @include('farmer.partials.notification-item', [
                                'href' => route('farmer.notifications.distribution-receipt', $distribution),
                                'itemIcon' => 'bi-box-seam', 'title' => $distribution->program->name,
                                'badge' => 'Benefit Received', 'badgeStyle' => 'text-bg-success',
                                'description' => ($distribution->benefit?->name ?? 'Program-level benefit').' · '.$distribution->distributed_at->format('M j, Y'),
                                'action' => 'View details',
                            ])
                        @endforeach
                    @elseif ($key === 'pcic')
                        @foreach ($pcicUpdates as $update)
                            @include('farmer.partials.notification-item', [
                                'href' => route('farmer.applications.index').'#application-'.$update->application_id,
                                'itemIcon' => $icon,
                                'title' => ($update->application->benefit?->name ?? 'PCIC application').' · '.\App\Models\PcicTrackingUpdate::STATUSES[$update->status],
                                'description' => collect([
                                    $update->created_at->format('M j, Y, g:i A'), $update->remarks,
                                    $update->expected_release_date ? 'Expected release '.$update->expected_release_date->format('M j, Y') : null,
                                ])->filter()->implode("\n"),
                                'action' => 'View application',
                            ])
                        @endforeach
                    @elseif ($key === 'announcement')
                        @foreach ($announcements as $announcement)
                            @include('farmer.partials.notification-item', [
                                'itemIcon' => $icon, 'title' => $announcement->title,
                                'description' => Str::limit($announcement->content, 180),
                            ])
                        @endforeach
                    @else
                        @foreach ($notifications as $notification)
                            @include('farmer.partials.notification-item', [
                                'itemIcon' => $icon, 'title' => $notification->recipient,
                                'description' => $notification->message,
                            ])
                        @endforeach
                    @endif
                    @unless ($count)
                        <p class="text-muted small mb-0">
                            {{ $key === 'distribution' && !$total ? 'No notifications yet.' : 'No updates in this category yet.' }}
                        </p>
                    @endunless
                </div>
            </div>
        </section>
    @endforeach
</div>
<script>
    (() => {
        const filters = [...document.querySelectorAll('[data-notification-filter]')];
        const sections = [...document.querySelectorAll('[data-notification-category]')];
        filters.forEach(button => button.addEventListener('click', () => {
            const category = button.dataset.notificationFilter;
            filters.forEach(filter => {
                const selected = filter === button;
                filter.setAttribute('aria-pressed', selected);
                filter.classList.toggle('btn-fams', selected);
                filter.classList.toggle('btn-outline-secondary', !selected);
            });
            const hasItems = sections.some(section => Number(section.dataset.count) > 0);
            sections.forEach((section, index) => {
                section.hidden = category === 'all'
                    ? Number(section.dataset.count) === 0 && (hasItems || index !== 0)
                    : category !== section.dataset.notificationCategory;
            });
        }));
    })();
</script>
@endsection
