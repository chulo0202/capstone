<div class="fams-app">
    <div class="fams-sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="fams-sidebar" id="sidebar">
        <div class="fams-sidebar-brand">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="brand-icon"><i class="bi bi-tree-fill"></i></div>
                    <h5>{{ $sidebarTitle ?? 'FAMS' }}</h5>
                    <small>{{ $sidebarSubtitle ?? 'Management System' }}</small>
                </div>
                <button type="button" class="fams-sidebar-close d-lg-none" id="sidebarClose" aria-label="Close menu">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <nav class="fams-sidebar-nav" id="sidebarNav">
            @include($sidebarNav ?? 'layouts.partials.admin-sidebar-nav')
        </nav>

        <div class="fams-sidebar-footer">
            &copy; {{ date('Y') }} Municipal Agriculture Office
        </div>
    </aside>

    <div class="fams-content">
        <header class="fams-topbar">
            <button type="button" class="fams-sidebar-toggle d-lg-none" id="sidebarToggle" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>

            <div class="fams-topbar-title flex-grow-1">
                <h1>@yield('page-title', 'Dashboard')</h1>
                @hasSection('page-subtitle')
                    <p>@yield('page-subtitle')</p>
                @endif
            </div>

            <div class="fams-user-chip d-none d-sm-flex">
                <div class="fams-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div>
                    <div class="fams-user-name">{{ auth()->user()->name }}</div>
                    <div class="fams-user-role">{{ auth()->user()->role }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-fams-outline text-danger border-danger-subtle">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-md-inline ms-1">Logout</span>
                </button>
            </form>
        </header>

        <main class="fams-main">
            @include('layouts.partials.alerts')
            @yield('main')
        </main>
    </div>
</div>

<script>
(function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');
    const nav = document.getElementById('sidebarNav');

    function openSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.classList.add('fams-sidebar-open');
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.classList.remove('fams-sidebar-open');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', openSidebar);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    if (nav) {
        nav.querySelectorAll('a.fams-nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
})();
</script>
