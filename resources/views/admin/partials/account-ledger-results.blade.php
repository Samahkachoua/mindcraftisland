{{-- These three reflect the currently applied filters and reconcile as
     Opening + Current = Overall. Opening Balance is the running balance
     immediately before date_from (carrying forward everything earlier),
     falling back to the account's real opening_balance when there's no
     date_from or nothing before it. Current Balance is the net movement of
     just the filtered/visible transactions. Overall Balance is the running
     balance as of the last filtered transaction (already correctly
     includes any activity from before the filtered window, since
     running_balance is always computed over the account's complete
     history). --}}
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-value" style="color: {{ $openingBalance < 0 ? '#c0392b' : 'var(--coral)' }};">{{ number_format($openingBalance, 2) }}</div>
        <div class="stat-label">Opening Balance</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: {{ $currentBalance < 0 ? '#c0392b' : 'var(--coral)' }};">{{ number_format($currentBalance, 2) }}</div>
        <div class="stat-label">Current Balance (filtered)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: {{ ($overallBalance ?? 0) < 0 ? '#c0392b' : 'var(--coral)' }};">{{ $overallBalance !== null ? number_format($overallBalance, 2) : '—' }}</div>
        <div class="stat-label">Overall Balance (as of filter)</div>
    </div>
</div>

@if($transactions->total() === 0)
<div class="card empty-state">
    <div class="empty-icon">&#128220;</div>
    <p style="font-weight: 700; font-size: 1.1rem;">No transactions match these filters.</p>
</div>
@else
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Direction</th>
                <th>Amount</th>
                <th>Category</th>
                <th>Reference</th>
                <th>Note</th>
                <th>Running Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td data-label="Date">{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                <td data-label="Direction">
                    <span class="badge-filtered" style="color: {{ $transaction['direction'] === 'in' ? '#1a8a4a' : '#c0392b' }};">
                        {{ $transaction['direction'] === 'in' ? 'In' : 'Out' }}
                    </span>
                </td>
                <td data-label="Amount" style="font-weight: 700;">{{ number_format((float) $transaction['amount'], 2) }}</td>
                <td data-label="Category">{{ ucfirst(str_replace('_', ' ', $transaction['category'])) }}</td>
                <td data-label="Reference">{{ $transaction['reference_label'] }}</td>
                <td data-label="Note">{{ $transaction['note'] ?? '—' }}</td>
                <td data-label="Running Balance" style="font-weight: 700;">{{ number_format((float) $transaction['running_balance'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($transactions->lastPage() > 1)
<div class="pagination-bar">
    <div class="page-info">
        Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
    </div>
    <div class="page-buttons">
        {{-- Prev --}}
        @if($transactions->onFirstPage())
        <span class="page-btn disabled">&#8592;</span>
        @else
        <a href="{{ $transactions->previousPageUrl() }}" class="page-btn ajax-nav">&#8592;</a>
        @endif

        {{-- Page numbers --}}
        @php
        $current = $transactions->currentPage();
        $last = $transactions->lastPage();
        $window = 2;
        $pages = collect();
        for ($i = max(1, $current - $window); $i <= min($last, $current + $window); $i++) {
            $pages->push($i);
            }
            $showLeadingEllipsis = $pages->first() > 2;
            $showTrailingEllipsis = $pages->last() < $last - 1;
                @endphp

                @if($pages->first() > 1)
                <a href="{{ $transactions->url(1) }}" class="page-btn ajax-nav">1</a>
                @endif
                @if($showLeadingEllipsis)
                <span class="page-btn disabled">&hellip;</span>
                @endif

                @foreach($pages as $p)
                @if($p === $current)
                <span class="page-btn active">{{ $p }}</span>
                @else
                <a href="{{ $transactions->url($p) }}" class="page-btn ajax-nav">{{ $p }}</a>
                @endif
                @endforeach

                @if($showTrailingEllipsis)
                <span class="page-btn disabled">&hellip;</span>
                @endif
                @if($pages->last() < $last)
                    <a href="{{ $transactions->url($last) }}" class="page-btn ajax-nav">{{ $last }}</a>
                    @endif

                    {{-- Next --}}
                    @if($transactions->hasMorePages())
                    <a href="{{ $transactions->nextPageUrl() }}" class="page-btn ajax-nav">&#8594;</a>
                    @else
                    <span class="page-btn disabled">&#8594;</span>
                    @endif
    </div>
</div>
@else
<div class="page-info" style="margin-top: 1rem;">
    Showing all {{ $transactions->total() }} transaction{{ $transactions->total() !== 1 ? 's' : '' }}
</div>
@endif
@endif
