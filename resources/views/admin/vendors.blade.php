@extends('layouts.admin')

@section('title', 'Vendors — Mind Craft Island Admin')

@section('breadcrumb', 'Vendors')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Vendors <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Manage the vendors used across the Expenses list.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-vendor-btn">+ Add Vendor</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($vendors) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#127978;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No vendors yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Add your first vendor to start tagging expenses.</p>
</div>
@else
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendors as $vendor)
            <tr>
                <td data-label="Name" style="font-weight: 700;">{{ $vendor['name'] ?? '—' }}</td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon edit-vendor-btn"
                            data-id="{{ $vendor['id'] }}"
                            data-name="{{ $vendor['name'] }}"
                            title="Edit">&#9998;</button>
                        <form method="POST" action="{{ route('admin.vendors.destroy', $vendor['id']) }}"
                            onsubmit="return confirm('Delete vendor &quot;{{ $vendor['name'] }}&quot;? This cannot be undone.');" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete">&#128465;</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Add / Edit modal --}}
<div class="modal-overlay" id="vendor-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old-name="{{ old('name') }}">
    <div class="modal">
        <div class="modal-header">
            <h2 id="vendor-modal-title" style="margin-bottom:0;">Add Vendor</h2>
            <button type="button" class="modal-close" id="vendor-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="vendor-form" action="{{ route('admin.vendors.store') }}">
            @csrf
            <div id="vendor-method-field"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="vendor-name">Vendor Name</label>
                    <input type="text" id="vendor-name" name="name" required maxlength="100" class="{{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="vendor-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('vendor-modal-overlay');
        var title = document.getElementById('vendor-modal-title');
        var form = document.getElementById('vendor-form');
        var methodField = document.getElementById('vendor-method-field');
        var nameInput = document.getElementById('vendor-name');
        var storeUrl = '{{ route("admin.vendors.store") }}';

        function openModal() {
            overlay.classList.add('open');
            nameInput.focus();
        }

        function closeModal() {
            overlay.classList.remove('open');
        }

        function openForAdd() {
            title.textContent = 'Add Vendor';
            form.action = storeUrl;
            methodField.innerHTML = '';
            nameInput.value = '';
            openModal();
        }

        function openForEdit(id, name) {
            title.textContent = 'Edit Vendor';
            form.action = storeUrl + '/' + id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            nameInput.value = name;
            openModal();
        }

        document.getElementById('add-vendor-btn').addEventListener('click', openForAdd);
        document.getElementById('vendor-modal-close').addEventListener('click', closeModal);
        document.getElementById('vendor-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.querySelectorAll('.edit-vendor-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openForEdit(btn.dataset.id, btn.dataset.name);
            });
        });

        if (overlay.dataset.reopen === '1') {
            openForAdd();
            nameInput.value = overlay.dataset.oldName;
        }
    });
</script>
@endpush
