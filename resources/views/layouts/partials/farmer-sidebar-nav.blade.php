<div class="fams-nav-label">Main</div>
<a class="fams-nav-link {{ request()->routeIs('farmer.dashboard') ? 'active' : '' }}" href="{{ route('farmer.dashboard') }}">
    <i class="bi bi-house-door-fill"></i> Dashboard
</a>
<a class="fams-nav-link {{ request()->routeIs('farmer.profile*') ? 'active' : '' }}" href="{{ route('farmer.profile') }}">
    <i class="bi bi-person-badge-fill"></i> My Profile
</a>

<div class="fams-nav-label">Programs</div>
<a class="fams-nav-link {{ request()->routeIs('farmer.eligibility') ? 'active' : '' }}" href="{{ route('farmer.eligibility') }}">
    <i class="bi bi-check-circle-fill"></i> Eligibility
</a>
<a class="fams-nav-link {{ request()->routeIs('farmer.recommendations') ? 'active' : '' }}" href="{{ route('farmer.recommendations') }}">
    <i class="bi bi-lightbulb-fill"></i> Recommendations
</a>
<a class="fams-nav-link {{ request()->routeIs('farmer.applications.*') ? 'active' : '' }}" href="{{ route('farmer.applications.index') }}">
    <i class="bi bi-file-earmark-check-fill"></i> My Applications
</a>
<a class="fams-nav-link {{ request()->routeIs('farmer.qr-code*') ? 'active' : '' }}" href="{{ route('farmer.qr-code') }}">
    <i class="bi bi-qr-code"></i> My QR Code
</a>

<div class="fams-nav-label">Updates</div>
<a class="fams-nav-link {{ request()->routeIs('farmer.notifications.*') ? 'active' : '' }}" href="{{ route('farmer.notifications.index') }}">
    <i class="bi bi-bell-fill"></i> Notifications
</a>
<a class="fams-nav-link {{ request()->routeIs('farmer.announcements') ? 'active' : '' }}" href="{{ route('farmer.announcements') }}">
    <i class="bi bi-megaphone-fill"></i> Announcements
</a>
