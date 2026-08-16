@extends('layouts.admin')

@section('title', 'Enrollments — Mind Craft Island Admin')

@section('breadcrumb', 'Enrollments')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Enrollments <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">Enroll a participant in a Program or a standalone Session, then record payments right here.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-enrollment-btn">+ Add Enrollment</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($enrollments) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#128221;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No enrollments yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Click "Add Enrollment" above to enroll a participant.</p>
</div>
@else
{{-- Filters are applied entirely client-side against the already-rendered
     rows below — nothing here ever becomes a URL query parameter, so
     filtering/pagination state never touches the address bar or reloads. --}}
<div class="search-bar" style="flex-wrap: wrap;">
    <input type="date" id="enrollments-filter-start-date" class="search-input" style="max-width: 170px;" title="Filter by date (program start date or session date)">
    <select id="enrollments-filter-program" class="search-input" style="max-width: 200px;">
        <option value="">Program</option>
        @foreach($programs as $program)
        <option value="{{ $program['id'] }}">{{ $program['name'] }}</option>
        @endforeach
    </select>
    <select id="enrollments-filter-session" class="search-input" style="max-width: 200px;">
        <option value="">Session</option>
        @foreach($sessions as $session)
        <option value="{{ $session['id'] }}">{{ $session['name'] }}</option>
        @endforeach
    </select>
    <select id="enrollments-filter-status" class="search-input" style="max-width: 160px;">
        <option value="">Status</option>
        <option value="unpaid">Unpaid</option>
        <option value="partial">Partial</option>
        <option value="paid">Paid</option>
    </select>
    <button type="button" class="btn btn-secondary" id="enrollments-filter-clear" style="padding: 0.62rem 1rem; font-size: 0.92rem;">Clear Filters</button>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Participant</th>
                <th>Type</th>
                <th>Program / Session</th>
                <th>Start / Session Date</th>
                <th>Expires</th>
                <th>Price</th>
                <th>Discount</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Status</th>
                <th style="width: 200px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enrollment)
            <tr data-enrollment-row
                data-type="{{ $enrollment['enrollment_type'] }}"
                data-program-id="{{ $enrollment['program_id'] }}"
                data-session-id="{{ $enrollment['session_id'] }}"
                data-start-date="{{ $enrollment['start_date'] }}"
                data-status="{{ $enrollment['display_status'] }}"
                data-total="{{ $enrollment['total'] }}"
                data-paid="{{ $enrollment['amount_paid'] }}"
                @if($enrollment['is_expired_unpaid']) style="background-color: rgba(192, 57, 43, 0.08); border-left: 3px solid #c0392b;" @endif>
                <td data-label="Participant" style="font-weight: 700;">{{ $enrollment['participant_name'] ?? '—' }}</td>
                <td data-label="Type">{{ ucfirst($enrollment['enrollment_type'] ?? '—') }}</td>
                <td data-label="Program / Session">{{ $enrollment['target_name'] ?? '—' }}</td>
                <td data-label="Start / Session Date">
                    @if(!empty($enrollment['start_date']))
                    {{ \Carbon\Carbon::parse($enrollment['start_date'])->format('d M Y') }}
                    @else —
                    @endif
                </td>
                <td data-label="Expires">
                    @if(!empty($enrollment['expiry_date']))
                    {{ \Carbon\Carbon::parse($enrollment['expiry_date'])->format('d M Y') }}
                    @else —
                    @endif
                </td>
                <td data-label="Price">{{ number_format((float) ($enrollment['price'] ?? 0), 2) }}</td>
                <td data-label="Discount">
                    @if((float) ($enrollment['discount_amount'] ?? 0) > 0)
                    {{ number_format((float) $enrollment['discount_amount'], 2) }}
                    @else —
                    @endif
                </td>
                <td data-label="Total" style="font-weight: 700;">{{ number_format((float) ($enrollment['total'] ?? 0), 2) }}</td>
                <td data-label="Paid">{{ number_format((float) ($enrollment['amount_paid'] ?? 0), 2) }}</td>
                <td data-label="Status">
                    @php
                    $statusColors = ['unpaid' => '#c0392b', 'partial' => '#c98a1a', 'paid' => '#1a8a4a'];
                    $statusColor = $statusColors[$enrollment['display_status']] ?? '#8a9ab0';
                    @endphp
                    <span class="badge-filtered" style="color: {{ $statusColor }};">{{ ucfirst($enrollment['display_status']) }}</span>
                </td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon edit-enrollment-btn"
                            data-id="{{ $enrollment['id'] }}"
                            data-registration-id="{{ $enrollment['registration_id'] }}"
                            data-participant-name="{{ $enrollment['participant_name'] }}"
                            data-type="{{ $enrollment['enrollment_type'] }}"
                            data-target-name="{{ $enrollment['target_name'] }}"
                            data-price="{{ $enrollment['price'] }}"
                            data-start-date="{{ $enrollment['start_date'] }}"
                            data-discount-amount="{{ $enrollment['discount_amount'] }}"
                            title="Edit">&#9998;</button>
                        @if((float) $enrollment['balance'] > 0)
                        <button type="button" class="btn-icon pay-enrollment-btn"
                            data-id="{{ $enrollment['id'] }}"
                            data-participant-name="{{ $enrollment['participant_name'] }}"
                            data-target-name="{{ $enrollment['target_name'] }}"
                            data-balance="{{ $enrollment['balance'] }}"
                            title="Record Payment">&#128179;</button>
                        @endif
                        {{-- Nothing shown once the enrollment is fully paid — the Status column already covers that. --}}
                        <form method="POST" action="{{ route('admin.enrollments.destroy', $enrollment['id']) }}"
                            onsubmit="return confirm('Delete this enrollment? This cannot be undone.');" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete">&#128465;</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            <tr id="enrollments-no-match-row" style="display:none;">
                <td colspan="11" style="text-align:center; color:#8a9ab0; padding: 2rem 0;">No enrollments match these filters.</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" style="font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px; font-size: 0.82rem;">Total</td>
                <td id="enrollments-total-sum" style="font-weight: 800;">0.00</td>
                <td id="enrollments-paid-sum" style="font-weight: 800;">0.00</td>
                <td colspan="2" class="tfoot-spacer"></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="pagination-bar" id="enrollments-pagination-bar" style="display:none; margin-top: 1rem;">
    <div class="page-info" id="enrollments-page-info"></div>
    <div class="page-buttons">
        <button type="button" class="page-btn" id="enrollments-prev-btn">&#8592;</button>
        <button type="button" class="page-btn" id="enrollments-next-btn">&#8594;</button>
    </div>
</div>
@endif

{{-- Add modal --}}
<div class="modal-overlay" id="add-enrollment-modal-overlay"
    data-reopen="{{ old('enrollment_type') !== null ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 style="margin-bottom:0;">Add Enrollment</h2>
            <button type="button" class="modal-close" id="add-enrollment-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="add-enrollment-form" action="{{ route('admin.enrollments.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="enrollment-registration">Participant</label>
                    <select id="enrollment-registration" name="registration_id" required class="{{ $errors->has('registration_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select a participant…</option>
                        @foreach($registrations as $registration)
                        <option value="{{ $registration['id'] }}">{{ $registration['full_name'] }} ({{ $registration['phone_number'] }})</option>
                        @endforeach
                    </select>
                    @error('registration_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="enrollment-type">Enroll In</label>
                    <select id="enrollment-type" name="enrollment_type" required class="{{ $errors->has('enrollment_type') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select…</option>
                        @foreach($enrollmentTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    @error('enrollment_type')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group" id="enrollment-program-field">
                    <label for="enrollment-program">Program</label>
                    <select id="enrollment-program" name="program_id" class="{{ $errors->has('program_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select a program…</option>
                        @foreach($programs as $program)
                        <option value="{{ $program['id'] }}">{{ $program['name'] }} ({{ number_format((float) $program['program_price'], 2) }})</option>
                        @endforeach
                    </select>
                    @error('program_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group" id="enrollment-session-field">
                    <label for="enrollment-session">Session</label>
                    <select id="enrollment-session" name="session_id" class="{{ $errors->has('session_id') ? 'is-invalid' : '' }}">
                        <option value="" disabled selected>Select a session…</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session['id'] }}">{{ $session['name'] }} ({{ number_format((float) $session['price'], 2) }})</option>
                        @endforeach
                    </select>
                    @error('session_id')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group" id="enrollment-start-date-field">
                    <label for="enrollment-start-date" id="enrollment-start-date-label">Date</label>
                    <input type="date" id="enrollment-start-date" name="start_date" required class="{{ $errors->has('start_date') ? 'is-invalid' : '' }}">
                    @error('start_date')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                    <p style="margin: 0.35rem 0 0; color: #8a9ab0; font-size: 0.82rem;" id="enrollment-start-date-help"></p>
                </div>
                <div class="form-group">
                    <label for="enrollment-discount-amount">Discount Amount</label>
                    <input type="number" id="enrollment-discount-amount" name="discount_amount" step="0.01" min="0" value="0" class="{{ $errors->has('discount_amount') ? 'is-invalid' : '' }}">
                    @error('discount_amount')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                    <p style="margin: 0.35rem 0 0; color: #8a9ab0; font-size: 0.82rem;">Flat amount knocked off the price for this participant. Leave at 0 if none applies.</p>
                </div>
                <p style="margin: 0; color: #8a9ab0; font-size: 0.85rem;">The price is captured automatically from the program/session's current price at the moment of enrollment.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="add-enrollment-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit modal --}}
<div class="modal-overlay" id="edit-enrollment-modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 style="margin-bottom:0;">Edit Enrollment</h2>
            <button type="button" class="modal-close" id="edit-enrollment-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="edit-enrollment-form">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <p style="margin: 0 0 1rem;">
                    <span id="edit-enrollment-type-badge" class="badge-filtered"></span>
                    <strong id="edit-enrollment-target"></strong>
                    <br>
                    <span style="color: #8a9ab0; font-size: 0.85rem;">Price: <span id="edit-enrollment-price"></span> (fixed at enrollment time, not editable)</span>
                </p>
                <div class="form-group">
                    <label for="edit-enrollment-registration">Participant</label>
                    <select id="edit-enrollment-registration" name="registration_id" required>
                        @foreach($registrations as $registration)
                        <option value="{{ $registration['id'] }}">{{ $registration['full_name'] }} ({{ $registration['phone_number'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-enrollment-start-date" id="edit-enrollment-start-date-label">Date</label>
                    <input type="date" id="edit-enrollment-start-date" name="start_date" required>
                    <p style="margin: 0.35rem 0 0; color: #8a9ab0; font-size: 0.82rem;" id="edit-enrollment-start-date-help"></p>
                </div>
                <div class="form-group">
                    <label for="edit-enrollment-discount-amount">Discount Amount</label>
                    <input type="number" id="edit-enrollment-discount-amount" name="discount_amount" step="0.01" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="edit-enrollment-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Quick-pay modal --}}
<div class="modal-overlay" id="pay-enrollment-modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 style="margin-bottom:0;">Record Payment</h2>
            <button type="button" class="modal-close" id="pay-enrollment-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="pay-enrollment-form" action="{{ route('admin.payments.store') }}">
            @csrf
            <input type="hidden" id="pay-enrollment-id" name="enrollment_id">
            <div class="modal-body">
                <p style="margin: 0 0 1rem;">
                    <strong id="pay-enrollment-participant"></strong> — <span id="pay-enrollment-target"></span>
                </p>
                <div class="form-group">
                    <label for="pay-enrollment-amount">Amount</label>
                    <input type="number" id="pay-enrollment-amount" name="amount" step="0.01" min="0.01" required>
                    <p style="margin: 0.35rem 0 0; color: #8a9ab0; font-size: 0.82rem;">Remaining balance: <span id="pay-enrollment-balance"></span>. Defaults to the full remaining balance — lower it for a partial payment.</p>
                </div>
                <div class="form-group">
                    <label for="pay-enrollment-date">Payment Date</label>
                    <input type="date" id="pay-enrollment-date" name="payment_date" required>
                </div>
                <div class="form-group">
                    <label for="pay-enrollment-method">Payment Method</label>
                    <select id="pay-enrollment-method" name="payment_method" required>
                        <option value="" disabled selected>Select a payment method…</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="pay-enrollment-notes">Notes</label>
                    <textarea id="pay-enrollment-notes" name="notes" rows="3" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="pay-enrollment-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ── Add modal ────────────────────────────────────────
        var addOverlay = document.getElementById('add-enrollment-modal-overlay');
        var addForm = document.getElementById('add-enrollment-form');
        var typeField = document.getElementById('enrollment-type');
        var programField = document.getElementById('enrollment-program-field');
        var sessionField = document.getElementById('enrollment-session-field');
        var startDateLabel = document.getElementById('enrollment-start-date-label');
        var startDateHelp = document.getElementById('enrollment-start-date-help');

        var PROGRAM_DATE_HELP = "Expiry date is calculated automatically from this date and the program's weekday pattern.";
        var SESSION_DATE_HELP = 'The specific date this participant is attending.';

        function syncTargetFields() {
            var type = typeField.value;
            programField.style.display = type === 'program' ? '' : 'none';
            sessionField.style.display = type === 'session' ? '' : 'none';
            if (type === 'session') {
                startDateLabel.textContent = 'Session Date';
                startDateHelp.textContent = SESSION_DATE_HELP;
            } else {
                startDateLabel.textContent = 'Start Date';
                startDateHelp.textContent = PROGRAM_DATE_HELP;
            }
        }

        typeField.addEventListener('change', syncTargetFields);

        function openAddModal() {
            addOverlay.classList.add('open');
            syncTargetFields();
        }

        function closeAddModal() {
            addOverlay.classList.remove('open');
        }

        document.getElementById('add-enrollment-btn').addEventListener('click', function () {
            addForm.reset();
            syncTargetFields();
            openAddModal();
        });
        document.getElementById('add-enrollment-modal-close').addEventListener('click', closeAddModal);
        document.getElementById('add-enrollment-modal-cancel').addEventListener('click', closeAddModal);
        addOverlay.addEventListener('click', function (e) {
            if (e.target === addOverlay) closeAddModal();
        });

        if (addOverlay.dataset.reopen === '1') {
            var old = JSON.parse(addOverlay.dataset.old || '{}');
            openAddModal();
            if (old.registration_id) document.getElementById('enrollment-registration').value = old.registration_id;
            if (old.enrollment_type) typeField.value = old.enrollment_type;
            if (old.program_id) document.getElementById('enrollment-program').value = old.program_id;
            if (old.session_id) document.getElementById('enrollment-session').value = old.session_id;
            if (old.start_date) document.getElementById('enrollment-start-date').value = old.start_date;
            if (old.discount_amount) document.getElementById('enrollment-discount-amount').value = old.discount_amount;
            syncTargetFields();
        }

        // ── Edit modal ───────────────────────────────────────
        var editOverlay = document.getElementById('edit-enrollment-modal-overlay');
        var editForm = document.getElementById('edit-enrollment-form');
        var editTypeBadge = document.getElementById('edit-enrollment-type-badge');
        var editTarget = document.getElementById('edit-enrollment-target');
        var editPrice = document.getElementById('edit-enrollment-price');
        var editRegistration = document.getElementById('edit-enrollment-registration');
        var editStartDateLabel = document.getElementById('edit-enrollment-start-date-label');
        var editStartDate = document.getElementById('edit-enrollment-start-date');
        var editStartDateHelp = document.getElementById('edit-enrollment-start-date-help');
        var editDiscountAmount = document.getElementById('edit-enrollment-discount-amount');

        function openEditModal() {
            editOverlay.classList.add('open');
        }

        function closeEditModal() {
            editOverlay.classList.remove('open');
        }

        document.querySelectorAll('.edit-enrollment-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editForm.action = '{{ url("/admin/enrollments") }}/' + btn.dataset.id;
                editTypeBadge.textContent = btn.dataset.type.charAt(0).toUpperCase() + btn.dataset.type.slice(1);
                editTarget.textContent = btn.dataset.targetName;
                editPrice.textContent = parseFloat(btn.dataset.price).toFixed(2);
                editRegistration.value = btn.dataset.registrationId;
                editDiscountAmount.value = btn.dataset.discountAmount;
                editStartDate.value = btn.dataset.startDate;
                if (btn.dataset.type === 'program') {
                    editStartDateLabel.textContent = 'Start Date';
                    editStartDateHelp.textContent = 'Expiry date will be recalculated automatically from this date.';
                } else {
                    editStartDateLabel.textContent = 'Session Date';
                    editStartDateHelp.textContent = 'The specific date this participant is attending.';
                }
                openEditModal();
            });
        });

        document.getElementById('edit-enrollment-modal-close').addEventListener('click', closeEditModal);
        document.getElementById('edit-enrollment-modal-cancel').addEventListener('click', closeEditModal);
        editOverlay.addEventListener('click', function (e) {
            if (e.target === editOverlay) closeEditModal();
        });

        // ── Quick-pay modal ──────────────────────────────────
        var payOverlay = document.getElementById('pay-enrollment-modal-overlay');
        var payId = document.getElementById('pay-enrollment-id');
        var payParticipant = document.getElementById('pay-enrollment-participant');
        var payTarget = document.getElementById('pay-enrollment-target');
        var payBalanceLabel = document.getElementById('pay-enrollment-balance');
        var payAmount = document.getElementById('pay-enrollment-amount');
        var payDate = document.getElementById('pay-enrollment-date');
        var payMethod = document.getElementById('pay-enrollment-method');
        var payNotes = document.getElementById('pay-enrollment-notes');

        function openPayModal() {
            payOverlay.classList.add('open');
        }

        function closePayModal() {
            payOverlay.classList.remove('open');
        }

        function todayIso() {
            var d = new Date();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            return d.getFullYear() + '-' + m + '-' + day;
        }

        document.querySelectorAll('.pay-enrollment-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var balance = parseFloat(btn.dataset.balance);
                payId.value = btn.dataset.id;
                payParticipant.textContent = btn.dataset.participantName;
                payTarget.textContent = btn.dataset.targetName;
                payBalanceLabel.textContent = balance.toFixed(2);
                payAmount.value = balance.toFixed(2);
                payAmount.setAttribute('max', balance.toFixed(2));
                payDate.value = todayIso();
                payMethod.value = '';
                payNotes.value = '';
                openPayModal();
            });
        });

        document.getElementById('pay-enrollment-modal-close').addEventListener('click', closePayModal);
        document.getElementById('pay-enrollment-modal-cancel').addEventListener('click', closePayModal);
        payOverlay.addEventListener('click', function (e) {
            if (e.target === payOverlay) closePayModal();
        });

        // The `max` attribute set above triggers native browser validation on
        // submit; the server independently re-checks this as the source of truth.

        @if($errors->any() && old('enrollment_id'))
        payId.value = '{{ old('enrollment_id') }}';
        payAmount.value = '{{ old('amount') }}';
        payDate.value = '{{ old('payment_date') }}';
        payMethod.value = '{{ old('payment_method') }}';
        payNotes.value = '{{ old('notes') }}';
        openPayModal();
        @endif

        @if(count($enrollments) > 0)
        // ── Filters, footer sums & pagination ─────────────────
        // Entirely client-side against the rows already in the DOM — nothing
        // here is ever sent as a URL query parameter or triggers a reload.
        var rows = Array.prototype.slice.call(document.querySelectorAll('tbody tr[data-enrollment-row]'));
        var noMatchRow = document.getElementById('enrollments-no-match-row');
        var filterStartDate = document.getElementById('enrollments-filter-start-date');
        var filterProgram = document.getElementById('enrollments-filter-program');
        var filterSession = document.getElementById('enrollments-filter-session');
        var filterStatus = document.getElementById('enrollments-filter-status');
        var filterClear = document.getElementById('enrollments-filter-clear');
        var totalSumEl = document.getElementById('enrollments-total-sum');
        var paidSumEl = document.getElementById('enrollments-paid-sum');
        var paginationBar = document.getElementById('enrollments-pagination-bar');
        var pageInfo = document.getElementById('enrollments-page-info');
        var prevBtn = document.getElementById('enrollments-prev-btn');
        var nextBtn = document.getElementById('enrollments-next-btn');
        var perPage = 15;
        var currentPage = 1;

        function rowMatchesFilters(row) {
            if (filterStartDate.value && row.dataset.startDate !== filterStartDate.value) return false;
            if (filterProgram.value && row.dataset.programId !== filterProgram.value) return false;
            if (filterSession.value && row.dataset.sessionId !== filterSession.value) return false;
            if (filterStatus.value && row.dataset.status !== filterStatus.value) return false;
            return true;
        }

        function renderEnrollments() {
            var matching = rows.filter(rowMatchesFilters);

            var totalSum = matching.reduce(function (sum, r) { return sum + parseFloat(r.dataset.total || '0'); }, 0);
            var paidSum = matching.reduce(function (sum, r) { return sum + parseFloat(r.dataset.paid || '0'); }, 0);
            totalSumEl.textContent = totalSum.toFixed(2);
            paidSumEl.textContent = paidSum.toFixed(2);

            noMatchRow.style.display = matching.length === 0 ? '' : 'none';

            var totalPages = Math.max(1, Math.ceil(matching.length / perPage));
            if (currentPage > totalPages) currentPage = totalPages;

            rows.forEach(function (r) { r.style.display = 'none'; });
            var start = (currentPage - 1) * perPage;
            matching.slice(start, start + perPage).forEach(function (r) { r.style.display = ''; });

            if (matching.length > perPage) {
                paginationBar.style.display = '';
                var shownFrom = matching.length === 0 ? 0 : start + 1;
                var shownTo = Math.min(start + perPage, matching.length);
                pageInfo.textContent = 'Showing ' + shownFrom + '–' + shownTo + ' of ' + matching.length + ' enrollments — page ' + currentPage + ' of ' + totalPages;
                prevBtn.disabled = currentPage === 1;
                nextBtn.disabled = currentPage === totalPages;
            } else {
                paginationBar.style.display = 'none';
            }
        }

        [filterStartDate, filterProgram, filterSession, filterStatus].forEach(function (el) {
            el.addEventListener('change', function () {
                currentPage = 1;
                renderEnrollments();
            });
        });

        filterClear.addEventListener('click', function () {
            filterStartDate.value = '';
            filterProgram.value = '';
            filterSession.value = '';
            filterStatus.value = '';
            currentPage = 1;
            renderEnrollments();
        });

        prevBtn.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                renderEnrollments();
            }
        });
        nextBtn.addEventListener('click', function () {
            currentPage++;
            renderEnrollments();
        });

        renderEnrollments();
        @endif
    });
</script>
@endpush
