@extends('layouts.admin')

@section('title', 'Payments — Mind Craft Island Admin')

@section('breadcrumb', 'Payments')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Payments <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Every payment recorded against an Enrollment, whether for a Program or a Session.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-payment-btn">+ Record Payment</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($payments) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#128179;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No payments recorded yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Click "Record Payment" above to log a payment against an enrollment.</p>
</div>
@else
{{-- Filters are applied entirely client-side against the already-rendered
     rows below — nothing here is ever sent as a URL query parameter. --}}
<div class="search-bar" style="flex-wrap: wrap;">
    <select id="payments-filter-program" class="search-input" style="max-width: 200px;">
        <option value="">Program</option>
        @foreach($programs as $program)
        <option value="{{ $program['id'] }}">{{ $program['name'] }}</option>
        @endforeach
    </select>
    <select id="payments-filter-session" class="search-input" style="max-width: 200px;">
        <option value="">Session</option>
        @foreach($sessions as $session)
        <option value="{{ $session['id'] }}">{{ $session['name'] }}</option>
        @endforeach
    </select>
    <input type="date" id="payments-filter-date-from" class="search-input" style="max-width: 170px;" title="From payment date">
    <input type="date" id="payments-filter-date-to" class="search-input" style="max-width: 170px;" title="To payment date">
    <select id="payments-filter-participant" class="search-input" style="max-width: 200px;">
        <option value="">Participant</option>
        @foreach($registrations as $registration)
        <option value="{{ $registration['id'] }}">{{ $registration['full_name'] }}</option>
        @endforeach
    </select>
    <select id="payments-filter-method" class="search-input" style="max-width: 160px;">
        <option value="">Method</option>
        @foreach($paymentMethods as $method)
        <option value="{{ $method }}">{{ $method }}</option>
        @endforeach
    </select>
    <select id="payments-filter-account" class="search-input" style="max-width: 200px;">
        <option value="">Account</option>
        @foreach($allAccounts as $account)
        <option value="{{ $account['id'] }}">{{ \App\Http\Controllers\AccountController::optionLabel($account) }}</option>
        @endforeach
    </select>
    <button type="button" class="btn btn-secondary" id="payments-filter-clear" style="padding: 0.62rem 1.25rem; font-size: 0.92rem;">Clear Filters</button>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Participant</th>
                <th>Enrolled In</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Account</th>
                <th>Notes</th>
                <th>Recorded By</th>
                <th style="width: 80px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr data-payment-row
                data-program-id="{{ $payment['program_id'] }}"
                data-session-id="{{ $payment['session_id'] }}"
                data-payment-date="{{ $payment['payment_date'] }}"
                data-registration-id="{{ $payment['registration_id'] }}"
                data-payment-method="{{ $payment['payment_method'] }}"
                data-account-id="{{ $payment['account_id'] }}"
                data-amount="{{ $payment['amount'] }}">
                <td data-label="Date">
                    @if(isset($payment['payment_date']))
                    {{ \Carbon\Carbon::parse($payment['payment_date'])->format('d M Y') }}
                    @else —
                    @endif
                </td>
                <td data-label="Participant" style="font-weight: 700;">{{ $payment['participant_name'] ?? '—' }}</td>
                <td data-label="Enrolled In">
                    @if($payment['enrollment_type'] === 'program')
                    <span class="badge-filtered" style="color: #2f6fed;">Program</span>
                    @elseif($payment['enrollment_type'] === 'session')
                    <span class="badge-filtered" style="color: #8a3fd1;">Session</span>
                    @else
                    <span class="badge-filtered">—</span>
                    @endif
                    {{ $payment['target_name'] ?? '—' }}
                </td>
                <td data-label="Amount" style="font-weight: 700;">{{ number_format((float) ($payment['amount'] ?? 0), 2) }}</td>
                <td data-label="Method">{{ $payment['payment_method'] ?? '—' }}</td>
                <td data-label="Account">{{ $payment['account_name'] ?? '—' }}</td>
                <td data-label="Notes">{{ $payment['notes'] ?? '—' }}</td>
                <td data-label="Recorded By" style="color: #8a9ab0; font-size: 0.83rem;">{{ $payment['created_by'] ?? '—' }}</td>
                <td data-label="Actions">
                    <form method="POST" action="{{ route('admin.payments.destroy', $payment['id']) }}"
                        onsubmit="return confirm('Delete this payment? Its ledger entry will be deleted too and the enrollment\'s payment status will be recalculated. This cannot be undone.');" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete">&#128465;</button>
                    </form>
                </td>
            </tr>
            @endforeach
            <tr id="payments-no-match-row" style="display:none;">
                <td colspan="9" style="text-align:center; color:#8a9ab0; padding: 2rem 0;">No payments match these filters.</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px; font-size: 0.82rem;">Total Paid</td>
                <td id="payments-total-sum" style="font-weight: 800;">0.00</td>
                <td colspan="5" class="tfoot-spacer"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- Add modal --}}
<div class="modal-overlay" id="add-payment-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old='@json(old())'
    data-preselect="{{ $preselectEnrollmentId ?? '' }}">
    <div class="modal">
        <div class="modal-header">
            <h2 style="margin-bottom:0;">Record Payment</h2>
            <button type="button" class="modal-close" id="add-payment-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="add-payment-form" action="{{ route('admin.payments.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="payment-enrollment">Enrollment</label>
                    <select id="payment-enrollment" name="enrollment_id" required class="{{ $errors->has('enrollment_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select an enrollment…</option>
                        @foreach($enrollmentOptions as $enrollment)
                        <option value="{{ $enrollment['id'] }}" data-balance="{{ $enrollment['balance'] }}">
                            {{ $enrollment['participant_name'] }} — {{ ucfirst($enrollment['enrollment_type']) }}: {{ $enrollment['target_name'] }}
                            (balance {{ number_format($enrollment['balance'], 2) }} of {{ number_format($enrollment['net_price'], 2) }}@if((float) $enrollment['discount_amount'] > 0), {{ number_format((float) $enrollment['discount_amount'], 2) }} discount applied @endif)
                        </option>
                        @endforeach
                    </select>
                    @error('enrollment_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="payment-amount">Amount</label>
                    <input type="number" id="payment-amount" name="amount" step="0.01" min="0.01" required class="{{ $errors->has('amount') ? 'is-invalid' : '' }}">
                    <p style="margin: 0.35rem 0 0; color: #8a9ab0; font-size: 0.82rem;" id="payment-balance-hint"></p>
                    @error('amount')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="payment-account">Account</label>
                    <select id="payment-account" name="account_id" required class="{{ $errors->has('account_id') ? 'is-invalid' : '' }}">
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
                    <label for="payment-date">Payment Date</label>
                    <input type="date" id="payment-date" name="payment_date" required class="{{ $errors->has('payment_date') ? 'is-invalid' : '' }}">
                    @error('payment_date')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="payment-method">Payment Method</label>
                    <select id="payment-method" name="payment_method" required class="{{ $errors->has('payment_method') ? 'is-invalid' : '' }}">
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
                    <label for="payment-notes">Notes</label>
                    <textarea id="payment-notes" name="notes" rows="3" maxlength="500" class="{{ $errors->has('notes') ? 'is-invalid' : '' }}"></textarea>
                    @error('notes')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="add-payment-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('add-payment-modal-overlay');
        var form = document.getElementById('add-payment-form');
        var enrollmentField = document.getElementById('payment-enrollment');
        var accountField = document.getElementById('payment-account');
        var amountField = document.getElementById('payment-amount');
        var balanceHint = document.getElementById('payment-balance-hint');
        var dateField = document.getElementById('payment-date');
        var methodField = document.getElementById('payment-method');
        var notesField = document.getElementById('payment-notes');

        function syncBalance() {
            var selected = enrollmentField.options[enrollmentField.selectedIndex];
            var balance = selected ? parseFloat(selected.dataset.balance) : NaN;
            if (!isNaN(balance)) {
                amountField.setAttribute('max', balance.toFixed(2));
                balanceHint.textContent = 'Remaining balance: ' + balance.toFixed(2) + '.';
            } else {
                amountField.removeAttribute('max');
                balanceHint.textContent = '';
            }
        }

        enrollmentField.addEventListener('change', syncBalance);

        function openModal() {
            overlay.classList.add('open');
            syncBalance();
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
            balanceHint.textContent = '';
            amountField.removeAttribute('max');
        }

        document.getElementById('add-payment-btn').addEventListener('click', function () {
            resetForm();
            openModal();
        });
        document.getElementById('add-payment-modal-close').addEventListener('click', closeModal);
        document.getElementById('add-payment-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        // Reopen with previous input after a validation error, or when
        // arriving from the Enrollments page's "Record Payment" link.
        if (overlay.dataset.reopen === '1') {
            var old = JSON.parse(overlay.dataset.old || '{}');
            openModal();
            if (old.enrollment_id) enrollmentField.value = old.enrollment_id;
            if (old.account_id) accountField.value = old.account_id;
            if (old.amount) amountField.value = old.amount;
            if (old.payment_date) dateField.value = old.payment_date;
            if (old.payment_method) methodField.value = old.payment_method;
            if (old.notes) notesField.value = old.notes;
            syncBalance();
        } else if (overlay.dataset.preselect) {
            openModal();
            enrollmentField.value = overlay.dataset.preselect;
            syncBalance();
        }

        @if(count($payments) > 0)
        // ── Filters ────────────────────────────────────────
        // Entirely client-side against the rows already in the DOM — nothing
        // here is ever sent as a URL query parameter.
        var paymentRows = Array.prototype.slice.call(document.querySelectorAll('tbody tr[data-payment-row]'));
        var noMatchRow = document.getElementById('payments-no-match-row');
        var filterProgram = document.getElementById('payments-filter-program');
        var filterSession = document.getElementById('payments-filter-session');
        var filterDateFrom = document.getElementById('payments-filter-date-from');
        var filterDateTo = document.getElementById('payments-filter-date-to');
        var filterParticipant = document.getElementById('payments-filter-participant');
        var filterMethod = document.getElementById('payments-filter-method');
        var filterAccount = document.getElementById('payments-filter-account');
        var filterClear = document.getElementById('payments-filter-clear');
        var totalSumEl = document.getElementById('payments-total-sum');

        function rowMatchesPaymentFilters(row) {
            if (filterProgram.value && row.dataset.programId !== filterProgram.value) return false;
            if (filterSession.value && row.dataset.sessionId !== filterSession.value) return false;
            if (filterDateFrom.value && row.dataset.paymentDate < filterDateFrom.value) return false;
            if (filterDateTo.value && row.dataset.paymentDate > filterDateTo.value) return false;
            if (filterParticipant.value && row.dataset.registrationId !== filterParticipant.value) return false;
            if (filterMethod.value && row.dataset.paymentMethod !== filterMethod.value) return false;
            if (filterAccount.value && row.dataset.accountId !== filterAccount.value) return false;
            return true;
        }

        function renderPayments() {
            var anyVisible = false;
            var totalSum = 0;
            paymentRows.forEach(function (row) {
                var matches = rowMatchesPaymentFilters(row);
                row.style.display = matches ? '' : 'none';
                if (matches) {
                    anyVisible = true;
                    totalSum += parseFloat(row.dataset.amount || '0');
                }
            });
            noMatchRow.style.display = anyVisible ? 'none' : '';
            totalSumEl.textContent = totalSum.toFixed(2);
        }

        [filterProgram, filterSession, filterDateFrom, filterDateTo, filterParticipant, filterMethod, filterAccount].forEach(function (el) {
            el.addEventListener('change', renderPayments);
        });

        filterClear.addEventListener('click', function () {
            filterProgram.value = '';
            filterSession.value = '';
            filterDateFrom.value = '';
            filterDateTo.value = '';
            filterParticipant.value = '';
            filterMethod.value = '';
            filterAccount.value = '';
            renderPayments();
        });

        renderPayments();
        @endif
    });
</script>
@endpush
