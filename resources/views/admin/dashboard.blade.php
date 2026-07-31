@extends('layouts.app')

@section('title', 'Dashboard — Mind Craft Island Admin')

@section('nav-links')
<a href="{{ route('register') }}">Registration Form</a>
<form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
    @csrf
    <button type="submit" class="btn-logout">Log Out</button>
</form>
@endsection

@section('content')
<div class="container-wide">

    @if(session('error'))
    <div class="alert alert-error">
        <span>&#9888;</span>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Header --}}
    <div style="margin-bottom: 1.75rem;">
        <h1>Registrations <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">All submitted registration forms — sorted by newest first.</p>
    </div>

    {{-- Stats --}}
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-value">{{ $total }}</div>
            <div class="stat-label">Total Registrations</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--sky);">{{ $todayCount }}</div>
            <div class="stat-label">Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--green);">{{ $weekCount }}</div>
            <div class="stat-label">This Week</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--coral);">{{ $childCount }}</div>
            <div class="stat-label">Total Children</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--gold);">{{ $ladyCount }}</div>
            <div class="stat-label">Total Ladies</div>
        </div>
    </div>

    {{-- Search --}}
    <div class="search-bar">
        <form method="GET" action="{{ route('admin.dashboard') }}" id="dashboard-search-form" style="display:contents;">
            <div class="search-input-wrap">
                <input
                    type="text"
                    name="search"
                    id="dashboard-search-input"
                    class="search-input"
                    placeholder="Search by name or phone…"
                    value="{{ $search }}"
                    autocomplete="off">
                <a href="{{ route('admin.dashboard') }}" class="search-clear" id="dashboard-search-clear" title="Clear search" style="display: {{ $search !== '' ? 'inline' : 'none' }};">&#215;</a>
            </div>
            <select name="type" class="search-input" style="max-width: 160px;">
                <option value="" {{ $type === '' ? 'selected' : '' }}>All Types</option>
                <option value="child" {{ $type === 'child' ? 'selected' : '' }}>Child</option>
                <option value="lady" {{ $type === 'lady' ? 'selected' : '' }}>Lady</option>
            </select>
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <button type="submit" class="btn btn-secondary" style="padding: 0.62rem 1.25rem; font-size: 0.92rem;">Search</button>
        </form>
    </div>

    {{-- Table --}}
    <div id="results-panel">
        @include('admin.partials.results')
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('results-panel');
        var form = document.getElementById('dashboard-search-form');
        var searchInput = document.getElementById('dashboard-search-input');
        var typeSelect = form.querySelector('select[name="type"]');
        var sortField = form.querySelector('input[name="sort"]');
        var directionField = form.querySelector('input[name="direction"]');
        var clearBtn = document.getElementById('dashboard-search-clear');
        var dashboardUrl = '{{ route('admin.dashboard') }}';

        function syncFormFromUrl(url) {
            var params = new URL(url, window.location.origin).searchParams;
            searchInput.value = params.get('search') || '';
            typeSelect.value = params.get('type') || '';
            sortField.value = params.get('sort') || sortField.value;
            directionField.value = params.get('direction') || directionField.value;
            clearBtn.style.display = searchInput.value.trim() !== '' ? 'inline' : 'none';
        }

        function loadUrl(url) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    panel.innerHTML = html;
                    syncFormFromUrl(url);
                    window.history.replaceState(null, '', dashboardUrl);
                });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var params = new URLSearchParams(new FormData(form));
                params.set('page', '1');
                loadUrl(dashboardUrl + '?' + params.toString());
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                searchInput.value = '';
                var params = new URLSearchParams(new FormData(form));
                params.set('page', '1');
                loadUrl(dashboardUrl + '?' + params.toString());
            });
        }

        document.addEventListener('click', function (e) {
            var link = e.target.closest('a.ajax-nav');
            if (!link || !panel.contains(link)) return;
            e.preventDefault();
            loadUrl(link.href);
        });
    });
</script>
@endpush