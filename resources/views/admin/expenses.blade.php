@extends('layouts.admin')

@section('title', 'Expenses — Mind Craft Island Admin')

@section('breadcrumb', 'Expenses Overview')

@section('admin-content')

{{-- Header --}}
<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Expenses <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Track spending by category, vendor and payment method.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-expense-btn">+ Add Expense</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

{{-- Filters --}}
<div class="search-bar" style="flex-wrap: wrap;">
    <form method="GET" action="{{ route('admin.expenses') }}" id="expenses-filter-form" style="display:contents;">
        <div class="search-input-wrap">
            <input
                type="text"
                name="search"
                id="expenses-search-input"
                class="search-input"
                placeholder="Search category, vendor or description…"
                value="{{ $search }}"
                autocomplete="off">
            <a href="{{ route('admin.expenses') }}" class="search-clear" id="expenses-search-clear" title="Clear search" style="display: {{ $search !== '' ? 'inline' : 'none' }};">&#215;</a>
        </div>
        <select name="category_id" class="search-input" style="max-width: 170px;">
            <option value="">Categories</option>
            @foreach($categories as $category)
            <option value="{{ $category['id'] }}" {{ (string) $categoryId === (string) $category['id'] ? 'selected' : '' }}>{{ $category['name'] }}</option>
            @endforeach
        </select>
        <select name="vendor_id" class="search-input" style="max-width: 170px;">
            <option value="">Vendors</option>
            @foreach($vendors as $vendor)
            <option value="{{ $vendor['id'] }}" {{ (string) $vendorId === (string) $vendor['id'] ? 'selected' : '' }}>{{ $vendor['name'] }}</option>
            @endforeach
        </select>
        <select name="payment_method" class="search-input" style="max-width: 170px;">
            <option value="">Payment Methods</option>
            @foreach($paymentMethods as $method)
            <option value="{{ $method }}" {{ $paymentMethod === $method ? 'selected' : '' }}>{{ $method }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" class="search-input" style="max-width: 160px;" value="{{ $dateFrom }}" title="From date">
        <input type="date" name="date_to" class="search-input" style="max-width: 160px;" value="{{ $dateTo }}" title="To date">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        <button type="submit" class="btn btn-secondary" style="padding: 0.62rem 1.25rem; font-size: 0.92rem;">Filter</button>
        <button type="button" class="btn btn-secondary" id="expenses-filter-clear-all" style="padding: 0.62rem 1.25rem; font-size: 0.92rem;">Clear Filters</button>
    </form>
</div>

{{-- Table --}}
<div id="results-panel">
    @include('admin.partials.expenses-results')
</div>

{{-- Add / Edit modal --}}
<div class="modal-overlay" id="expense-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 id="expense-modal-title" style="margin-bottom:0;">Add Expense</h2>
            <button type="button" class="modal-close" id="expense-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="expense-form" action="{{ route('admin.expenses.store') }}">
            @csrf
            <div id="expense-method-field"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="expense-category">Category</label>
                    <select id="expense-category" name="category_id" required class="{{ $errors->has('category_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select a category…</option>
                        @foreach($categories as $category)
                        <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="expense-vendor">Vendor</label>
                    <select id="expense-vendor" name="vendor_id" required class="{{ $errors->has('vendor_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select a vendor…</option>
                        @foreach($vendors as $vendor)
                        <option value="{{ $vendor['id'] }}">{{ $vendor['name'] }}</option>
                        @endforeach
                    </select>
                    @error('vendor_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="expense-date">Date</label>
                    <input type="date" id="expense-date" name="expense_date" required class="{{ $errors->has('expense_date') ? 'is-invalid' : '' }}">
                    @error('expense_date')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="expense-amount">Amount</label>
                    <input type="number" id="expense-amount" name="amount" step="0.01" min="0" required class="{{ $errors->has('amount') ? 'is-invalid' : '' }}">
                    @error('amount')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="expense-payment-method">Payment Method</label>
                    <select id="expense-payment-method" name="payment_method" required class="{{ $errors->has('payment_method') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select a payment method…</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="expense-funding-type">Funded By</label>
                    <select id="expense-funding-type" name="funding_type" required class="{{ $errors->has('funding_type') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select who funded this…</option>
                        @foreach($fundingTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    @error('funding_type')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group" id="expense-funding-account-group">
                    <label for="expense-funding-account">Funding Account</label>
                    <select id="expense-funding-account" name="funding_account_id" class="{{ $errors->has('funding_account_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select an account…</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account['id'] }}">{{ $account['label'] }}</option>
                        @endforeach
                    </select>
                    @error('funding_account_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group" id="expense-funding-member-group">
                    <label for="expense-funding-member">Funding Member</label>
                    <select id="expense-funding-member" name="funding_member_id" class="{{ $errors->has('funding_member_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select a member…</option>
                        @foreach($members as $member)
                        <option value="{{ $member['id'] }}">{{ $member['name'] }}</option>
                        @endforeach
                    </select>
                    @error('funding_member_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="expense-description">Description</label>
                    <textarea id="expense-description" name="description" rows="3" maxlength="500" class="{{ $errors->has('description') ? 'is-invalid' : '' }}"></textarea>
                    @error('description')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="expense-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('results-panel');
        var form = document.getElementById('expenses-filter-form');
        var searchInput = document.getElementById('expenses-search-input');
        var categorySelect = form.querySelector('select[name="category_id"]');
        var vendorSelect = form.querySelector('select[name="vendor_id"]');
        var paymentSelect = form.querySelector('select[name="payment_method"]');
        var dateFromInput = form.querySelector('input[name="date_from"]');
        var dateToInput = form.querySelector('input[name="date_to"]');
        var sortField = form.querySelector('input[name="sort"]');
        var directionField = form.querySelector('input[name="direction"]');
        var clearBtn = document.getElementById('expenses-search-clear');
        var expensesUrl = '{{ route("admin.expenses") }}';

        function syncFormFromUrl(url) {
            var params = new URL(url, window.location.origin).searchParams;
            searchInput.value = params.get('search') || '';
            categorySelect.value = params.get('category_id') || '';
            vendorSelect.value = params.get('vendor_id') || '';
            paymentSelect.value = params.get('payment_method') || '';
            dateFromInput.value = params.get('date_from') || '';
            dateToInput.value = params.get('date_to') || '';
            sortField.value = params.get('sort') || sortField.value;
            directionField.value = params.get('direction') || directionField.value;
            clearBtn.style.display = searchInput.value.trim() !== '' ? 'inline' : 'none';
        }

        function loadUrl(url) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    panel.innerHTML = html;
                    syncFormFromUrl(url);
                    // Keep the address bar bare — no filter/sort/page params ever
                    // show up in the URL, by design.
                    window.history.replaceState(null, '', expensesUrl);
                });
        }

        function submitFilters() {
            var params = new URLSearchParams(new FormData(form));
            params.set('page', '1');
            loadUrl(expensesUrl + '?' + params.toString());
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitFilters();
            });
        }

        if (clearBtn) {
            // Only clears the search text — matches its "×" position inside the
            // search box, unlike the "Clear Filters" button which resets everything.
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                searchInput.value = '';
                submitFilters();
            });
        }

        var clearAllBtn = document.getElementById('expenses-filter-clear-all');
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                loadUrl(expensesUrl);
            });
        }

        document.addEventListener('click', function (e) {
            var link = e.target.closest('a.ajax-nav');
            if (!link || !panel.contains(link)) return;
            e.preventDefault();
            loadUrl(link.href);
        });

        // ── Add / Edit modal ────────────────────────────────
        var overlay = document.getElementById('expense-modal-overlay');
        var title = document.getElementById('expense-modal-title');
        var expenseForm = document.getElementById('expense-form');
        var methodField = document.getElementById('expense-method-field');
        var storeUrl = '{{ route("admin.expenses.store") }}';

        var categoryField = document.getElementById('expense-category');
        var vendorField = document.getElementById('expense-vendor');
        var dateField = document.getElementById('expense-date');
        var amountField = document.getElementById('expense-amount');
        var paymentField = document.getElementById('expense-payment-method');
        var descriptionField = document.getElementById('expense-description');
        var fundingTypeField = document.getElementById('expense-funding-type');
        var fundingAccountField = document.getElementById('expense-funding-account');
        var fundingAccountGroup = document.getElementById('expense-funding-account-group');
        var fundingMemberField = document.getElementById('expense-funding-member');
        var fundingMemberGroup = document.getElementById('expense-funding-member-group');

        function syncFundingAccountVisibility() {
            var isAccount = fundingTypeField.value === 'account';
            var isMember = fundingTypeField.value === 'member';

            fundingAccountGroup.style.display = isAccount ? '' : 'none';
            fundingAccountField.required = isAccount;
            if (!isAccount) fundingAccountField.value = '';

            fundingMemberGroup.style.display = isMember ? '' : 'none';
            fundingMemberField.required = isMember;
            if (!isMember) fundingMemberField.value = '';
        }

        fundingTypeField.addEventListener('change', syncFundingAccountVisibility);

        function openModal() {
            overlay.classList.add('open');
        }

        function closeModal() {
            overlay.classList.remove('open');
            clearErrors();
        }

        function clearErrors() {
            expenseForm.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            expenseForm.querySelectorAll('.error-msg').forEach(function (el) { el.remove(); });
        }

        function resetForm() {
            categoryField.value = '';
            vendorField.value = '';
            dateField.value = '';
            amountField.value = '';
            paymentField.value = '';
            descriptionField.value = '';
            fundingTypeField.value = '';
            fundingAccountField.value = '';
            fundingMemberField.value = '';
            syncFundingAccountVisibility();
        }

        function openForAdd() {
            title.textContent = 'Add Expense';
            expenseForm.action = storeUrl;
            methodField.innerHTML = '';
            resetForm();
            openModal();
        }

        function openForEdit(btn) {
            title.textContent = 'Edit Expense';
            expenseForm.action = storeUrl + '/' + btn.dataset.id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            categoryField.value = btn.dataset.categoryId;
            vendorField.value = btn.dataset.vendorId;
            dateField.value = btn.dataset.date;
            amountField.value = btn.dataset.amount;
            paymentField.value = btn.dataset.paymentMethod;
            descriptionField.value = btn.dataset.description;
            fundingTypeField.value = btn.dataset.fundingType;
            syncFundingAccountVisibility();
            if (btn.dataset.fundingType === 'account') fundingAccountField.value = btn.dataset.fundingAccountId;
            if (btn.dataset.fundingType === 'member') fundingMemberField.value = btn.dataset.fundingMemberId;
            openModal();
        }

        document.getElementById('add-expense-btn').addEventListener('click', openForAdd);
        document.getElementById('expense-modal-close').addEventListener('click', closeModal);
        document.getElementById('expense-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.edit-expense-btn');
            if (!btn || !panel.contains(btn)) return;
            openForEdit(btn);
        });

        if (overlay.dataset.reopen === '1') {
            var old = JSON.parse(overlay.dataset.old || '{}');
            openForAdd();
            if (old.category_id) categoryField.value = old.category_id;
            if (old.vendor_id) vendorField.value = old.vendor_id;
            if (old.expense_date) dateField.value = old.expense_date;
            if (old.amount) amountField.value = old.amount;
            if (old.payment_method) paymentField.value = old.payment_method;
            if (old.description) descriptionField.value = old.description;
            if (old.funding_type) fundingTypeField.value = old.funding_type;
            syncFundingAccountVisibility();
            if (old.funding_account_id) fundingAccountField.value = old.funding_account_id;
            if (old.funding_member_id) fundingMemberField.value = old.funding_member_id;
        }

    });
</script>
@endpush
