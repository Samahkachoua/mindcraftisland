@extends('layouts.app')

@section('title', __('register.page_title'))

@section('nav-links')
<a href="{{ route('register.lang', __('register.switch_lang_locale')) }}" class="lang-switch-btn">
    {{ __('register.switch_lang_label') }}
</a>
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
        <h1>{{ __('register.heading_prefix') }} <span class="text-secondary">Mind Craft</span> <span class="text-primary">Island</span></h1>
        <p class="page-subtitle">{{ __('register.subtitle') }}</p>

        <hr class="divider">

        <form method="POST" action="{{ route('register.store') }}" novalidate>
            @csrf

            <div class="form-group">
                <label>
                    {{ __('register.registration_type_heading') }}
                    <span class="label-tag">{{ __('register.required') }}</span>
                </label>
                <div class="radio-card-group">
                    <label class="radio-card">
                        <input
                            type="radio"
                            name="registration_type"
                            value="child"
                            {{ old('registration_type', 'child') === 'child' ? 'checked' : '' }}>
                        <span class="radio-card-text">{{ __('register.registration_type_child') }}</span>
                    </label>
                    <label class="radio-card">
                        <input
                            type="radio"
                            name="registration_type"
                            value="lady"
                            {{ old('registration_type') === 'lady' ? 'checked' : '' }}>
                        <span class="radio-card-text">{{ __('register.registration_type_lady') }}</span>
                    </label>
                </div>
                @error('registration_type')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="full_name">
                    <span id="full_name-label-text" data-child-text="{{ __('register.full_name') }}" data-lady-text="{{ __('register.full_name_lady') }}">{{ __('register.full_name') }}</span>
                    <span class="label-tag">{{ __('register.required') }}</span>
                </label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="{{ old('full_name') }}"
                    placeholder="{{ __('register.full_name_ph') }}"
                    class="{{ $errors->has('full_name') ? 'is-invalid' : '' }}"
                    autocomplete="name">
                @error('full_name')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="phone_number">
                    <span id="phone_number-label-text" data-child-text="{{ __('register.phone_number') }}" data-lady-text="{{ __('register.phone_number_lady') }}">{{ __('register.phone_number') }}</span>
                    <span class="label-tag">{{ __('register.required') }}</span>
                </label>
                <input
                    type="tel"
                    id="phone_number"
                    name="phone_number"
                    value="{{ old('phone_number') }}"
                    placeholder="{{ __('register.phone_number_ph') }}"
                    class="{{ $errors->has('phone_number') ? 'is-invalid' : '' }}"
                    autocomplete="tel">
                @error('phone_number')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group" data-flow="child">
                <label for="emergency_contact_number">
                    {{ __('register.emergency_contact_number') }}
                    <span class="label-tag">{{ __('register.required') }}</span>
                </label>
                <input
                    type="tel"
                    id="emergency_contact_number"
                    name="emergency_contact_number"
                    value="{{ old('emergency_contact_number') }}"
                    placeholder="{{ __('register.emergency_contact_number_ph') }}"
                    class="{{ $errors->has('emergency_contact_number') ? 'is-invalid' : '' }}"
                    autocomplete="tel">
                @error('emergency_contact_number')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group" data-flow="child">
                <label for="mother_name">
                    {{ __('register.mother_name') }}
                    <span class="label-tag">{{ __('register.required') }}</span>
                </label>
                <input
                    type="text"
                    id="mother_name"
                    name="mother_name"
                    value="{{ old('mother_name') }}"
                    placeholder="{{ __('register.mother_name_ph') }}"
                    class="{{ $errors->has('mother_name') ? 'is-invalid' : '' }}">
                @error('mother_name')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="date_of_birth">
                    {{ __('register.date_of_birth') }}
                    <span class="label-tag">{{ __('register.required') }}</span>
                </label>
                <div class="date-input-wrapper">
                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        data-child-max="{{ now()->subYears(8)->format('Y-m-d') }}"
                        data-child-min="{{ now()->subYears(18)->format('Y-m-d') }}"
                        data-lady-max="{{ now()->subYears(18)->format('Y-m-d') }}"
                        max="{{ now()->subYears(8)->format('Y-m-d') }}"
                        min="{{ now()->subYears(18)->format('Y-m-d') }}"
                        class="{{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}">
                </div>
                @error('date_of_birth')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="medical_conditions">
                    {{ __('register.medical_conditions') }}
                </label>
                <input
                    type="text"
                    id="medical_conditions"
                    name="medical_conditions"
                    value="{{ old('medical_conditions') }}"
                    placeholder="{{ __('register.medical_conditions_ph') }}"
                    class="{{ $errors->has('medical_conditions') ? 'is-invalid' : '' }}">
                @error('medical_conditions')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label for="field_of_interests">
                    {{ __('register.field_of_interests') }}
                </label>
                <input
                    type="text"
                    id="field_of_interests"
                    name="field_of_interests"
                    value="{{ old('field_of_interests') }}"
                    placeholder="{{ __('register.field_of_interests_ph') }}"
                    class="{{ $errors->has('field_of_interests') ? 'is-invalid' : '' }}">
                @error('field_of_interests')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <hr class="divider">

            <div class="form-group form-group-checkbox">
                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="photo_video_consent"
                        value="1"
                        {{ old('photo_video_consent') ? 'checked' : '' }}>
                    <span id="photo_video_consent-label-text" data-child-text="{{ __('register.photo_video_consent') }}" data-lady-text="{{ __('register.photo_video_consent_lady') }}">{{ __('register.photo_video_consent') }}</span>
                </label>
                @error('photo_video_consent')
                <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                {{ __('register.submit') }}
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var dateInput = document.getElementById('date_of_birth');
        if (!dateInput) return;

        var wrapper = dateInput.parentElement;
        var refInput = document.getElementById('full_name');

        function fixDateInput() {
            var w = wrapper.clientWidth;
            dateInput.style.width = w + 'px';
            dateInput.style.maxWidth = w + 'px';

            if (refInput) {
                var h = refInput.getBoundingClientRect().height;
                dateInput.style.height = h + 'px';
                dateInput.style.minHeight = h + 'px';
            }
        }

        fixDateInput();
        window.addEventListener('resize', fixDateInput);
        window.addEventListener('orientationchange', fixDateInput);
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var typeRadios = document.querySelectorAll('input[name="registration_type"]');
        if (!typeRadios.length) return;

        var childOnlyFields = document.querySelectorAll('[data-flow="child"]');
        var swappableLabels = document.querySelectorAll('[data-child-text][data-lady-text]');
        var dob = document.getElementById('date_of_birth');

        function applyFlow(type) {
            childOnlyFields.forEach(function(el) {
                el.style.display = (type === 'lady') ? 'none' : '';
            });

            swappableLabels.forEach(function(el) {
                el.textContent = (type === 'lady') ? el.dataset.ladyText : el.dataset.childText;
            });

            if (dob) {
                if (type === 'lady') {
                    dob.max = dob.dataset.ladyMax;
                    dob.removeAttribute('min');
                } else {
                    dob.max = dob.dataset.childMax;
                    dob.min = dob.dataset.childMin;
                }
            }
        }

        typeRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                applyFlow(this.value);
            });
        });

        var checked = document.querySelector('input[name="registration_type"]:checked');
        applyFlow(checked ? checked.value : 'child');
    });
</script>
@endpush