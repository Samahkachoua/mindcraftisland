@extends('layouts.admin')

@section('title', 'Rentals — Mind Craft Island Admin')

@section('breadcrumb', 'Rentals')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Rentals <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Check out rental items and track their return.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-rental-btn">+ Check Out</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($rentals) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#128230;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No rentals yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Click "Check Out" above to record your first rental.</p>
</div>
@else
<div class="search-bar">
    <select id="rentals-filter-status" class="search-input" style="max-width: 170px;">
        <option value="">Status</option>
        <option value="booked">Booked</option>
        <option value="out">Out</option>
        <option value="overdue">Overdue</option>
        <option value="returned">Returned</option>
    </select>
    <input type="text" id="rentals-filter-search" class="search-input" placeholder="Search renter name or phone…" autocomplete="off">
    <button type="button" class="btn btn-secondary" id="rentals-filter-clear" style="padding: 0.62rem 1.25rem; font-size: 0.92rem;">Clear Filters</button>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Renter</th>
                <th>Phone</th>
                <th>Out</th>
                <th>Due Back</th>
                <th>Returned</th>
                <th>Rate</th>
                <th>Deposit</th>
                <th>Status</th>
                <th style="width: 90px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rentals as $rental)
            <tr data-rental-row data-status="{{ $rental['display_status'] }}" data-renter="{{ mb_strtolower($rental['renter_name'] . ' ' . $rental['renter_phone']) }}">
                <td data-label="Item" style="font-weight: 700;">{{ $rental['item_name'] ?? '—' }}</td>
                <td data-label="Renter">{{ $rental['renter_name'] ?? '—' }}</td>
                <td data-label="Phone">{{ $rental['renter_phone'] ?? '—' }}</td>
                <td data-label="Out">{{ \Carbon\Carbon::parse($rental['date_out'])->format('d M Y') }}</td>
                <td data-label="Due Back">{{ \Carbon\Carbon::parse($rental['date_due_back'])->format('d M Y') }}</td>
                <td data-label="Returned">
                    @if(!empty($rental['date_returned']))
                    {{ \Carbon\Carbon::parse($rental['date_returned'])->format('d M Y') }}
                    @else —
                    @endif
                </td>
                <td data-label="Rate">{{ number_format((float) ($rental['rate_charged'] ?? 0), 2) }}</td>
                <td data-label="Deposit">{{ isset($rental['deposit_collected']) ? number_format((float) $rental['deposit_collected'], 2) : '—' }}</td>
                <td data-label="Status">
                    @php
                    $statusColors = ['booked' => '#2f6fed', 'out' => '#c98a1a', 'overdue' => '#c0392b', 'returned' => '#1a8a4a'];
                    $statusColor = $statusColors[$rental['display_status']] ?? '#8a9ab0';
                    @endphp
                    <span class="badge-filtered" style="color: {{ $statusColor }};">{{ ucfirst($rental['display_status']) }}</span>
                </td>
                <td data-label="Actions">
                    <button type="button" class="btn-icon edit-rental-btn"
                        data-id="{{ $rental['id'] }}"
                        data-renter-name="{{ $rental['renter_name'] }}"
                        data-renter-phone="{{ $rental['renter_phone'] }}"
                        data-date-due-back="{{ $rental['date_due_back'] }}"
                        title="Edit">&#9998;</button>
                    @if($rental['status'] !== 'returned')
                    <button type="button" class="btn-icon return-rental-btn"
                        data-id="{{ $rental['id'] }}"
                        data-item-name="{{ $rental['item_name'] }}"
                        data-renter-name="{{ $rental['renter_name'] }}"
                        title="Mark Returned">&#9989;</button>
                    @endif
                    <form method="POST" action="{{ route('admin.rentals.destroy', $rental['id']) }}"
                        onsubmit="return confirm('Delete this rental? This also permanently deletes its Payment and ledger Transaction — not just the rental record. This cannot be undone.');" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete">&#128465;</button>
                    </form>
                </td>
            </tr>
            @endforeach
            <tr id="rentals-no-match-row" style="display:none;">
                <td colspan="10" style="text-align:center; color:#8a9ab0; padding: 2rem 0;">No rentals match these filters.</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- Check Out modal --}}
<div class="modal-overlay" id="rental-modal-overlay"
    {{-- old('form_marker') disambiguates which of the three forms on this page
         failed validation — checking a real field's own value breaks the
         moment that field is itself submitted blank (ConvertEmptyStringsToNull
         turns it to null, same as any other required field on a failed
         submission), so a fixed, never-blank hidden marker is used instead. --}}
    data-reopen="{{ $errors->any() && old('form_marker') === null ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 style="margin-bottom:0;">Check Out</h2>
            <button type="button" class="modal-close" id="rental-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="rental-form" action="{{ route('admin.rentals.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="rental-item">Item</label>
                    <select id="rental-item" name="rental_item_id" required class="{{ $errors->has('rental_item_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select an item…</option>
                        @foreach($availableItems as $item)
                        <option value="{{ $item['id'] }}" data-rate="{{ $item['rate'] }}" data-deposit="{{ $item['deposit_amount'] }}">
                            {{ $item['name_en'] }} (rate {{ number_format((float) $item['rate'], 2) }})
                        </option>
                        @endforeach
                    </select>
                    @error('rental_item_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                    @if(count($availableItems) === 0)
                    <p style="margin: 0.35rem 0 0; color: #c0392b; font-size: 0.82rem;">No items are currently available.</p>
                    @endif
                </div>
                <div class="form-group">
                    <label for="rental-renter-name">Renter Name</label>
                    <input type="text" id="rental-renter-name" name="renter_name" required maxlength="150" class="{{ $errors->has('renter_name') ? 'is-invalid' : '' }}">
                    @error('renter_name')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-renter-phone">Renter Phone</label>
                    <input type="text" id="rental-renter-phone" name="renter_phone" required maxlength="50" class="{{ $errors->has('renter_phone') ? 'is-invalid' : '' }}">
                    @error('renter_phone')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-date-out">Date Out</label>
                    <input type="date" id="rental-date-out" name="date_out" required class="{{ $errors->has('date_out') ? 'is-invalid' : '' }}">
                    @error('date_out')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-date-due-back">Date Due Back</label>
                    <input type="date" id="rental-date-due-back" name="date_due_back" required class="{{ $errors->has('date_due_back') ? 'is-invalid' : '' }}">
                    @error('date_due_back')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-rate-charged">Rate Charged</label>
                    <input type="number" id="rental-rate-charged" name="rate_charged" step="0.01" min="0.01" required class="{{ $errors->has('rate_charged') ? 'is-invalid' : '' }}">
                    @error('rate_charged')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-deposit-collected">Deposit Collected</label>
                    <input type="number" id="rental-deposit-collected" name="deposit_collected" step="0.01" min="0" class="{{ $errors->has('deposit_collected') ? 'is-invalid' : '' }}">
                    @error('deposit_collected')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-account">Payment Account</label>
                    <select id="rental-account" name="account_id" required class="{{ $errors->has('account_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select an account…</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account['id'] }}">{{ $account['label'] }}</option>
                        @endforeach
                    </select>
                    @error('account_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-payment-method">Payment Method</label>
                    <select id="rental-payment-method" name="payment_method" required class="{{ $errors->has('payment_method') ? 'is-invalid' : '' }}">
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
                    <label for="rental-notes">Notes</label>
                    <textarea id="rental-notes" name="notes" rows="3" maxlength="500" class="{{ $errors->has('notes') ? 'is-invalid' : '' }}"></textarea>
                    @error('notes')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="rental-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Check Out</button>
            </div>
        </form>
    </div>
</div>

{{-- Return modal --}}
<div class="modal-overlay" id="return-modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 style="margin-bottom:0;">Mark Returned</h2>
            <button type="button" class="modal-close" id="return-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="return-form">
            @csrf
            <input type="hidden" name="form_marker" value="return_rental">
            <div class="modal-body">
                <p id="return-summary" style="margin: 0 0 1rem; color: #8a9ab0;"></p>
                <div class="form-group">
                    <label for="return-date">Date Returned</label>
                    <input type="date" id="return-date" name="date_returned" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="return-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm Return</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Rental modal — renter contact info and due-back date only. The
     financial fields (item, account, rate, deposit) already posted a
     Payment/Transaction at checkout and can't be edited here. --}}
<div class="modal-overlay" id="edit-rental-modal-overlay"
    data-reopen="{{ $errors->any() && old('form_marker') === 'edit_rental' ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 style="margin-bottom:0;">Edit Rental</h2>
            <button type="button" class="modal-close" id="edit-rental-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="edit-rental-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="form_marker" value="edit_rental">
            <input type="hidden" id="edit-rental-id" name="edit_id" value="{{ old('edit_id') }}">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit-rental-renter-name">Renter Name</label>
                    <input type="text" id="edit-rental-renter-name" name="edit_renter_name" required maxlength="150" class="{{ $errors->has('edit_renter_name') ? 'is-invalid' : '' }}">
                    @error('edit_renter_name')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="edit-rental-renter-phone">Renter Phone</label>
                    <input type="text" id="edit-rental-renter-phone" name="edit_renter_phone" required maxlength="50" class="{{ $errors->has('edit_renter_phone') ? 'is-invalid' : '' }}">
                    @error('edit_renter_phone')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="edit-rental-date-due-back">Date Due Back</label>
                    <input type="date" id="edit-rental-date-due-back" name="edit_date_due_back" required class="{{ $errors->has('edit_date_due_back') ? 'is-invalid' : '' }}">
                    @error('edit_date_due_back')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="edit-rental-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function today() {
            return new Date().toISOString().slice(0, 10);
        }

        // ── Check Out modal ──────────────────────────────────
        var overlay = document.getElementById('rental-modal-overlay');
        var form = document.getElementById('rental-form');
        var itemField = document.getElementById('rental-item');
        var renterNameField = document.getElementById('rental-renter-name');
        var renterPhoneField = document.getElementById('rental-renter-phone');
        var dateOutField = document.getElementById('rental-date-out');
        var dateDueBackField = document.getElementById('rental-date-due-back');
        var rateField = document.getElementById('rental-rate-charged');
        var depositField = document.getElementById('rental-deposit-collected');
        var accountField = document.getElementById('rental-account');
        var paymentMethodField = document.getElementById('rental-payment-method');
        var notesField = document.getElementById('rental-notes');

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
            form.reset();
            dateOutField.value = today();
        }

        function syncItemDefaults() {
            var selected = itemField.options[itemField.selectedIndex];
            if (!selected || !selected.dataset) return;
            if (selected.dataset.rate) rateField.value = selected.dataset.rate;
            if (selected.dataset.deposit && selected.dataset.deposit !== 'undefined') {
                depositField.value = selected.dataset.deposit;
            }
        }

        itemField.addEventListener('change', syncItemDefaults);

        document.getElementById('add-rental-btn').addEventListener('click', function () {
            resetForm();
            openModal();
        });
        document.getElementById('rental-modal-close').addEventListener('click', closeModal);
        document.getElementById('rental-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        if (overlay.dataset.reopen === '1') {
            var old = JSON.parse(overlay.dataset.old || '{}');
            openModal();
            if (old.rental_item_id) { itemField.value = old.rental_item_id; syncItemDefaults(); }
            if (old.renter_name) renterNameField.value = old.renter_name;
            if (old.renter_phone) renterPhoneField.value = old.renter_phone;
            if (old.date_out) dateOutField.value = old.date_out;
            if (old.date_due_back) dateDueBackField.value = old.date_due_back;
            if (old.rate_charged) rateField.value = old.rate_charged;
            if (old.deposit_collected) depositField.value = old.deposit_collected;
            if (old.account_id) accountField.value = old.account_id;
            if (old.payment_method) paymentMethodField.value = old.payment_method;
            if (old.notes) notesField.value = old.notes;
        }

        // ── Return modal ──────────────────────────────────────
        var returnOverlay = document.getElementById('return-modal-overlay');
        var returnForm = document.getElementById('return-form');
        var returnSummary = document.getElementById('return-summary');
        var returnDateField = document.getElementById('return-date');
        var returnUrlTemplate = '{{ route("admin.rentals.return", ["id" => "__ID__"]) }}';

        document.querySelectorAll('.return-rental-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                returnForm.action = returnUrlTemplate.replace('__ID__', btn.dataset.id);
                returnSummary.textContent = btn.dataset.itemName + ' — ' + btn.dataset.renterName;
                returnDateField.value = today();
                returnOverlay.classList.add('open');
            });
        });

        document.getElementById('return-modal-close').addEventListener('click', function () {
            returnOverlay.classList.remove('open');
        });
        document.getElementById('return-modal-cancel').addEventListener('click', function () {
            returnOverlay.classList.remove('open');
        });
        returnOverlay.addEventListener('click', function (e) {
            if (e.target === returnOverlay) returnOverlay.classList.remove('open');
        });

        // ── Edit Rental modal ────────────────────────────────
        var editOverlay = document.getElementById('edit-rental-modal-overlay');
        var editForm = document.getElementById('edit-rental-form');
        var editIdField = document.getElementById('edit-rental-id');
        var editNameField = document.getElementById('edit-rental-renter-name');
        var editPhoneField = document.getElementById('edit-rental-renter-phone');
        var editDueBackField = document.getElementById('edit-rental-date-due-back');
        var editUrlTemplate = '{{ route("admin.rentals.update", ["id" => "__ID__"]) }}';

        function editCloseModal() {
            editOverlay.classList.remove('open');
            clearEditErrors();
        }

        function clearEditErrors() {
            editForm.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            editForm.querySelectorAll('.error-msg').forEach(function (el) { el.remove(); });
        }

        document.querySelectorAll('.edit-rental-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editForm.action = editUrlTemplate.replace('__ID__', btn.dataset.id);
                editIdField.value = btn.dataset.id;
                editNameField.value = btn.dataset.renterName;
                editPhoneField.value = btn.dataset.renterPhone;
                editDueBackField.value = btn.dataset.dateDueBack;
                editOverlay.classList.add('open');
            });
        });

        document.getElementById('edit-rental-modal-close').addEventListener('click', editCloseModal);
        document.getElementById('edit-rental-modal-cancel').addEventListener('click', editCloseModal);
        editOverlay.addEventListener('click', function (e) {
            if (e.target === editOverlay) editCloseModal();
        });

        // Reopen with previous input after a validation error — the row's id
        // isn't known from the URL, so it's carried through as a hidden field.
        if (editOverlay.dataset.reopen === '1' && editIdField.value) {
            var editOld = JSON.parse(editOverlay.dataset.old || '{}');
            editForm.action = editUrlTemplate.replace('__ID__', editIdField.value);
            if (editOld.edit_renter_name) editNameField.value = editOld.edit_renter_name;
            if (editOld.edit_renter_phone) editPhoneField.value = editOld.edit_renter_phone;
            if (editOld.edit_date_due_back) editDueBackField.value = editOld.edit_date_due_back;
            editOverlay.classList.add('open');
        }

        // ── Filters ───────────────────────────────────────────
        @if(count($rentals) > 0)
        var rentalRows = Array.prototype.slice.call(document.querySelectorAll('tbody tr[data-rental-row]'));
        var noMatchRow = document.getElementById('rentals-no-match-row');
        var filterStatus = document.getElementById('rentals-filter-status');
        var filterSearch = document.getElementById('rentals-filter-search');
        var filterClear = document.getElementById('rentals-filter-clear');

        function renderRentals() {
            var anyVisible = false;
            var statusVal = filterStatus.value;
            var searchVal = filterSearch.value.trim().toLowerCase();
            rentalRows.forEach(function (row) {
                var matches = (!statusVal || row.dataset.status === statusVal)
                    && (!searchVal || row.dataset.renter.indexOf(searchVal) !== -1);
                row.style.display = matches ? '' : 'none';
                if (matches) anyVisible = true;
            });
            noMatchRow.style.display = anyVisible ? 'none' : '';
        }

        filterStatus.addEventListener('change', renderRentals);
        filterSearch.addEventListener('input', renderRentals);
        filterClear.addEventListener('click', function () {
            filterStatus.value = '';
            filterSearch.value = '';
            renderRentals();
        });
        @endif
    });
</script>
@endpush
