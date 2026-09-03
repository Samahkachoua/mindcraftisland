<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RentalController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $rentals = $this->supabase->getAllRentals();
        } catch (\RuntimeException $e) {
            $rentals = [];
            session()->flash('error', 'Could not load rentals: ' . $e->getMessage());
        }

        try {
            $rentalItems = $this->supabase->getAllRentalItems();
        } catch (\RuntimeException $e) {
            $rentalItems = [];
        }

        try {
            $accounts = $this->supabase->getAllAccounts();
        } catch (\RuntimeException $e) {
            $accounts = [];
        }

        $rentalItemsById = collect($rentalItems)->keyBy('id');
        $availableItems  = collect($rentalItems)->where('status', 'available')->values()->all();
        $activeAccounts  = collect($accounts)->where('is_active', true)
            ->map(fn($a) => $a + ['label' => AccountController::optionLabel($a)])
            ->values()->all();
        $today           = Carbon::today()->toDateString();

        $rentals = collect($rentals)->map(function ($rental) use ($rentalItemsById, $today) {
            $rental['item_name'] = $rentalItemsById->get($rental['rental_item_id'])['name_en'] ?? '—';
            // 'overdue' is shown as a derived badge rather than a stored status —
            // there's no scheduled job in this app to flip status='out' rows over
            // automatically as their due date passes.
            $rental['display_status'] = ($rental['status'] === 'out' && $rental['date_due_back'] < $today)
                ? 'overdue'
                : $rental['status'];
            return $rental;
        })->sortByDesc('date_out')->values()->all();

        return view('admin.rentals', [
            'rentals'         => $rentals,
            'total'           => count($rentals),
            'rentalItems'     => $rentalItems,
            'availableItems'  => $availableItems,
            'accounts'        => $activeAccounts,
            'paymentMethods'  => ExpenseController::PAYMENT_METHODS,
        ]);
    }

    // "Check Out" — creates the Payment (+ its ledger Transaction) and the
    // Rental row together. Order matters: the Payment must exist before the
    // Rental can reference it (payment_id is required), so if the Rental
    // insert fails after the Payment/Transaction already succeeded, we
    // can't safely roll back — the Transaction is append-only by design —
    // so that failure surfaces as an error pointing at the orphaned payment
    // instead of attempting a destructive undo.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rental_item_id'   => 'required|integer',
            'renter_name'      => 'required|string|max:150',
            'renter_phone'     => 'required|string|max:50',
            'date_out'         => 'required|date',
            'date_due_back'    => 'required|date|after_or_equal:date_out',
            'rate_charged'     => 'required|numeric|min:0.01',
            'deposit_collected' => 'nullable|numeric|min:0',
            'account_id'       => 'required|integer',
            'payment_method'   => 'required|in:' . implode(',', ExpenseController::PAYMENT_METHODS),
            'notes'            => 'nullable|string|max:500',
        ]);

        try {
            $item = $this->supabase->getRentalItem((int) $validated['rental_item_id']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not load rental item.');
        }

        if (!$item) {
            return back()->withInput()->with('error', 'Rental item was not found.');
        }

        if ($item['status'] !== 'available') {
            return back()->withInput()->with('error', 'This rental item is not currently available.');
        }

        // Checked here, before any Payment/Transaction exists — not just left
        // to the DB's unique constraint on the final insertRental() call.
        // That constraint is still there as a race-condition backstop, but
        // catching it only there would mean a duplicate checkout attempt
        // already posted a real Payment + Transaction that then has to be
        // explained away as an orphan (see the insertRental catch below).
        try {
            $existingRentals = $this->supabase->getAllRentals();
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not verify existing rentals.');
        }

        $duplicate = collect($existingRentals)->contains(
            fn($r) => (int) $r['rental_item_id'] === (int) $validated['rental_item_id']
                && $r['date_out'] === $validated['date_out']
                && mb_strtolower(trim($r['renter_name'])) === mb_strtolower(trim($validated['renter_name']))
                && mb_strtolower(trim($r['renter_phone'])) === mb_strtolower(trim($validated['renter_phone']))
        );

        if ($duplicate) {
            return back()->withInput()->with('error', 'This exact rental (same item, renter, and date out) has already been recorded.');
        }

        $createdBy = config('admin.username');

        try {
            $payment = $this->supabase->insertPayment([
                'enrollment_id'  => null,
                'account_id'     => (int) $validated['account_id'],
                'amount'         => $validated['rate_charged'],
                'payment_date'   => $validated['date_out'],
                'payment_method' => $validated['payment_method'],
                'notes'          => 'Rental: ' . $item['name_en'] . ' — ' . $validated['renter_name'],
                'created_by'     => $createdBy,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not record the rental payment.');
        }

        try {
            $this->supabase->insertTransaction([
                'account_id'     => (int) $validated['account_id'],
                'direction'      => 'in',
                'amount'         => $validated['rate_charged'],
                'date'           => $validated['date_out'],
                'category'       => 'payment_received',
                'reference_type' => 'payment',
                'reference_id'   => (int) $payment['id'],
                'note'           => 'Rental: ' . $item['name_en'],
                'created_by'     => $createdBy,
            ]);
        } catch (\RuntimeException $e) {
            try {
                $this->supabase->deletePayment((int) $payment['id']);
            } catch (\RuntimeException $e2) {
                // Fall through — the error message below still surfaces the
                // ledger failure either way.
            }
            return back()->withInput()->with('error', 'Could not check out: the ledger entry failed, so the payment was rolled back.');
        }

        try {
            $rental = $this->supabase->insertRental([
                'rental_item_id'     => (int) $validated['rental_item_id'],
                'renter_name'        => $validated['renter_name'],
                'renter_phone'       => $validated['renter_phone'],
                'date_out'           => $validated['date_out'],
                'date_due_back'      => $validated['date_due_back'],
                'rate_charged'       => $validated['rate_charged'],
                'deposit_collected'  => $validated['deposit_collected'] ?? null,
                'status'             => 'out',
                'payment_id'         => (int) $payment['id'],
            ]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with(
                'error',
                'Payment #' . $payment['id'] . ' was recorded and posted to the ledger, but creating the rental record failed. Please check the Payments and Accounts pages, then contact support before retrying — do not record a second payment for this checkout.'
            );
        }

        try {
            $this->supabase->updateRentalItem((int) $item['id'], ['status' => 'rented']);
        } catch (\RuntimeException $e) {
            return back()->with('success', 'Rental checked out, but the item\'s status could not be updated — please set it to "rented" manually.');
        }

        return back()->with('success', 'Rental checked out.');
    }

    // "Return" — a status transition only. Deposit refunds and any other
    // money movement on return aren't wired to the ledger in this pass.
    public function returnRental(Request $request, int $id)
    {
        $validated = $request->validate([
            'date_returned' => 'required|date',
        ]);

        try {
            $rental = $this->supabase->getRental($id);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not load rental.');
        }

        if (!$rental) {
            return back()->with('error', 'Rental was not found.');
        }

        if ($rental['status'] === 'returned') {
            return back()->with('error', 'This rental was already marked returned.');
        }

        try {
            $this->supabase->updateRental($id, [
                'status'        => 'returned',
                'date_returned' => $validated['date_returned'],
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not process the return.');
        }

        try {
            $this->supabase->updateRentalItem((int) $rental['rental_item_id'], ['status' => 'available']);
        } catch (\RuntimeException $e) {
            return back()->with('success', 'Rental marked as returned, but the item\'s status could not be updated — please set it to "available" manually.');
        }

        return back()->with('success', 'Rental marked as returned.');
    }

    // Editing is deliberately narrow: renter contact info and the due-back
    // date only. rental_item_id, payment_id, rate_charged and
    // deposit_collected all describe the checkout that already posted a
    // Payment/Transaction — same immutability rule as Payments/Expenses
    // once money has moved, those facts can't be edited here.
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'edit_renter_name'   => 'required|string|max:150',
            'edit_renter_phone'  => 'required|string|max:50',
            'edit_date_due_back' => 'required|date',
        ]);

        try {
            $rental = $this->supabase->getRental($id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not load rental.');
        }

        if (!$rental) {
            return back()->withInput()->with('error', 'Rental was not found.');
        }

        if ($validated['edit_date_due_back'] < $rental['date_out']) {
            return back()->withInput()->with(
                'error',
                'Date due back cannot be before the date out (' . $rental['date_out'] . ').'
            );
        }

        try {
            $this->supabase->updateRental($id, [
                'renter_name'   => $validated['edit_renter_name'],
                'renter_phone'  => $validated['edit_renter_phone'],
                'date_due_back' => $validated['edit_date_due_back'],
            ]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not update rental.');
        }

        return back()->with('success', 'Rental updated.');
    }

    // Fully undoes a checkout: deletes the Transaction, then the Rental,
    // then the Payment, in that order (order matters — see
    // rentals_delete_migration.sql). This is a real hard delete, not a
    // reversal entry, and relies on a narrow, deliberate carve-out in the
    // ledger's normally-append-only Transactions table that permits this
    // specific case only.
    public function destroy(int $id)
    {
        try {
            $rental = $this->supabase->getRental($id);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not load rental.');
        }

        if (!$rental) {
            return back()->with('error', 'Rental was not found.');
        }

        try {
            $transactions = $this->supabase->getTransactionsForReference('payment', (int) $rental['payment_id']);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not verify the ledger before deleting.');
        }

        foreach ($transactions as $transaction) {
            try {
                $this->supabase->deleteTransaction((int) $transaction['id']);
            } catch (\RuntimeException $e) {
                return back()->with('error', 'Could not delete the linked ledger entry — nothing was deleted.');
            }
        }

        try {
            $this->supabase->deleteRental($id);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'The ledger entry was deleted, but the rental record could not be removed. Please check the Accounts ledger and contact support.');
        }

        try {
            $this->supabase->deletePayment((int) $rental['payment_id']);
        } catch (\RuntimeException $e) {
            return back()->with('success', 'Rental and its ledger entry were deleted, but the payment record could not be removed automatically — please delete it manually from the Payments page.');
        }

        try {
            $item = $this->supabase->getRentalItem((int) $rental['rental_item_id']);
            if ($item && $item['status'] === 'rented') {
                $this->supabase->updateRentalItem((int) $rental['rental_item_id'], ['status' => 'available']);
            }
        } catch (\RuntimeException $e) {
            // Non-critical — the rental is gone either way.
        }

        return back()->with('success', 'Rental deleted, along with its payment and ledger entry.');
    }
}
