@extends('layouts.admin')

@section('title', 'Categories — Mind Craft Island Admin')

@section('breadcrumb', 'Categories')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Categories <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Manage the expense categories used across the Expenses list.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-category-btn">+ Add Category</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($categories) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#127991;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No categories yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Add your first category to start tagging expenses.</p>
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
            @foreach($categories as $category)
            <tr>
                <td data-label="Name" style="font-weight: 700;">{{ $category['name'] ?? '—' }}</td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon edit-category-btn"
                            data-id="{{ $category['id'] }}"
                            data-name="{{ $category['name'] }}"
                            title="Edit">&#9998;</button>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category['id']) }}"
                            onsubmit="return confirm('Delete category &quot;{{ $category['name'] }}&quot;? This cannot be undone.');" style="display:inline;">
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
<div class="modal-overlay" id="category-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old-name="{{ old('name') }}">
    <div class="modal">
        <div class="modal-header">
            <h2 id="category-modal-title" style="margin-bottom:0;">Add Category</h2>
            <button type="button" class="modal-close" id="category-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="category-form" action="{{ route('admin.categories.store') }}">
            @csrf
            <div id="category-method-field"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="category-name">Category Name</label>
                    <input type="text" id="category-name" name="name" required maxlength="100" class="{{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="category-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('category-modal-overlay');
        var title = document.getElementById('category-modal-title');
        var form = document.getElementById('category-form');
        var methodField = document.getElementById('category-method-field');
        var nameInput = document.getElementById('category-name');
        var storeUrl = '{{ route("admin.categories.store") }}';

        function openModal() {
            overlay.classList.add('open');
            nameInput.focus();
        }

        function closeModal() {
            overlay.classList.remove('open');
        }

        function openForAdd() {
            title.textContent = 'Add Category';
            form.action = storeUrl;
            methodField.innerHTML = '';
            nameInput.value = '';
            openModal();
        }

        function openForEdit(id, name) {
            title.textContent = 'Edit Category';
            form.action = storeUrl + '/' + id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            nameInput.value = name;
            openModal();
        }

        document.getElementById('add-category-btn').addEventListener('click', openForAdd);
        document.getElementById('category-modal-close').addEventListener('click', closeModal);
        document.getElementById('category-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.querySelectorAll('.edit-category-btn').forEach(function (btn) {
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
