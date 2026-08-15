@if($expenses->total() === 0)
<div class="card empty-state">
    <div class="empty-icon">&#128176;</div>
    @if($search !== '' || $categoryId !== '' || $vendorId !== '' || $paymentMethod !== '' || $dateFrom !== '' || $dateTo !== '')
    <p style="font-weight: 700; font-size: 1.1rem;">No expenses match these filters.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;"><a href="{{ route('admin.expenses', ['sort' => $sort, 'direction' => $direction]) }}" class="ajax-nav">Clear filters</a> to see all expenses.</p>
    @else
    <p style="font-weight: 700; font-size: 1.1rem;">No expenses yet.</p>
    <p style="margin-top: 0.4rem; color: #8a9ab0;">Click "Add Expense" above to record your first one.</p>
    @endif
</div>
@else

@php
$cols = [
'expense_date' => 'Date',
'category_name' => 'Category',
'vendor_name' => 'Vendor',
'amount' => 'Amount',
'payment_method' => 'Payment Method',
];
$fixed = ['Description', 'Actions'];
$isFiltered = $search !== '' || $categoryId !== '' || $vendorId !== '' || $paymentMethod !== '' || $dateFrom !== '' || $dateTo !== '';

if (!function_exists('sortUrl')) {
function sortUrl(string $col, string $currentSort, string $currentDir): string {
$newDir = ($col === $currentSort && $currentDir === 'asc') ? 'desc' : 'asc';
return request()->fullUrlWithQuery(['sort' => $col, 'direction' => $newDir, 'page' => 1]);
}
}

if (!function_exists('sortIcon')) {
function sortIcon(string $col, string $currentSort, string $currentDir): string {
if ($col !== $currentSort) return '<span class="sort-icon">&#8597;</span>';
return $currentDir === 'asc'
? '<span class="sort-icon sort-active">&#8593;</span>'
: '<span class="sort-icon sort-active">&#8595;</span>';
}
}
@endphp

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                @foreach($cols as $key => $label)
                <th>
                    <a href="{{ sortUrl($key, $sort, $direction) }}" class="th-sort ajax-nav">
                        {{ $label }}{!! sortIcon($key, $sort, $direction) !!}
                    </a>
                </th>
                @endforeach
                @foreach($fixed as $label)
                <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
            <tr>
                <td data-label="Date">
                    @if(isset($expense['expense_date']))
                    {{ \Carbon\Carbon::parse($expense['expense_date'])->format('d M Y') }}
                    @else —
                    @endif
                </td>
                <td data-label="Category">{{ $expense['category_name'] ?? '—' }}</td>
                <td data-label="Vendor">{{ $expense['vendor_name'] ?? '—' }}</td>
                <td data-label="Amount" style="font-weight: 700;">{{ number_format((float) ($expense['amount'] ?? 0), 2) }}</td>
                <td data-label="Payment Method">{{ $expense['payment_method'] ?? '—' }}</td>
                <td data-label="Description">{{ $expense['description'] ?? '—' }}</td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon edit-expense-btn"
                            data-id="{{ $expense['id'] }}"
                            data-category-id="{{ $expense['category_id'] }}"
                            data-vendor-id="{{ $expense['vendor_id'] }}"
                            data-date="{{ $expense['expense_date'] }}"
                            data-amount="{{ $expense['amount'] }}"
                            data-payment-method="{{ $expense['payment_method'] }}"
                            data-description="{{ $expense['description'] }}"
                            title="Edit">&#9998;</button>
                        <form method="POST" action="{{ route('admin.expenses.destroy', $expense['id']) }}"
                            onsubmit="return confirm('Delete this expense? This cannot be undone.');" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete">&#128465;</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" data-label="Total" style="font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px; font-size: 0.82rem;">
                    Total{{ $isFiltered ? ' (filtered)' : '' }}
                </td>
                <td data-label="Total Amount" style="font-weight: 800;">{{ number_format($totalAmount, 2) }}</td>
                <td colspan="3" class="tfoot-spacer"></td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Pagination --}}
@if($expenses->lastPage() > 1)
<div class="pagination-bar">
    <div class="page-info">
        Showing {{ $expenses->firstItem() }}–{{ $expenses->lastItem() }} of {{ $expenses->total() }} expenses
        @if($isFiltered) <span class="badge-filtered">(filtered)</span> @endif
    </div>
    <div class="page-buttons">
        {{-- Prev --}}
        @if($expenses->onFirstPage())
        <span class="page-btn disabled">&#8592;</span>
        @else
        <a href="{{ $expenses->previousPageUrl() }}" class="page-btn ajax-nav">&#8592;</a>
        @endif

        {{-- Page numbers --}}
        @php
        $current = $expenses->currentPage();
        $last = $expenses->lastPage();
        $window = 2;
        $pages = collect();
        for ($i = max(1, $current - $window); $i <= min($last, $current + $window); $i++) {
            $pages->push($i);
            }
            $showLeadingEllipsis = $pages->first() > 2;
            $showTrailingEllipsis = $pages->last() < $last - 1;
                @endphp

                @if($pages->first() > 1)
                <a href="{{ $expenses->url(1) }}" class="page-btn ajax-nav">1</a>
                @endif
                @if($showLeadingEllipsis)
                <span class="page-btn disabled">&hellip;</span>
                @endif

                @foreach($pages as $p)
                @if($p === $current)
                <span class="page-btn active">{{ $p }}</span>
                @else
                <a href="{{ $expenses->url($p) }}" class="page-btn ajax-nav">{{ $p }}</a>
                @endif
                @endforeach

                @if($showTrailingEllipsis)
                <span class="page-btn disabled">&hellip;</span>
                @endif
                @if($pages->last() < $last)
                    <a href="{{ $expenses->url($last) }}" class="page-btn ajax-nav">{{ $last }}</a>
                    @endif

                    {{-- Next --}}
                    @if($expenses->hasMorePages())
                    <a href="{{ $expenses->nextPageUrl() }}" class="page-btn ajax-nav">&#8594;</a>
                    @else
                    <span class="page-btn disabled">&#8594;</span>
                    @endif
    </div>
</div>
@else
<div class="page-info" style="margin-top: 1rem;">
    Showing all {{ $expenses->total() }} expense{{ $expenses->total() !== 1 ? 's' : '' }}
    @if($isFiltered) <span class="badge-filtered">(filtered)</span> @endif
</div>
@endif

@endif
