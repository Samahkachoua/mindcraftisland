@extends('layouts.app')

@section('title', 'Register — MindCraft Island')

@section('nav-links')
    <a href="{{ route('admin.login') }}">Admin</a>
@endsection

@section('content')
<div class="container">

    {{-- Success message --}}
    @if(session('success'))
        <div class="alert alert-success" role="alert">
            <span>&#10003;</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Error message --}}
    @if(session('error'))
        <div class="alert alert-error" role="alert">
            <span>&#9888;</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="card">
        <h1>Join MindCraft Island</h1>
        <p class="page-subtitle">Complete the form below to register for the program.</p>

        <hr class="divider">

        <form method="POST" action="{{ route('register.store') }}" novalidate>
            @csrf

            <div class="form-group">
                <label for="full_name">
                    Full Name
                    <span class="label-tag">Required</span>
                </label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="{{ old('full_name') }}"
                    placeholder="e.g. Amira Hassan"
                    class="{{ $errors->has('full_name') ? 'is-invalid' : '' }}"
                    autocomplete="name"
                >
                @error('full_name')
                    <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="phone_number">
                    Phone Number
                    <span class="label-tag">Required</span>
                </label>
                <input
                    type="tel"
                    id="phone_number"
                    name="phone_number"
                    value="{{ old('phone_number') }}"
                    placeholder="e.g. 0661234567"
                    class="{{ $errors->has('phone_number') ? 'is-invalid' : '' }}"
                    autocomplete="tel"
                >
                @error('phone_number')
                    <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="father_name">
                    Father's Name
                    <span class="label-tag">Required</span>
                </label>
                <input
                    type="text"
                    id="father_name"
                    name="father_name"
                    value="{{ old('father_name') }}"
                    placeholder="e.g. Karim Hassan"
                    class="{{ $errors->has('father_name') ? 'is-invalid' : '' }}"
                >
                @error('father_name')
                    <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="mother_name">
                    Mother's Name
                    <span class="label-tag">Required</span>
                </label>
                <input
                    type="text"
                    id="mother_name"
                    name="mother_name"
                    value="{{ old('mother_name') }}"
                    placeholder="e.g. Fatima Benali"
                    class="{{ $errors->has('mother_name') ? 'is-invalid' : '' }}"
                >
                @error('mother_name')
                    <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="date_of_birth">
                    Date of Birth
                    <span class="label-tag">Required</span>
                </label>
                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth') }}"
                    max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                    class="{{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}"
                >
                @error('date_of_birth')
                    <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <hr class="divider">

            <button type="submit" class="btn btn-primary btn-block">
                Submit Registration
            </button>
        </form>
    </div>

</div>
@endsection
