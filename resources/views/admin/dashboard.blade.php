@extends('layouts.app')

@section('title', 'Dashboard — MindCraft Island Admin')

@section('nav-links')
    <a href="{{ route('register') }}">Public Form</a>
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
        <h1>Registrations <span class="badge-count">{{ count($registrations) }}</span></h1>
        <p class="page-subtitle">All submitted registration forms — sorted by newest first.</p>
    </div>

    {{-- Stats --}}
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-value">{{ count($registrations) }}</div>
            <div class="stat-label">Total Registrations</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--sky);">
                {{ collect($registrations)->filter(fn($r) => isset($r['created_at']) && \Carbon\Carbon::parse($r['created_at'])->isToday())->count() }}
            </div>
            <div class="stat-label">Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--green);">
                {{ collect($registrations)->filter(fn($r) => isset($r['created_at']) && \Carbon\Carbon::parse($r['created_at'])->isCurrentWeek())->count() }}
            </div>
            <div class="stat-label">This Week</div>
        </div>
    </div>

    {{-- Table --}}
    @if(count($registrations) === 0)
        <div class="card empty-state">
            <div class="empty-icon">&#128203;</div>
            <p style="font-weight: 700; font-size: 1.1rem;">No registrations yet.</p>
            <p style="margin-top: 0.4rem; color: #8a9ab0;">Share the <a href="{{ route('register') }}">registration form</a> to get started.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Phone Number</th>
                        <th>Father's Name</th>
                        <th>Mother's Name</th>
                        <th>Date of Birth</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registrations as $index => $reg)
                        <tr>
                            <td style="color: #8a9ab0; font-weight: 700;">{{ $index + 1 }}</td>
                            <td style="font-weight: 700;">{{ $reg['full_name'] ?? '—' }}</td>
                            <td>{{ $reg['phone_number'] ?? '—' }}</td>
                            <td>{{ $reg['father_name'] ?? '—' }}</td>
                            <td>{{ $reg['mother_name'] ?? '—' }}</td>
                            <td>
                                @if(isset($reg['date_of_birth']))
                                    {{ \Carbon\Carbon::parse($reg['date_of_birth'])->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="color: #8a9ab0; font-size: 0.83rem;">
                                @if(isset($reg['created_at']))
                                    {{ \Carbon\Carbon::parse($reg['created_at'])->format('d M Y, H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
