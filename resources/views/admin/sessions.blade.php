@extends('layouts.admin')

@section('title', 'Sessions — Mind Craft Island Admin')

@section('breadcrumb', 'Sessions')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Sessions <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Manage standalone, one-off enrollable sessions.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-session-btn">+ Add Session</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($sessions) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#127891;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No sessions yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Click "Add Session" above to create your first one.</p>
</div>
@else
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sessions as $session)
            <tr>
                <td data-label="Name" style="font-weight: 700;">{{ $session['name'] ?? '—' }}</td>
                <td data-label="Price" style="font-weight: 700;">{{ number_format((float) ($session['price'] ?? 0), 2) }}</td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon edit-session-btn"
                            data-id="{{ $session['id'] }}"
                            data-name="{{ $session['name'] }}"
                            data-price="{{ $session['price'] }}"
                            data-description="{{ $session['description'] }}"
                            title="Edit">&#9998;</button>
                        @if($session['in_use'])
                        <button type="button" class="btn-icon btn-icon-danger" disabled title="This session has existing enrollments and cannot be deleted">&#128465;</button>
                        @else
                        <form method="POST" action="{{ route('admin.sessions.destroy', $session['id']) }}"
                            onsubmit="return confirm('Delete session &quot;{{ $session['name'] }}&quot;? This cannot be undone.');" style="display:inline;">
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
<div class="modal-overlay" id="session-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 id="session-modal-title" style="margin-bottom:0;">Add Session</h2>
            <button type="button" class="modal-close" id="session-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="session-form" action="{{ route('admin.sessions.store') }}">
            @csrf
            <div id="session-method-field"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="session-name">Name</label>
                    <input type="text" id="session-name" name="name" required maxlength="150" class="{{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="session-price">Price</label>
                    <input type="number" id="session-price" name="price" step="0.01" min="0" required class="{{ $errors->has('price') ? 'is-invalid' : '' }}">
                    @error('price')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="session-description">Description</label>
                    <textarea id="session-description" name="description" rows="3" maxlength="1000" class="{{ $errors->has('description') ? 'is-invalid' : '' }}"></textarea>
                    @error('description')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="session-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('session-modal-overlay');
        var title = document.getElementById('session-modal-title');
        var form = document.getElementById('session-form');
        var methodField = document.getElementById('session-method-field');
        var storeUrl = '{{ route("admin.sessions.store") }}';

        var nameField = document.getElementById('session-name');
        var priceField = document.getElementById('session-price');
        var descriptionField = document.getElementById('session-description');

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
            priceField.value = '';
            descriptionField.value = '';
        }

        function openForAdd() {
            title.textContent = 'Add Session';
            form.action = storeUrl;
            methodField.innerHTML = '';
            resetForm();
            openModal();
        }

        function openForEdit(btn) {
            title.textContent = 'Edit Session';
            form.action = storeUrl + '/' + btn.dataset.id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            nameField.value = btn.dataset.name;
            priceField.value = btn.dataset.price;
            descriptionField.value = btn.dataset.description || '';
            openModal();
        }

        document.getElementById('add-session-btn').addEventListener('click', openForAdd);
        document.getElementById('session-modal-close').addEventListener('click', closeModal);
        document.getElementById('session-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.querySelectorAll('.edit-session-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openForEdit(btn);
            });
        });

        if (overlay.dataset.reopen === '1') {
            var old = JSON.parse(overlay.dataset.old || '{}');
            openForAdd();
            if (old.name) nameField.value = old.name;
            if (old.price) priceField.value = old.price;
            if (old.description) descriptionField.value = old.description;
        }
    });
</script>
@endpush
