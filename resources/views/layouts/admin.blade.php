@extends('layouts.app')

@section('nav-links')
<!-- <a href="{{ route('register') }}">Registration Form</a> -->
<form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
    @csrf
    <button type="submit" class="btn-logout">Log Out</button>
</form>
@endsection

@section('content')
<div class="admin-layout" id="admin-layout">
    <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="admin-sidebar">
        <span></span><span></span><span></span>
    </button>

    <div class="admin-sidebar-backdrop" id="admin-sidebar-backdrop"></div>

    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="{{ route('register') }}" class="admin-sidebar-brand">
                <img src="{{ asset('favicons/android-chrome-192x192.png') }}" alt="" class="admin-sidebar-logo">
                <span class="admin-sidebar-brand-text">Mind Craft</span>
            </a>
            <button type="button" class="admin-sidebar-collapse" id="admin-sidebar-collapse" aria-label="Collapse menu" aria-expanded="true">
                &#8249;
            </button>
        </div>

        <nav class="admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" title="Dashboard">
                <span class="admin-nav-icon">&#128202;</span>
                <span class="admin-nav-label">Dashboard</span>
            </a>
            <a href="{{ route('admin.registrations') }}" class="admin-nav-link {{ request()->routeIs('admin.registrations') ? 'active' : '' }}" title="Registrations">
                <span class="admin-nav-icon">&#128203;</span>
                <span class="admin-nav-label">Registrations</span>
            </a>
        </nav>
    </aside>

    <div class="admin-content">
        <div class="container-wide">

            <nav class="admin-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
                <span class="breadcrumb-sep">&rsaquo;</span>
                <span class="breadcrumb-current">@yield('breadcrumb', 'Dashboard')</span>
            </nav>

            @if(session('error'))
            <div class="alert alert-error">
                <span>&#9888;</span>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @yield('admin-content')

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var layout = document.getElementById('admin-layout');
        var toggle = document.getElementById('admin-menu-toggle');
        var sidebar = document.getElementById('admin-sidebar');
        var backdrop = document.getElementById('admin-sidebar-backdrop');
        var collapseBtn = document.getElementById('admin-sidebar-collapse');

        function closeSidebar() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        function openSidebar() {
            sidebar.classList.add('open');
            backdrop.classList.add('open');
            toggle.setAttribute('aria-expanded', 'true');
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                if (sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeSidebar);
        }

        sidebar.querySelectorAll('.admin-nav-link').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });

        if (collapseBtn) {
            collapseBtn.addEventListener('click', function () {
                var collapsed = layout.classList.toggle('admin-layout-collapsed');
                collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            });
        }
    });
</script>
@endpush
