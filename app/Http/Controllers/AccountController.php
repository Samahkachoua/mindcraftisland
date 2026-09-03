<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public const TYPES = ['cash', 'bank', 'mobile_wallet'];

    public const TYPE_LABELS = [
        'cash'          => 'Cash',
        'bank'          => 'Bank',
        'mobile_wallet' => 'Mobile Wallet',
    ];

    public const DIRECTIONS = ['in', 'out'];

    public const CATEGORIES = ['payment_received', 'expense', 'transfer', 'adjustment'];

    public function __construct(private SupabaseService $supabase) {}

    // Shared by every account picker (Payments, Expenses, Rentals) so an
    // account is always distinguishable by type, e.g. "Cash — Petty Cash",
    // not just its bare name.
    public static function optionLabel(array $account): string
    {
        $type = self::TYPE_LABELS[$account['type']] ?? ucfirst($account['type']);
        return $type . ' — ' . $account['name_en'];
    }

    public function index(Request $request)
    {
        try {
            $accounts = $this->supabase->getAllAccounts();
        } catch (\RuntimeException $e) {
            $accounts = [];
            session()->flash('error', 'Could not load accounts: ' . $e->getMessage());
        }

        $accounts = collect($accounts)->map(function ($account) {
            $account['type_label'] = self::TYPE_LABELS[$account['type']] ?? ucfirst($account['type']);
            return $account;
        })->all();

        return view('admin.accounts', [
            'accounts' => $accounts,
            'total'    => count($accounts),
            'types'    => self::TYPES,
        ]);
    }

    // Read-only ledger for one account: current balance (from the existing
    // account_balances view, never recomputed here) plus every Transaction
    // with a running balance. The running balance is always computed over
    // the account's complete history before any filter is applied, so a
    // filtered row's number still reflects the account's real balance at
    // that point in time — filters only decide which rows are displayed.
    public function show(Request $request, int $id)
    {
        try {
            $account = $this->supabase->getAccount($id);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.accounts')->with('error', 'Could not load account.');
        }

        if (!$account) {
            return redirect()->route('admin.accounts')->with('error', 'Account was not found.');
        }

        try {
            $balanceRow = $this->supabase->getAccountBalanceRow($id);
        } catch (\RuntimeException $e) {
            $balanceRow = null;
        }

        try {
            $transactions = $this->supabase->getAllTransactionsForAccount($id);
        } catch (\RuntimeException $e) {
            $transactions = [];
            session()->flash('error', 'Could not load transactions: ' . $e->getMessage());
        }

        // Running balance, walked once over the full unfiltered history.
        $running = (float) $account['opening_balance'];
        foreach ($transactions as &$transaction) {
            $running += $transaction['direction'] === 'in' ? (float) $transaction['amount'] : -(float) $transaction['amount'];
            $transaction['running_balance'] = $running;
        }
        unset($transaction);

        $transactions = $this->attachReferenceLabels($transactions);

        // Filters — applied only to decide which already-computed rows display.
        // Read via `?? ''` rather than `input($key, '')`: the global
        // ConvertEmptyStringsToNull middleware turns a submitted blank field
        // into null before it reaches here, and input()'s default only
        // applies when the key is absent, not when it's present-but-null.
        // Left as `input($key, '')`, a blank field submitted alongside a real
        // filter (e.g. from the AJAX form's FormData, which includes every
        // field) turned into an active "=== null" filter no row could match,
        // silently zeroing the results.
        $dateFrom  = $request->input('date_from') ?? '';
        $dateTo    = $request->input('date_to') ?? '';
        $direction = $request->input('direction') ?? '';
        $category  = $request->input('category') ?? '';

        $displayed = collect($transactions)
            ->when($dateFrom !== '', fn($c) => $c->filter(fn($t) => $t['date'] >= $dateFrom))
            ->when($dateTo !== '', fn($c) => $c->filter(fn($t) => $t['date'] <= $dateTo))
            ->when(in_array($direction, self::DIRECTIONS, true), fn($c) => $c->filter(fn($t) => $t['direction'] === $direction))
            ->when(in_array($category, self::CATEGORIES, true), fn($c) => $c->filter(fn($t) => $t['category'] === $category))
            ->values();

        // Paginate — order stays oldest-to-newest within each page (per the
        // confirmed screen design: running balance reads top-to-bottom
        // naturally), pagination just chunks that same order.
        $perPage = 15;
        $page    = max(1, (int) $request->input('page', 1));
        $filteredCount = $displayed->count();
        $items = $displayed->forPage($page, $perPage)->values();

        $paginator = (new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $filteredCount,
            $perPage,
            $page,
            ['path' => $request->url()]
        ))->appends($request->except('page'));

        $viewData = [
            'account'      => $account,
            'balance'      => $balanceRow['balance'] ?? null,
            'transactions' => $paginator,
            'dateFrom'     => $dateFrom,
            'dateTo'       => $dateTo,
            'direction'    => $direction,
            'category'     => $category,
            'directions'   => self::DIRECTIONS,
            'categories'   => self::CATEGORIES,
        ];

        if ($request->ajax()) {
            return view('admin.partials.account-ledger-results', $viewData);
        }

        return view('admin.account-ledger', $viewData);
    }

    // Batches one lookup per referenced table across every transaction,
    // rather than querying per row.
    private function attachReferenceLabels(array $transactions): array
    {
        $paymentIds = collect($transactions)->where('reference_type', 'payment')->pluck('reference_id')->filter()->unique()->values()->all();
        $expenseIds = collect($transactions)->where('reference_type', 'expense')->pluck('reference_id')->filter()->unique()->values()->all();

        try {
            $paymentsById = collect($this->supabase->getPaymentsByIds($paymentIds))->keyBy('id');
        } catch (\RuntimeException $e) {
            $paymentsById = collect();
        }

        try {
            $expensesById = collect($this->supabase->getExpensesByIds($expenseIds))->keyBy('id');
        } catch (\RuntimeException $e) {
            $expensesById = collect();
        }

        try {
            $categoriesById = collect($this->supabase->getAllCategories())->keyBy('id');
        } catch (\RuntimeException $e) {
            $categoriesById = collect();
        }

        // Only payments with no enrollment_id can possibly be rental
        // payments — narrow the lookup to just those.
        $rentalPaymentIds = $paymentsById->filter(fn($p) => empty($p['enrollment_id']))->keys()->all();

        try {
            $rentalsByPaymentId = collect($this->supabase->getRentalsByPaymentIds($rentalPaymentIds))->keyBy('payment_id');
        } catch (\RuntimeException $e) {
            $rentalsByPaymentId = collect();
        }

        foreach ($transactions as &$transaction) {
            $transaction['reference_label'] = $this->referenceLabel($transaction, $paymentsById, $expensesById, $categoriesById, $rentalsByPaymentId);
        }
        unset($transaction);

        return $transactions;
    }

    private function referenceLabel(array $transaction, $paymentsById, $expensesById, $categoriesById, $rentalsByPaymentId): string
    {
        $refId = $transaction['reference_id'] ?? null;

        switch ($transaction['reference_type']) {
            case 'payment':
                $payment = $refId ? $paymentsById->get($refId) : null;
                if (!$payment) {
                    return $refId ? "Payment #{$refId}" : 'Payment';
                }
                if (!empty($payment['enrollment_id'])) {
                    return "Payment — Enrollment #{$payment['enrollment_id']}";
                }
                $rental = $rentalsByPaymentId->get($payment['id']);
                return $rental ? "Payment — Rental #{$rental['id']}" : 'Payment — Rental';

            case 'expense':
                $expense = $refId ? $expensesById->get($refId) : null;
                if (!$expense) {
                    return $refId ? "Expense #{$refId}" : 'Expense';
                }
                $description = trim($expense['description'] ?? '');
                if ($description !== '') {
                    return "Expense — {$description}";
                }
                $categoryName = $categoriesById->get($expense['category_id'])['name'] ?? null;
                return $categoryName ? "Expense — {$categoryName}" : "Expense #{$expense['id']}";

            case 'manual':
                return $refId ? "Manual — reversal of Transaction #{$refId}" : 'Manual';

            case 'transfer':
                return $refId ? "Transfer — linked to Transaction #{$refId}" : 'Transfer';

            default:
                $label = ucfirst($transaction['reference_type']);
                return $refId ? "{$label} #{$refId}" : $label;
        }
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        try {
            $this->supabase->insertAccount($validated);
            return back()->with('success', 'Account added.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'An account with this name already exists.'
                : 'Could not add account.';
            return back()->withInput()->with('error', $message);
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validated($request);

        try {
            $current = $this->supabase->getAccount($id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not load account.');
        }

        if (!$current) {
            return back()->withInput()->with('error', 'Account was not found.');
        }

        try {
            $linkedTransactions = $this->supabase->getTransactionsForAccount($id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not verify the ledger before updating.');
        }

        // Once an account has ledger history, its type and opening_balance
        // are locked — editing either would silently rewrite the derived
        // balance's history. name_en/name_ar/is_active stay editable.
        if (count($linkedTransactions) > 0) {
            $lockedFieldsChanged = $validated['type'] !== $current['type']
                || (float) $validated['opening_balance'] !== (float) $current['opening_balance'];

            if ($lockedFieldsChanged) {
                return back()->withInput()->with('error', 'This account has ledger history — its type and opening balance can no longer change.');
            }
        }

        try {
            $this->supabase->updateAccount($id, $validated);
            return back()->with('success', 'Account updated.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'An account with this name already exists.'
                : 'Could not update account.';
            return back()->withInput()->with('error', $message);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteAccount($id);
            return back()->with('success', 'Account deleted.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'ROW_IN_USE'
                ? 'This account has ledger history and cannot be deleted — deactivate it instead.'
                : 'Could not delete account.';
            return back()->with('error', $message);
        }
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name_en'         => 'required|string|max:150',
            'name_ar'         => 'required|string|max:150',
            'type'            => 'required|in:' . implode(',', self::TYPES),
            'opening_balance' => 'required|numeric',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
