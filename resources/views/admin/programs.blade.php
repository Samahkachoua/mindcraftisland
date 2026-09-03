@extends('layouts.admin')

@section('title', 'Programs — Mind Craft Island Admin')

@section('breadcrumb', 'Programs')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Programs <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Reusable recurring schedule templates. Each participant gets their own start date and expiry when they enroll.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-program-btn">+ Add Program</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($programs) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#128218;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No programs yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Click "Add Program" above to create your first one.</p>
</div>
@else
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Weekdays</th>
                <th># Sessions</th>
                <th>Price</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($programs as $program)
            <tr>
                <td data-label="Name" style="font-weight: 700;">{{ $program['name'] ?? '—' }}</td>
                <td data-label="Weekdays">{{ implode(', ', $program['weekdays'] ?? []) }}</td>
                <td data-label="# Sessions">{{ $program['num_sessions'] ?? '—' }}</td>
                <td data-label="Price" style="font-weight: 700;">{{ number_format((float) ($program['program_price'] ?? 0), 2) }}</td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon edit-program-btn"
                            data-id="{{ $program['id'] }}"
                            data-name="{{ $program['name'] }}"
                            data-weekdays="{{ implode(',', $program['weekdays'] ?? []) }}"
                            data-num-sessions="{{ $program['num_sessions'] }}"
                            data-program-price="{{ $program['program_price'] }}"
                            data-description="{{ $program['description'] }}"
                            title="Edit">&#9998;</button>
                        @if($program['in_use'])
                        <button type="button" class="btn-icon btn-icon-danger" disabled title="This program has existing enrollments and cannot be deleted">&#128465;</button>
                        @else
                        <form method="POST" action="{{ route('admin.programs.destroy', $program['id']) }}"
                            onsubmit="return confirm('Delete program &quot;{{ $program['name'] }}&quot;? This cannot be undone.');" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete">&#128465;</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Add / Edit modal --}}
<div class="modal-overlay" id="program-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 id="program-modal-title" style="margin-bottom:0;">Add Program</h2>
            <button type="button" class="modal-close" id="program-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="program-form" action="{{ route('admin.programs.store') }}">
            @csrf
            <div id="program-method-field"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="program-name">Name</label>
                    <input type="text" id="program-name" name="name" required maxlength="150" class="{{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Weekdays</label>
                    <div class="weekday-checkboxes" style="display:flex; flex-wrap:wrap; gap: 0.75rem;">
                        @foreach($weekdays as $day)
                        <label style="display:flex; align-items:center; gap:0.35rem; font-weight:400;">
                            <input type="checkbox" name="weekdays[]" value="{{ $day }}" class="program-weekday-checkbox">
                            {{ $day }}
                        </label>
                        @endforeach
                    </div>
                    @error('weekdays')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="program-num-sessions"># Sessions</label>
                    <input type="number" id="program-num-sessions" name="num_sessions" min="1" step="1" required class="{{ $errors->has('num_sessions') ? 'is-invalid' : '' }}">
                    @error('num_sessions')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="program-price">Price</label>
                    <input type="number" id="program-price" name="program_price" step="0.01" min="0" required class="{{ $errors->has('program_price') ? 'is-invalid' : '' }}">
                    @error('program_price')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="program-description">Description</label>
                    <textarea id="program-description" name="description" rows="3" maxlength="1000" class="{{ $errors->has('description') ? 'is-invalid' : '' }}"></textarea>
                    @error('description')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="program-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('program-modal-overlay');
        var title = document.getElementById('program-modal-title');
        var form = document.getElementById('program-form');
        var methodField = document.getElementById('program-method-field');
        var storeUrl = '{{ route("admin.programs.store") }}';

        var nameField = document.getElementById('program-name');
        var numSessionsField = document.getElementById('program-num-sessions');
        var priceField = document.getElementById('program-price');
        var descriptionField = document.getElementById('program-description');
        var weekdayCheckboxes = document.querySelectorAll('.program-weekday-checkbox');

        function setWeekdays(days) {
            weekdayCheckboxes.forEach(function (cb) {
                cb.checked = days.indexOf(cb.value) !== -1;
            });
        }

        function openModal() {
            overlay.classList.add('open');
        }

        function closeModal() {
            overlay.classList.remove('open');
            clearErrors();
        }

        function clearErrors() {
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            form.querySelectorAll('.error-msg').forEach(function (el) { el.remove(); });
        }

        function resetForm() {
            nameField.value = '';
            numSessionsField.value = '';
            priceField.value = '';
            descriptionField.value = '';
            setWeekdays([]);
        }

        function openForAdd() {
            title.textContent = 'Add Program';
            form.action = storeUrl;
            methodField.innerHTML = '';
            resetForm();
            openModal();
        }

        function openForEdit(btn) {
            title.textContent = 'Edit Program';
            form.action = storeUrl + '/' + btn.dataset.id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            nameField.value = btn.dataset.name;
            setWeekdays(btn.dataset.weekdays ? btn.dataset.weekdays.split(',') : []);
            numSessionsField.value = btn.dataset.numSessions;
            priceField.value = btn.dataset.programPrice;
            descriptionField.value = btn.dataset.description || '';
            openModal();
        }

        document.getElementById('add-program-btn').addEventListener('click', openForAdd);
        document.getElementById('program-modal-close').addEventListener('click', closeModal);
        document.getElementById('program-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.querySelectorAll('.edit-program-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openForEdit(btn);
            });
        });

        if (overlay.dataset.reopen === '1') {
            var old = JSON.parse(overlay.dataset.old || '{}');
            openForAdd();
            if (old.name) nameField.value = old.name;
            if (old.weekdays) setWeekdays(old.weekdays);
            if (old.num_sessions) numSessionsField.value = old.num_sessions;
            if (old.program_price) priceField.value = old.program_price;
            if (old.description) descriptionField.value = old.description;
        }
    });
</script>
@endpush
