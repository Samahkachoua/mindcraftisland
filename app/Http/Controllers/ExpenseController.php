<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public const PAYMENT_METHODS = ['Cash', 'Whish', 'Other'];
    public const FUNDING_TYPES = ['account', 'member'];

    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $expenses = $this->supabase->getAllExpenses();
        } catch (\RuntimeException $e) {
            $expenses = [];
            session()->flash('error', 'Could not load expenses: ' . $e->getMessage());
        }

        try {
            $categories = $this->supabase->getAllCategories();
        } catch (\RuntimeException $e) {
            $categories = [];
        }

        try {
            $vendors = $this->supabase->getAllVendors();
        } catch (\RuntimeException $e) {
            $vendors = [];
        }

        try {
            $accounts = $this->supabase->getAllAccounts();
        } catch (\RuntimeException $e) {
            $accounts = [];
        }

        try {
            $members = $this->supabase->getAllMembers();
        } catch (\RuntimeException $e) {
            $members = [];
        }

        $categoriesById = collect($categories)->keyBy('id');
        $vendorsById    = collect($vendors)->keyBy('id');
        $accountsById   = collect($accounts)->keyBy('id');
        $membersById    = collect($members)->keyBy('id');
        $activeAccounts = collect($accounts)->where('is_active', true)
            ->map(fn($a) => $a + ['label' => AccountController::optionLabel($a)])
            ->values()->all();

        $collection = collect($expenses)->map(function ($expense) use ($categoriesById, $vendorsById, $accountsById, $membersById) {
            $expense['category_name'] = $categoriesById->get($expense['category_id'])['name'] ?? '—';
            $expense['vendor_name']   = $vendorsById->get($expense['vendor_id'])['name'] ?? '—';
            $expense['funding_label'] = $expense['funding_type'] === 'account'
                ? ($accountsById->get($expense['funding_account_id'] ?? null)['name_en'] ?? '—')
                : ($membersById->get($expense['funding_member_id'] ?? null)['name'] ?? '—');
            return $expense;
        });

        $total = $collection->count();

        // Filters
        // Every field is read via `?? ''` rather than `input($key, '')` — the
        // global ConvertEmptyStringsToNull middleware turns a submitted blank
        // field into null before it reaches here, and input()'s default only
        // applies when the key is absent, not when it's present-but-null. Left
        // as `input($key, '')`, a blank vendor/date field submitted alongside
        // a real category filter turned into an active "vendor_id === null"
        // filter that no real row could ever match, silently zeroing results.
        $categoryId = $request->input('category_id') ?? '';
        if ($categoryId !== '') {
            $collection = $collection->filter(fn($e) => (string) ($e['category_id'] ?? '') === (string) $categoryId);
        }

        $vendorId = $request->input('vendor_id') ?? '';
        if ($vendorId !== '') {
            $collection = $collection->filter(fn($e) => (string) ($e['vendor_id'] ?? '') === (string) $vendorId);
        }

        $paymentMethod = $request->input('payment_method') ?? '';
        if (in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            $collection = $collection->filter(fn($e) => ($e['payment_method'] ?? '') === $paymentMethod);
        }

        $dateFrom = $request->input('date_from') ?? '';
        if ($dateFrom !== '') {
            $collection = $collection->filter(fn($e) => ($e['expense_date'] ?? '') >= $dateFrom);
        }

        $dateTo = $request->input('date_to') ?? '';
        if ($dateTo !== '') {
            $collection = $collection->filter(fn($e) => ($e['expense_date'] ?? '') <= $dateTo);
        }

        $search = trim($request->input('search') ?? '');
        if ($search !== '') {
            $lower = mb_strtolower($search);
            $collection = $collection->filter(
                fn($e) =>
                str_contains(mb_strtolower($e['vendor_name'] ?? ''), $lower) ||
                    str_contains(mb_strtolower($e['category_name'] ?? ''), $lower) ||
                    str_contains(mb_strtolower($e['description'] ?? ''), $lower)
            );
        }

        // Sort
        $allowedSorts = ['expense_date', 'category_name', 'vendor_name', 'amount', 'payment_method'];
        $sort      = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'expense_date';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $collection = $direction === 'asc'
            ? $collection->sortBy(fn($e) => $e[$sort] ?? '')
            : $collection->sortByDesc(fn($e) => $e[$sort] ?? '');

        $filteredCount  = $collection->count();
        $filteredAmount = $collection->sum(fn($e) => (float) ($e['amount'] ?? 0));

        // Paginate
        $perPage = 15;
        $page    = max(1, (int) $request->input('page', 1));
        $items   = $collection->forPage($page, $perPage)->values();

        $paginator = (new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $filteredCount,
            $perPage,
            $page,
            ['path' => $request->url()]
        ))->appends($request->except('page'));

        $viewData = [
            'expenses'       => $paginator,
            'total'          => $total,
            'totalAmount'    => $filteredAmount,
            'search'         => $search,
            'sort'           => $sort,
            'direction'      => $direction,
            'categoryId'     => $categoryId,
            'vendorId'       => $vendorId,
            'paymentMethod'  => $paymentMethod,
            'dateFrom'       => $dateFrom,
            'dateTo'         => $dateTo,
            'categories'     => $categories,
            'vendors'        => $vendors,
            'accounts'       => $activeAccounts,
            'members'        => $members,
            'paymentMethods' => self::PAYMENT_METHODS,
            'fundingTypes'   => self::FUNDING_TYPES,
        ];

        if ($request->ajax()) {
            return view('admin.partials.expenses-results', $viewData);
        }

        return view('admin.expenses', $viewData);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['paid_amount'] = $validated['amount'];

        try {
            $expense = $this->supabase->insertExpense($validated);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not add expense.');
        }

        if ($validated['funding_type'] === 'account') {
            try {
                $this->createExpenseTransaction($expense['id'], $validated);
            } catch (\RuntimeException $e) {
                try {
                    $this->supabase->deleteExpense((int) $expense['id']);
                } catch (\RuntimeException $e2) {
                    // Fall through — the error message below still surfaces the
                    // ledger failure either way.
                }
                return back()->withInput()->with('error', 'Could not add expense: the ledger entry failed, so the expense was rolled back.');
            }
        }

        return back()->with('success', 'Expense added.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validated($request);

        try {
            $current = $this->supabase->getExpense($id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not load expense.');
        }

        if (!$current) {
            return back()->withInput()->with('error', 'Expense was not found.');
        }

        try {
            $linkedTransactions = $this->supabase->getTransactionsForReference('expense', $id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not verify the ledger before updating.');
        }

        // Account-funded expenses lock once their ledger entry exists (always
        // true immediately after creation — store() rolls back otherwise).
        // Member-funded expenses have no ledger entry to key off of, but lock
        // immediately too — there's no separate "recording" step any more,
        // so a member-funded expense is just as settled the instant it's
        // created. Only the description can still be corrected either way.
        $isLocked = count($linkedTransactions) > 0 || $current['funding_type'] === 'member';

        if ($isLocked) {
            $lockedFieldsChanged = (string) $validated['category_id'] !== (string) $current['category_id']
                || (string) $validated['vendor_id'] !== (string) $current['vendor_id']
                || $validated['expense_date'] !== $current['expense_date']
                || (float) $validated['amount'] !== (float) $current['amount']
                || $validated['payment_method'] !== $current['payment_method']
                || $validated['funding_type'] !== $current['funding_type']
                || (string) ($validated['funding_account_id'] ?? '') !== (string) ($current['funding_account_id'] ?? '')
                || (string) ($validated['funding_member_id'] ?? '') !== (string) ($current['funding_member_id'] ?? '');

            if ($lockedFieldsChanged) {
                return back()->withInput()->with('error', 'This expense is settled and can only have its description edited.');
            }

            try {
                $this->supabase->updateExpense($id, ['description' => $validated['description'] ?? null]);
                return back()->with('success', 'Expense updated.');
            } catch (\RuntimeException $e) {
                return back()->withInput()->with('error', 'Could not update expense.');
            }
        }

        $validated['paid_amount'] = $validated['amount'];

        try {
            $this->supabase->updateExpense($id, $validated);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not update expense.');
        }

        if ($validated['funding_type'] === 'account') {
            try {
                $this->createExpenseTransaction($id, $validated);
            } catch (\RuntimeException $e) {
                return back()->with('error', 'Expense was updated, but creating its ledger entry failed. Please check the Accounts ledger and fix this manually.');
            }
        }

        return back()->with('success', 'Expense updated.');
    }

    public function destroy(int $id)
    {
        try {
            $linkedTransactions = $this->supabase->getTransactionsForReference('expense', $id);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not verify the ledger before deleting.');
        }

        if (count($linkedTransactions) > 0) {
            return back()->with('error', 'This expense has a ledger entry and cannot be deleted. Post a reversing transaction instead.');
        }

        try {
            $this->supabase->deleteExpense($id);
            return back()->with('success', 'Expense deleted.');
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not delete expense.');
        }
    }

    private function createExpenseTransaction(int $expenseId, array $validated): void
    {
        $this->supabase->insertTransaction([
            'account_id'     => (int) $validated['funding_account_id'],
            'direction'      => 'out',
            'amount'         => $validated['paid_amount'],
            'date'           => $validated['expense_date'],
            'category'       => 'expense',
            'reference_type' => 'expense',
            'reference_id'   => $expenseId,
            'note'           => $validated['description'] ?? null,
            'created_by'     => config('admin.username'),
        ]);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'category_id'        => 'required|integer',
            'vendor_id'          => 'required|integer',
            'expense_date'       => 'required|date',
            'amount'             => 'required|numeric|min:0',
            'payment_method'     => 'required|in:' . implode(',', self::PAYMENT_METHODS),
            'funding_type'       => 'required|in:' . implode(',', self::FUNDING_TYPES),
            'funding_account_id' => 'nullable|integer|required_if:funding_type,account',
            'funding_member_id'  => 'nullable|integer|required_if:funding_type,member',
            'description'        => 'nullable|string|max:500',
        ]);

        // Enforce the DB's funding-matches-type rule at the form layer too,
        // in case a stale value rides along on a funding_type switch (e.g. a
        // leftover selection from before toggling Account <-> Member).
        $validated['funding_account_id'] = $validated['funding_type'] === 'account'
            ? (int) $validated['funding_account_id']
            : null;
        $validated['funding_member_id'] = $validated['funding_type'] === 'member'
            ? (int) $validated['funding_member_id']
            : null;

        return $validated;
    }
}
