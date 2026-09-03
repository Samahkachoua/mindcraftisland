@extends('layouts.admin')

@section('title', 'Rental Items — Mind Craft Island Admin')

@section('breadcrumb', 'Rental Items')

@section('admin-content')

<div style="margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1>Rental Items <span class="badge-count">{{ $total }}</span></h1>
        <p class="page-subtitle">The catalog of items available to rent out.</p>
    </div>
    <button type="button" class="btn btn-primary" id="add-rental-item-btn">+ Add Rental Item</button>
</div>

@if(session('success'))
<div class="alert alert-success"><span>&#10003;</span><span>{{ session('success') }}</span></div>
@endif
@if(session('error'))
<div class="alert alert-error"><span>&#9888;</span><span>{{ session('error') }}</span></div>
@endif

@if(count($rentalItems) === 0)
<div class="card empty-state">
    <div class="empty-icon">&#127919;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No rental items yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Add your first item to start renting it out.</p>
</div>
@else
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Name (EN)</th>
                <th>Name (AR)</th>
                <th>Rate</th>
                <th>Deposit</th>
                <th>Status</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rentalItems as $item)
            <tr>
                <td data-label="Name (EN)" style="font-weight: 700;">{{ $item['name_en'] ?? '—' }}</td>
                <td data-label="Name (AR)">{{ $item['name_ar'] ?? '—' }}</td>
                <td data-label="Rate">{{ number_format((float) ($item['rate'] ?? 0), 2) }}</td>
                <td data-label="Deposit">{{ isset($item['deposit_amount']) ? number_format((float) $item['deposit_amount'], 2) : '—' }}</td>
                <td data-label="Status">
                    @php
                    $statusColors = ['available' => '#1a8a4a', 'rented' => '#c98a1a', 'maintenance' => '#c0392b'];
                    $statusColor = $statusColors[$item['status']] ?? '#8a9ab0';
                    @endphp
                    <span class="badge-filtered" style="color: {{ $statusColor }};">{{ ucfirst($item['status']) }}</span>
                </td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon edit-rental-item-btn"
                            data-id="{{ $item['id'] }}"
                            data-name-en="{{ $item['name_en'] }}"
                            data-name-ar="{{ $item['name_ar'] }}"
                            data-rate="{{ $item['rate'] }}"
                            data-deposit-amount="{{ $item['deposit_amount'] }}"
                            data-status="{{ $item['status'] }}"
                            title="Edit">&#9998;</button>
                        @if($item['in_use'])
                        <button type="button" class="btn-icon btn-icon-danger" disabled title="This rental item has rental history and cannot be deleted">&#128465;</button>
                        @else
                        <form method="POST" action="{{ route('admin.rental-items.destroy', $item['id']) }}"
                            onsubmit="return confirm('Delete rental item &quot;{{ $item['name_en'] }}&quot;? This cannot be undone.');" style="display:inline;">
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
<div class="modal-overlay" id="rental-item-modal-overlay"
    data-reopen="{{ $errors->any() ? '1' : '0' }}"
    data-old='@json(old())'>
    <div class="modal">
        <div class="modal-header">
            <h2 id="rental-item-modal-title" style="margin-bottom:0;">Add Rental Item</h2>
            <button type="button" class="modal-close" id="rental-item-modal-close" aria-label="Close">&#215;</button>
        </div>
        <form method="POST" id="rental-item-form" action="{{ route('admin.rental-items.store') }}">
            @csrf
            <div id="rental-item-method-field"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="rental-item-name-en">Name (English)</label>
                    <input type="text" id="rental-item-name-en" name="name_en" required maxlength="150" class="{{ $errors->has('name_en') ? 'is-invalid' : '' }}">
                    @error('name_en')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-item-name-ar">Name (Arabic)</label>
                    <input type="text" id="rental-item-name-ar" name="name_ar" required maxlength="150" dir="rtl" class="{{ $errors->has('name_ar') ? 'is-invalid' : '' }}">
                    @error('name_ar')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-item-rate">Rate</label>
                    <input type="number" id="rental-item-rate" name="rate" step="0.01" min="0" required class="{{ $errors->has('rate') ? 'is-invalid' : '' }}">
                    @error('rate')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-item-deposit">Deposit Amount</label>
                    <input type="number" id="rental-item-deposit" name="deposit_amount" step="0.01" min="0" class="{{ $errors->has('deposit_amount') ? 'is-invalid' : '' }}">
                    @error('deposit_amount')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="rental-item-status">Status</label>
                    <select id="rental-item-status" name="status" required class="{{ $errors->has('status') ? 'is-invalid' : '' }}">
                        @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @error('status')
                    <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="rental-item-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('rental-item-modal-overlay');
        var title = document.getElementById('rental-item-modal-title');
        var form = document.getElementById('rental-item-form');
        var methodField = document.getElementById('rental-item-method-field');
        var storeUrl = '{{ route("admin.rental-items.store") }}';

        var nameEnField = document.getElementById('rental-item-name-en');
        var nameArField = document.getElementById('rental-item-name-ar');
        var rateField = document.getElementById('rental-item-rate');
        var depositField = document.getElementById('rental-item-deposit');
        var statusField = document.getElementById('rental-item-status');

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
            rateField.value = '';
            depositField.value = '';
            statusField.value = 'available';
        }

        function openForAdd() {
            title.textContent = 'Add Rental Item';
            form.action = storeUrl;
            methodField.innerHTML = '';
            resetForm();
            openModal();
        }

        function openForEdit(btn) {
            title.textContent = 'Edit Rental Item';
            form.action = storeUrl + '/' + btn.dataset.id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            nameEnField.value = btn.dataset.nameEn;
            nameArField.value = btn.dataset.nameAr;
            rateField.value = btn.dataset.rate;
            depositField.value = btn.dataset.depositAmount === 'undefined' ? '' : btn.dataset.depositAmount;
            statusField.value = btn.dataset.status;
            openModal();
        }

        document.getElementById('add-rental-item-btn').addEventListener('click', openForAdd);
        document.getElementById('rental-item-modal-close').addEventListener('click', closeModal);
        document.getElementById('rental-item-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.querySelectorAll('.edit-rental-item-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openForEdit(btn);
            });
        });

        if (overlay.dataset.reopen === '1') {
            var old = JSON.parse(overlay.dataset.old || '{}');
            openForAdd();
            if (old.name_en) nameEnField.value = old.name_en;
            if (old.name_ar) nameArField.value = old.name_ar;
            if (old.rate) rateField.value = old.rate;
            if (old.deposit_amount) depositField.value = old.deposit_amount;
            if (old.status) statusField.value = old.status;
        }
    });
</script>
@endpush
