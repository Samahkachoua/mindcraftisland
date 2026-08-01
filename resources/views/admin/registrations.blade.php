@extends('layouts.admin')

@section('title', 'Registrations — Mind Craft Island Admin')

@section('breadcrumb', 'Registrations Overview')

@section('admin-content')

{{-- Header --}}
<div style="margin-bottom: 1.75rem;">
    <h1>Registrations <span class="badge-count">{{ $total }}</span></h1>
    <p class="page-subtitle">All submitted registration forms — sorted by newest first.</p>
</div>

{{-- Search --}}
<div class="search-bar">
    <form method="GET" action="{{ route('admin.registrations') }}" id="dashboard-search-form" style="display:contents;">
        <div class="search-input-wrap">
            <input
                type="text"
                name="search"
                id="dashboard-search-input"
                class="search-input"
                placeholder="Search by name or phone…"
                value="{{ $search }}"
                autocomplete="off">
            <a href="{{ route('admin.registrations') }}" class="search-clear" id="dashboard-search-clear" title="Clear search" style="display: {{ $search !== '' ? 'inline' : 'none' }};">&#215;</a>
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
        var registrationsUrl = '{{ route('admin.registrations') }}';

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
                    window.history.replaceState(null, '', registrationsUrl);
                });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var params = new URLSearchParams(new FormData(form));
                params.set('page', '1');
                loadUrl(registrationsUrl + '?' + params.toString());
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                searchInput.value = '';
                var params = new URLSearchParams(new FormData(form));
                params.set('page', '1');
                loadUrl(registrationsUrl + '?' + params.toString());
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
