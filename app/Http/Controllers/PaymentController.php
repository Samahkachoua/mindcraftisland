<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use App\Support\PaymentStatus;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $payments = $this->supabase->getAllPayments();
        } catch (\RuntimeException $e) {
            $payments = [];
            session()->flash('error', 'Could not load payments: ' . $e->getMessage());
        }

        try {
            $enrollments = $this->supabase->getAllEnrollments();
        } catch (\RuntimeException $e) {
            $enrollments = [];
        }

        try {
            $registrations = $this->supabase->getAllRegistrations();
        } catch (\RuntimeException $e) {
            $registrations = [];
        }

        try {
            $programs = $this->supabase->getAllPrograms();
        } catch (\RuntimeException $e) {
            $programs = [];
        }

        try {
            $sessions = $this->supabase->getAllSessions();
        } catch (\RuntimeException $e) {
            $sessions = [];
        }

        try {
            $accounts = $this->supabase->getAllAccounts();
        } catch (\RuntimeException $e) {
            $accounts = [];
        }

        $registrationsById = collect($registrations)->keyBy('id');
        $programsById       = collect($programs)->keyBy('id');
        $sessionsById       = collect($sessions)->keyBy('id');
        $enrollmentsById    = collect($enrollments)->keyBy('id');
        $accountsById       = collect($accounts)->keyBy('id');
        $activeAccounts     = collect($accounts)->where('is_active', true)
            ->map(fn($a) => $a + ['label' => AccountController::optionLabel($a)])
            ->values()->all();

        $targetName = function (array $enrollment) use ($programsById, $sessionsById) {
            return $enrollment['enrollment_type'] === 'program'
                ? ($programsById->get($enrollment['program_id'])['name'] ?? '—')
                : ($sessionsById->get($enrollment['session_id'])['name'] ?? '—');
        };

        // Running balance per enrollment, shown in the "Record Payment" dropdown
        // so staff can see what's still owed before recording a new payment.
        $paidByEnrollment = collect($payments)
            ->groupBy('enrollment_id')
            ->map(fn($rows) => $rows->sum(fn($p) => (float) $p['amount']));

        // Only enrollments that still owe something are worth offering here —
        // nothing to record against one that's already fully paid (or free).
        $enrollmentOptions = collect($enrollments)->map(function ($enrollment) use ($registrationsById, $targetName, $paidByEnrollment) {
            $enrollment['participant_name'] = $registrationsById->get($enrollment['registration_id'])['full_name'] ?? '—';
            $enrollment['target_name'] = $targetName($enrollment);
            $netPrice = (float) $enrollment['price'] - (float) ($enrollment['discount_amount'] ?? 0);
            $enrollment['net_price'] = $netPrice;
            $enrollment['balance'] = $netPrice - $paidByEnrollment->get($enrollment['id'], 0.0);
            return $enrollment;
        })->filter(fn($enrollment) => $enrollment['balance'] > 0)->values()->all();

        $payments = collect($payments)->map(function ($payment) use ($enrollmentsById, $registrationsById, $targetName, $accountsById) {
            $enrollment = $enrollmentsById->get($payment['enrollment_id']);
            $payment['registration_id']  = $enrollment['registration_id'] ?? null;
            $payment['participant_name'] = $enrollment ? ($registrationsById->get($enrollment['registration_id'])['full_name'] ?? '—') : '—';
            $payment['enrollment_type']  = $enrollment['enrollment_type'] ?? null;
            $payment['program_id']       = $enrollment['program_id'] ?? null;
            $payment['session_id']       = $enrollment['session_id'] ?? null;
            $payment['target_name']      = $enrollment ? $targetName($enrollment) : '—';
            $payment['account_name']     = $accountsById->get($payment['account_id'] ?? null)['name_en'] ?? '—';
            return $payment;
        })->values()->all();

        return view('admin.payments', [
            'payments'              => $payments,
            'total'                 => count($payments),
            'enrollmentOptions'     => $enrollmentOptions,
            'registrations'         => $registrations,
            'programs'              => $programs,
            'sessions'              => $sessions,
            'accounts'              => $activeAccounts,
            // Every account, not just active ones — a filter needs to be able
            // to find payments against an account that's since been deactivated.
            'allAccounts'           => collect($accounts)->values()->all(),
            'paymentMethods'        => ExpenseController::PAYMENT_METHODS,
            'preselectEnrollmentId' => $request->query('enrollment_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id'  => 'required|integer',
            'account_id'     => 'required|integer',
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|in:' . implode(',', ExpenseController::PAYMENT_METHODS),
            'notes'          => 'nullable|string|max:500',
        ]);

        try {
            $enrollment = $this->supabase->getEnrollment((int) $validated['enrollment_id']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not load enrollment.');
        }

        if (!$enrollment) {
            return back()->withInput()->with('error', 'Selected enrollment was not found.');
        }

        try {
            $existingPayments = $this->supabase->getPaymentsForEnrollment((int) $enrollment['id']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not verify the remaining balance.');
        }

        $owed = (float) $enrollment['price'] - (float) ($enrollment['discount_amount'] ?? 0);
        $alreadyPaid = collect($existingPayments)->sum(fn($p) => (float) $p['amount']);
        $remaining = $owed - $alreadyPaid;

        if ($validated['amount'] > $remaining) {
            return back()->withInput()->with(
                'error',
                'Payment amount cannot exceed the remaining balance of ' . number_format(max($remaining, 0), 2) . '.'
            );
        }

        $payload = $validated;
        // There's no admin/staff accounts table in this app (admin login is a
        // single shared config-based credential) — record who by that account name.
        $payload['created_by'] = config('admin.username');

        try {
            $payment = $this->supabase->insertPayment($payload);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not record payment.');
        }

        // Every Payment must have a matching ledger Transaction — if this fails,
        // undo the just-inserted Payment (it has no Transaction yet, so it's
        // still safe to delete) rather than leave money "paid" with no ledger
        // entry behind it.
        try {
            $this->supabase->insertTransaction([
                'account_id'      => (int) $validated['account_id'],
                'direction'       => 'in',
                'amount'          => $validated['amount'],
                'date'            => $validated['payment_date'],
                'category'        => 'payment_received',
                'reference_type'  => 'payment',
                'reference_id'    => (int) $payment['id'],
                'note'            => $validated['notes'] ?? null,
                'created_by'      => $payload['created_by'],
            ]);
        } catch (\RuntimeException $e) {
            try {
                $this->supabase->deletePayment((int) $payment['id']);
            } catch (\RuntimeException $e2) {
                // Fall through — the error message below still surfaces the
                // ledger failure either way.
            }
            return back()->withInput()->with('error', 'Could not record payment: the ledger entry failed, so the payment was rolled back.');
        }

        try {
            $this->recomputeEnrollmentStatus($enrollment);
        } catch (\RuntimeException $e) {
            // Payment was recorded successfully; a stale status will self-correct
            // on the next payment write rather than hiding that success.
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(int $id)
    {
        try {
            $payment = $this->supabase->getPayment($id);
        } catch (\RuntimeException $e) {
            $payment = null;
        }

        try {
            $linkedTransactions = $this->supabase->getTransactionsForReference('payment', $id);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not verify the ledger before deleting.');
        }

        if (count($linkedTransactions) > 0) {
            return back()->with('error', 'This payment has a ledger entry and cannot be deleted. Post a reversing transaction instead.');
        }

        try {
            $this->supabase->deletePayment($id);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Could not delete payment.');
        }

        if ($payment) {
            try {
                $enrollment = $this->supabase->getEnrollment((int) $payment['enrollment_id']);
                if ($enrollment) {
                    $this->recomputeEnrollmentStatus($enrollment);
                }
            } catch (\RuntimeException $e) {
                // Deletion already succeeded; a stale status will self-correct
                // on the next payment write rather than hiding that success.
            }
        }

        return back()->with('success', 'Payment deleted.');
    }

    private function recomputeEnrollmentStatus(array $enrollment): void
    {
        $payments = $this->supabase->getPaymentsForEnrollment((int) $enrollment['id']);
        $paid = collect($payments)->sum(fn($p) => (float) $p['amount']);
        $owed = (float) $enrollment['price'] - (float) ($enrollment['discount_amount'] ?? 0);

        $status = PaymentStatus::derive($paid, $owed);

        $this->supabase->updateEnrollment((int) $enrollment['id'], ['payment_status' => $status]);
    }
}
