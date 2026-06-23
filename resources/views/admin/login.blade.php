@extends('layouts.app')

@section('title', 'Admin Login — Mind Craft Island')

@section('nav-links')
<a href="{{ route('register') }}">Public Form</a>
@endsection

@section('content')
<div class="container" style="max-width: 430px;">

    @if(session('error'))
    <div class="alert alert-error">
        <span>&#9888;</span>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="card">
        <h1>Admin Login</h1>
        <p class="page-subtitle">Mind Craft Island — Staff Portal</p>

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf

            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username') }}"
                    placeholder="admin"
                    class="{{ $errors->has('username') ? 'is-invalid' : '' }}"
                    autocomplete="username"
                    required>
                @error('username')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                    autocomplete="current-password"
                    required>
                @error('password')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <hr class="divider">

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
    </div>

</div>
@endsection