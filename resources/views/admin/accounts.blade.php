@extends('layouts.admin')

@section('title', 'Accounts — Mind Craft Island Admin')

@section('breadcrumb', 'Accounts')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Accounts <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Cash, bank and mobile wallet accounts the ledger tracks money against.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-account-btn">+ Add Account</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($accounts) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#127974;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No accounts yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Add your first account before recording Payments, Expenses or Rentals.</p>
</div>
@else
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Name (EN)</th>
                <th>Name (AR)</th>
                <th>Type</th>
                <th>Opening Balance</th>
                <th>Active</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $account)
            <tr>
                <td data-label="Name (EN)" style="font-weight: 700;">{{ $account['name_en'] ?? '—' }}</td>
                <td data-label="Name (AR)">{{ $account['name_ar'] ?? '—' }}</td>
                <td data-label="Type">{{ $account['type_label'] ?? '—' }}</td>
                <td data-label="Opening Balance">{{ number_format((float) ($account['opening_balance'] ?? 0), 2) }}</td>
                <td data-label="Active">
                    @if($account['is_active'])
                    <span class="badge-filtered" style="color: #1a8a4a;">Active</span>
                    @else
                    <span class="badge-filtered" style="color: #8a9ab0;">Inactive</span>
                    @endif
                </td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <a href="{{ route('admin.accounts.show', $account['id']) }}" class="btn-icon" title="View Ledger">&#128220;</a>
                        <button type="button" class="btn-icon edit-account-btn"
                            data-id="{{ $account['id'] }}"
                            data-name-en="{{ $account['name_en'] }}"
                            data-name-ar="{{ $account['name_ar'] }}"
                            data-type="{{ $account['type'] }}"
                            data-opening-balance="{{ $account['opening_balance'] }}"
                            data-is-active="{{ $account['is_active'] ? '1' : '0' }}"
                            title="Edit">&#9998;</button>
                        <form method="POST" action="{{ route('admin.accounts.destroy', $account['id']) }}"
                            onsubmit="return confirm('Delete account &quot;{{ $account['name_en'] }}&quot;? This cannot be undone.');" style="display:inline;">
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
<div class="modal-overlay" id="account-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 id="account-modal-title" style="margin-bottom:0;">Add Account</h2>
            <button type="button" class="modal-close" id="account-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="account-form" action="{{ route('admin.accounts.store') }}">
            @csrf
            <div id="account-method-field"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="account-name-en">Name (English)</label>
                    <input type="text" id="account-name-en" name="name_en" required maxlength="150" class="{{ $errors->has('name_en') ? 'is-invalid' : '' }}">
                    @error('name_en')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="account-name-ar">Name (Arabic)</label>
                    <input type="text" id="account-name-ar" name="name_ar" required maxlength="150" dir="rtl" class="{{ $errors->has('name_ar') ? 'is-invalid' : '' }}">
                    @error('name_ar')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="account-type">Type</label>
                    <select id="account-type" name="type" required class="{{ $errors->has('type') ? 'is-invalid' : '' }}">
                        @foreach($types as $type)
                        <option value="{{ $type }}">{{ \App\Http\Controllers\AccountController::TYPE_LABELS[$type] }}</option>
                        @endforeach
                    </select>
                    @error('type')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="account-opening-balance">Opening Balance</label>
                    <input type="number" id="account-opening-balance" name="opening_balance" step="0.01" required class="{{ $errors->has('opening_balance') ? 'is-invalid' : '' }}">
                    @error('opening_balance')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="account-is-active" name="is_active" value="1" style="width:auto; margin-right:0.4rem;">
                        Active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="account-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('account-modal-overlay');
        var title = document.getElementById('account-modal-title');
        var form = document.getElementById('account-form');
        var methodField = document.getElementById('account-method-field');
        var storeUrl = '{{ route("admin.accounts.store") }}';

        var nameEnField = document.getElementById('account-name-en');
        var nameArField = document.getElementById('account-name-ar');
        var typeField = document.getElementById('account-type');
        var openingBalanceField = document.getElementById('account-opening-balance');
        var isActiveField = document.getElementById('account-is-active');

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
            nameEnField.value = '';
            nameArField.value = '';
            typeField.value = 'cash';
            openingBalanceField.value = '0';
            isActiveField.checked = true;
        }

        function openForAdd() {
            title.textContent = 'Add Account';
            form.action = storeUrl;
            methodField.innerHTML = '';
            resetForm();
            openModal();
        }

        function openForEdit(btn) {
            title.textContent = 'Edit Account';
            form.action = storeUrl + '/' + btn.dataset.id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            nameEnField.value = btn.dataset.nameEn;
            nameArField.value = btn.dataset.nameAr;
            typeField.value = btn.dataset.type;
            openingBalanceField.value = btn.dataset.openingBalance;
            isActiveField.checked = btn.dataset.isActive === '1';
            openModal();
        }

        document.getElementById('add-account-btn').addEventListener('click', openForAdd);
        document.getElementById('account-modal-close').addEventListener('click', closeModal);
        document.getElementById('account-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.querySelectorAll('.edit-account-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openForEdit(btn);
            });
        });

        if (overlay.dataset.reopen === '1') {
            var old = JSON.parse(overlay.dataset.old || '{}');
            openForAdd();
            if (old.name_en) nameEnField.value = old.name_en;
            if (old.name_ar) nameArField.value = old.name_ar;
            if (old.type) typeField.value = old.type;
            if (old.opening_balance) openingBalanceField.value = old.opening_balance;
            isActiveField.checked = !!old.is_active;
        }
    });
</script>
@endpush
