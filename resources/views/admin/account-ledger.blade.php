@extends('layouts.admin')

@section('title', $account['name_en'] . ' — Mind Craft Island Admin')

@section('breadcrumb', 'Accounts › ' . $account['name_en'])

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <p style="margin: 0 0 0.3rem;"><a href="{{ route('admin.accounts') }}">&larr; Accounts</a></p>
        <h1>
            {{ $account['name_en'] }}
            <span class="badge-filtered" style="font-size: 0.85rem; vertical-align: middle;">{{ \App\Http\Controllers\AccountController::TYPE_LABELS[$account['type']] ?? ucfirst($account['type']) }}</span>
        </h1>
        <p class="page-subtitle">{{ $account['name_ar'] }}{{ $account['is_active'] ? '' : ' — Inactive' }}</p>
    </div>
    <div style="text-align: right;">
        <p style="margin: 0; color: #8a9ab0; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.4px;">Current Balance</p>
        <p style="margin: 0.15rem 0 0; font-size: 1.8rem; font-weight: 800; color: {{ ($balance ?? 0) < 0 ? '#c0392b' : '#1a8a4a' }};">
            {{ $balance !== null ? number_format((float) $balance, 2) : '—' }}
        </p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

<form method="GET" action="{{ route('admin.accounts.show', $account['id']) }}" class="search-bar" style="flex-wrap: wrap;" id="ledger-filter-form">
    <input type="date" name="date_from" class="search-input" style="max-width: 160px;" value="{{ $dateFrom }}" title="From date">
    <input type="date" name="date_to" class="search-input" style="max-width: 160px;" value="{{ $dateTo }}" title="To date">
    <select name="direction" class="search-input" style="max-width: 150px;">
        <option value="">Direction</option>
        @foreach($directions as $d)
        <option value="{{ $d }}" {{ $direction === $d ? 'selected' : '' }}>{{ ucfirst($d) }}</option>
        @endforeach
    </select>
    <select name="category" class="search-input" style="max-width: 200px;">
        <option value="">Category</option>
        @foreach($categories as $cat)
        <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary" style="padding: 0.62rem 1.25rem; font-size: 0.92rem;">Filter</button>
    <button type="button" class="btn btn-secondary" id="ledger-filter-clear-all" style="padding: 0.62rem 1.25rem; font-size: 0.92rem; display: {{ $dateFrom !== '' || $dateTo !== '' || $direction !== '' || $category !== '' ? 'inline-block' : 'none' }};">Clear Filters</button>
</form>

<div id="results-panel">
    @include('admin.partials.account-ledger-results')
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('results-panel');
        var form = document.getElementById('ledger-filter-form');
        var dateFromInput = form.querySelector('input[name="date_from"]');
        var dateToInput = form.querySelector('input[name="date_to"]');
        var directionSelect = form.querySelector('select[name="direction"]');
        var categorySelect = form.querySelector('select[name="category"]');
        var clearAllBtn = document.getElementById('ledger-filter-clear-all');
        var ledgerUrl = '{{ route("admin.accounts.show", $account["id"]) }}';

        function syncFormFromUrl(url) {
            var params = new URL(url, window.location.origin).searchParams;
            dateFromInput.value = params.get('date_from') || '';
            dateToInput.value = params.get('date_to') || '';
            directionSelect.value = params.get('direction') || '';
            categorySelect.value = params.get('category') || '';
            var hasFilters = dateFromInput.value !== '' || dateToInput.value !== '' || directionSelect.value !== '' || categorySelect.value !== '';
            clearAllBtn.style.display = hasFilters ? 'inline-block' : 'none';
        }

        function loadUrl(url) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    panel.innerHTML = html;
                    syncFormFromUrl(url);
                    // Keep the address bar bare — no filter params ever show
                    // up in the URL, by design.
                    window.history.replaceState(null, '', ledgerUrl);
                });
        }

        function submitFilters() {
            var params = new URLSearchParams(new FormData(form));
            params.set('page', '1');
            loadUrl(ledgerUrl + '?' + params.toString());
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            submitFilters();
        });

        clearAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            loadUrl(ledgerUrl);
        });

        document.addEventListener('click', function (e) {
            var link = e.target.closest('a.ajax-nav');
            if (!link || !panel.contains(link)) return;
            e.preventDefault();
            loadUrl(link.href);
        });
    });
</script>
@endpush
