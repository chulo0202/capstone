<div class="fams-nav-label">Overview</div>

<a class="fams-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
   href="{{ route('admin.dashboard') }}">
    <i class="bi bi-grid-1x2-fill"></i> Dashboard
</a>

<a class="fams-nav-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}"
   href="{{ route('admin.analytics.index') }}">
    <i class="bi bi-bar-chart-line-fill"></i> Analytics
</a>


<div class="fams-nav-label">Management</div>

<a class="fams-nav-link {{ request()->routeIs('admin.farmers.*') ? 'active' : '' }}"
   href="{{ route('admin.farmers.index') }}">
    <i class="bi bi-people-fill"></i> Farmers
</a>

<a class="fams-nav-link {{ request()->routeIs('admin.applications.*') ? 'active' : '' }}"
   href="{{ route('admin.applications.index') }}">
    <i class="bi bi-file-earmark-text-fill"></i> Applications
</a>

<a class="fams-nav-link {{ request()->routeIs('admin.programs.create') ? 'active' : '' }}"
   href="{{ route('admin.programs.create') }}">
    <i class="bi bi-journal-plus"></i> Program
</a>

<a class="fams-nav-link {{ request()->routeIs('admin.programs.index') ? 'active' : '' }}"
   href="{{ route('admin.programs.index') }}">
    <i class="bi bi-list-ul"></i> Program List
</a>

<div class="fams-nav-label">Operations</div>

<a class="fams-nav-link {{ request()->routeIs('admin.distributions.*') ? 'active' : '' }}"
   href="{{ route('admin.distributions.index') }}">
    <i class="bi bi-qr-code-scan"></i> Distributions
</a>

<a class="fams-nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}"
   href="{{ route('admin.announcements.index') }}">
    <i class="bi bi-megaphone-fill"></i> Announcements
</a>

<a class="fams-nav-link {{ request()->routeIs('admin.sms.*') ? 'active' : '' }}"
   href="{{ route('admin.sms.index') }}">
    <i class="bi bi-chat-left-text-fill"></i> SMS Notifications
</a>

<a class="fams-nav-link {{ request()->routeIs('admin.weather.*') ? 'active' : '' }}"
   href="{{ route('admin.weather.index') }}">
    <i class="bi bi-cloud-sun-fill"></i> Weather
</a>

