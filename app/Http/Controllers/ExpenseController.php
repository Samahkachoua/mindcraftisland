<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public const PAYMENT_METHODS = ['Cash', 'Whish', 'Other'];

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

        $categoriesById = collect($categories)->keyBy('id');
        $vendorsById    = collect($vendors)->keyBy('id');

        $collection = collect($expenses)->map(function ($expense) use ($categoriesById, $vendorsById) {
            $expense['category_name'] = $categoriesById->get($expense['category_id'])['name'] ?? '—';
            $expense['vendor_name']   = $vendorsById->get($expense['vendor_id'])['name'] ?? '—';
            return $expense;
        });

        $total = $collection->count();

        // Filters
        $categoryId = $request->input('category_id', '');
        if ($categoryId !== '') {
            $collection = $collection->filter(fn($e) => (string) ($e['category_id'] ?? '') === (string) $categoryId);
        }

        $vendorId = $request->input('vendor_id', '');
        if ($vendorId !== '') {
            $collection = $collection->filter(fn($e) => (string) ($e['vendor_id'] ?? '') === (string) $vendorId);
        }

        $paymentMethod = $request->input('payment_method', '');
        if (in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            $collection = $collection->filter(fn($e) => ($e['payment_method'] ?? '') === $paymentMethod);
        }

        $dateFrom = $request->input('date_from', '');
        if ($dateFrom !== '') {
            $collection = $collection->filter(fn($e) => ($e['expense_date'] ?? '') >= $dateFrom);
        }

        $dateTo = $request->input('date_to', '');
        if ($dateTo !== '') {
            $collection = $collection->filter(fn($e) => ($e['expense_date'] ?? '') <= $dateTo);
        }

        $search = trim($request->input('search', ''));
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
            'paymentMethods' => self::PAYMENT_METHODS,
        ];

        if ($request->ajax()) {
            return view('admin.partials.expenses-results', $viewData);
        }

        return view('admin.expenses', $viewData);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        try {
            $this->supabase->insertExpense($validated);
            return back()->with('success', 'Expense added.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not add expense.');
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validated($request);

        try {
            $this->supabase->updateExpense($id, $validated);
            return back()->with('success', 'Expense updated.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not update expense.');
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteExpense($id);
            return back()->with('success', 'Expense deleted.');
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not delete expense.');
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id'    => 'required|integer',
            'vendor_id'      => 'required|integer',
            'expense_date'   => 'required|date',
            'amount'         => 'required|numeric|min:0',
            'payment_method' => 'required|in:' . implode(',', self::PAYMENT_METHODS),
            'description'    => 'nullable|string|max:500',
        ]);
    }
}
