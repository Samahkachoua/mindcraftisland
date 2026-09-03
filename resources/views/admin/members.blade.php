@extends('layouts.admin')

@section('title', 'Members — Mind Craft Island Admin')

@section('breadcrumb', 'Members')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Members <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">People who can fund an expense directly instead of an Account.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-member-btn">+ Add Member</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($members) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#129309;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No members yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Add a member before recording a member-funded expense against them.</p>
</div>
@else
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Notes</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $member)
            <tr>
                <td data-label="Name" style="font-weight: 700;">{{ $member['name'] ?? '—' }}</td>
                <td data-label="Notes">{{ $member['notes'] ?? '—' }}</td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon edit-member-btn"
                            data-id="{{ $member['id'] }}"
                            data-name="{{ $member['name'] }}"
                            data-notes="{{ $member['notes'] }}"
                            title="Edit">&#9998;</button>
                        @if($member['in_use'])
                        <button type="button" class="btn-icon btn-icon-danger" disabled title="This member is used by existing expenses and cannot be deleted">&#128465;</button>
                        @else
                        <form method="POST" action="{{ route('admin.members.destroy', $member['id']) }}"
                            onsubmit="return confirm('Delete member &quot;{{ $member['name'] }}&quot;? This cannot be undone.');" style="display:inline;">
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
<div class="modal-overlay" id="member-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 id="member-modal-title" style="margin-bottom:0;">Add Member</h2>
            <button type="button" class="modal-close" id="member-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="member-form" action="{{ route('admin.members.store') }}">
            @csrf
            <div id="member-method-field"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="member-name">Name</label>
                    <input type="text" id="member-name" name="name" required maxlength="150" class="{{ $errors->has('name') ? 'is-invalid' : '' }}">
                    @error('name')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="member-notes">Notes</label>
                    <textarea id="member-notes" name="notes" rows="3" maxlength="500" class="{{ $errors->has('notes') ? 'is-invalid' : '' }}"></textarea>
                    @error('notes')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="member-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('member-modal-overlay');
        var title = document.getElementById('member-modal-title');
        var form = document.getElementById('member-form');
        var methodField = document.getElementById('member-method-field');
        var nameInput = document.getElementById('member-name');
        var notesInput = document.getElementById('member-notes');
        var storeUrl = '{{ route("admin.members.store") }}';

        function openModal() {
            overlay.classList.add('open');
            nameInput.focus();
        }

        function closeModal() {
            overlay.classList.remove('open');
            clearErrors();
        }

        function clearErrors() {
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            form.querySelectorAll('.error-msg').forEach(function (el) { el.remove(); });
        }

        function openForAdd() {
            title.textContent = 'Add Member';
            form.action = storeUrl;
            methodField.innerHTML = '';
            nameInput.value = '';
            notesInput.value = '';
            openModal();
        }

        function openForEdit(btn) {
            title.textContent = 'Edit Member';
            form.action = storeUrl + '/' + btn.dataset.id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            nameInput.value = btn.dataset.name;
            notesInput.value = btn.dataset.notes;
            openModal();
        }

        document.getElementById('add-member-btn').addEventListener('click', openForAdd);
        document.getElementById('member-modal-close').addEventListener('click', closeModal);
        document.getElementById('member-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.querySelectorAll('.edit-member-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openForEdit(btn);
            });
        });

        if (overlay.dataset.reopen === '1') {
            var old = JSON.parse(overlay.dataset.old || '{}');
            openForAdd();
            if (old.name) nameInput.value = old.name;
            if (old.notes) notesInput.value = old.notes;
        }
    });
</script>
@endpush
