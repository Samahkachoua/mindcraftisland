@extends('layouts.admin')

@section('title', 'Dashboard — Mind Craft Island Admin')

@section('breadcrumb', 'Dashboard Overview')

@section('admin-content')

{{-- Header --}}
<div style="margin-bottom: 1.75rem;">
    <h1>Dashboard</h1>
    <p class="page-subtitle">Overview of all submitted registration forms.</p>
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

@endsection
