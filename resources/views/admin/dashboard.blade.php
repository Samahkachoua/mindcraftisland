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
    </div>

    {{-- Search --}}
    <div class="search-bar">
        <form method="GET" action="{{ route('admin.dashboard') }}" style="display:contents;">
            <div class="search-input-wrap">
                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search by name or phone…"
                    value="{{ $search }}"
                    autocomplete="off">
                @if($search !== '')
                <a href="{{ route('admin.dashboard', array_merge(request()->except(['search','page']), ['sort' => $sort, 'direction' => $direction])) }}" class="search-clear" title="Clear search">&#215;</a>
                @endif
            </div>
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <button type="submit" class="btn btn-secondary" style="padding: 0.62rem 1.25rem; font-size: 0.92rem;">Search</button>
        </form>
    </div>

    {{-- Table --}}
    @if($registrations->total() === 0)
    <div class="card empty-state">
        <div class="empty-icon">&#128203;</div>
        @if($search !== '')
        <p style="font-weight: 700; font-size: 1.1rem;">No results for "{{ $search }}".</p>
        <p style="margin-top: 0.4rem; color: #8a9ab0;"><a href="{{ route('admin.dashboard') }}">Clear search</a> to see all registrations.</p>
        @else
        <p style="font-weight: 700; font-size: 1.1rem;">No registrations yet.</p>
        <p style="margin-top: 0.4rem; color: #8a9ab0;">Share the <a href="{{ route('register') }}">Registration Form</a> to get started.</p>
        @endif
    </div>
    @else

    @php
    $cols = [
    'full_name' => 'Full Name',
    'date_of_birth' => 'Age',
    'created_at' => 'Submitted At',
    ];
    $fixed = ['Phone Number', "Father's Name", "Mother's Name", 'Medical Conditions','Field of Interests'];

    function sortUrl(string $col, string $currentSort, string $currentDir): string {
    $newDir = ($col === $currentSort && $currentDir === 'asc') ? 'desc' : 'asc';
    return request()->fullUrlWithQuery(['sort' => $col, 'direction' => $newDir, 'page' => 1]);
    }

    function sortIcon(string $col, string $currentSort, string $currentDir): string {
    if ($col !== $currentSort) return '<span class="sort-icon">&#8597;</span>';
    return $currentDir === 'asc'
    ? '<span class="sort-icon sort-active">&#8593;</span>'
    : '<span class="sort-icon sort-active">&#8595;</span>';
    }
    @endphp

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    @foreach($cols as $key => $label)
                    <th>
                        <a href="{{ sortUrl($key, $sort, $direction) }}" class="th-sort">
                            {{ $label }}{!! sortIcon($key, $sort, $direction) !!}
                        </a>
                    </th>
                    @endforeach
                    @foreach($fixed as $label)
                    <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($registrations as $reg)
                <tr>
                    <td style="font-weight: 700;">{{ $reg['full_name'] ?? '—' }}</td>
                    <td>
                        @if(isset($reg['date_of_birth']))
                        {{ \Carbon\Carbon::parse($reg['date_of_birth'])->age }} years
                        @else —
                        @endif
                    </td>
                    <td style="color: #8a9ab0; font-size: 0.83rem;">
                        @if(isset($reg['created_at']))
                        {{ \Carbon\Carbon::parse($reg['created_at'])->format('d M Y, H:i') }}
                        @else —
                        @endif
                    </td>
                    <td>{{ $reg['phone_number'] ?? '—' }}</td>
                    <td>{{ $reg['father_name'] ?? '—' }}</td>
                    <td>{{ $reg['mother_name'] ?? '—' }}</td>
                    <td>{{ $reg['medical_conditions'] ?? '—' }}</td>
                    <td>{{ $reg['field_of_interests'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($registrations->lastPage() > 1)
    <div class="pagination-bar">
        <div class="page-info">
            Showing {{ $registrations->firstItem() }}–{{ $registrations->lastItem() }} of {{ $registrations->total() }} registrations
            @if($search !== '') <span class="badge-filtered">(filtered)</span> @endif
        </div>
        <div class="page-buttons">
            {{-- Prev --}}
            @if($registrations->onFirstPage())
            <span class="page-btn disabled">&#8592;</span>
            @else
            <a href="{{ $registrations->previousPageUrl() }}" class="page-btn">&#8592;</a>
            @endif

            {{-- Page numbers --}}
            @php
            $current = $registrations->currentPage();
            $last = $registrations->lastPage();
            $window = 2;
            $pages = collect();
            for ($i = max(1, $current - $window); $i <= min($last, $current + $window); $i++) {
                $pages->push($i);
                }
                $showLeadingEllipsis = $pages->first() > 2;
                $showTrailingEllipsis = $pages->last() < $last - 1;
                    @endphp

                    @if($pages->first() > 1)
                    <a href="{{ $registrations->url(1) }}" class="page-btn">1</a>
                    @endif
                    @if($showLeadingEllipsis)
                    <span class="page-btn disabled">&hellip;</span>
                    @endif

                    @foreach($pages as $p)
                    @if($p === $current)
                    <span class="page-btn active">{{ $p }}</span>
                    @else
                    <a href="{{ $registrations->url($p) }}" class="page-btn">{{ $p }}</a>
                    @endif
                    @endforeach

                    @if($showTrailingEllipsis)
                    <span class="page-btn disabled">&hellip;</span>
                    @endif
                    @if($pages->last() < $last)
                        <a href="{{ $registrations->url($last) }}" class="page-btn">{{ $last }}</a>
                        @endif

                        {{-- Next --}}
                        @if($registrations->hasMorePages())
                        <a href="{{ $registrations->nextPageUrl() }}" class="page-btn">&#8594;</a>
                        @else
                        <span class="page-btn disabled">&#8594;</span>
                        @endif
        </div>
    </div>
    @else
    <div class="page-info" style="margin-top: 1rem;">
        Showing all {{ $registrations->total() }} registration{{ $registrations->total() !== 1 ? 's' : '' }}
        @if($search !== '') <span class="badge-filtered">(filtered)</span> @endif
    </div>
    @endif

    @endif

</div>
@endsection