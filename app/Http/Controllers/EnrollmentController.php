<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use App\Support\PaymentStatus;
use App\Support\ProgramSchedule;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public const ENROLLMENT_TYPES = ['program', 'session'];

    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $enrollments = $this->supabase->getAllEnrollments();
        } catch (\RuntimeException $e) {
            $enrollments = [];
            session()->flash('error', 'Could not load enrollments: ' . $e->getMessage());
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
            $payments = $this->supabase->getAllPayments();
        } catch (\RuntimeException $e) {
            $payments = [];
        }

        $registrationsById = collect($registrations)->keyBy('id');
        $programsById      = collect($programs)->keyBy('id');
        $sessionsById      = collect($sessions)->keyBy('id');
        $paidByEnrollment  = collect($payments)
            ->groupBy('enrollment_id')
            ->map(fn($rows) => $rows->sum(fn($p) => (float) $p['amount']));

        $enrollments = collect($enrollments)->map(function ($enrollment) use ($registrationsById, $programsById, $sessionsById, $paidByEnrollment) {
            $enrollment['participant_name'] = $registrationsById->get($enrollment['registration_id'])['full_name'] ?? '—';
            $enrollment['target_name'] = $enrollment['enrollment_type'] === 'program'
                ? ($programsById->get($enrollment['program_id'])['name'] ?? '—')
                : ($sessionsById->get($enrollment['session_id'])['name'] ?? '—');
            $enrollment['amount_paid'] = $paidByEnrollment->get($enrollment['id'], 0.0);
            $enrollment['total'] = (float) $enrollment['price'] - (float) ($enrollment['discount_amount'] ?? 0);
            $enrollment['balance'] = $enrollment['total'] - $enrollment['amount_paid'];
            // Nothing owed is always "paid", regardless of the stored payment_status
            // (same rule the list view displays) — computed here so filtering and
            // display always agree on the same value.
            $enrollment['display_status'] = $enrollment['total'] <= 0 ? 'paid' : $enrollment['payment_status'];
            // Only program enrollments have an expiry_date at all (sessions
            // don't expire) — flags rows needing follow-up: the program's
            // over, and there's still a balance outstanding.
            $enrollment['is_expired_unpaid'] = $enrollment['enrollment_type'] === 'program'
                && !empty($enrollment['expiry_date'])
                && \Carbon\Carbon::parse($enrollment['expiry_date'])->isPast()
                && $enrollment['display_status'] !== 'paid';
            return $enrollment;
        })->values()->all();

        return view('admin.enrollments', [
            'enrollments'     => $enrollments,
            'total'           => count($enrollments),
            'registrations'   => $registrations,
            'programs'        => $programs,
            'sessions'        => $sessions,
            'enrollmentTypes' => self::ENROLLMENT_TYPES,
            'paymentMethods'  => ExpenseController::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'registration_id' => 'required|integer',
            'enrollment_type' => 'required|in:' . implode(',', self::ENROLLMENT_TYPES),
            'program_id'      => 'required_if:enrollment_type,program|nullable|integer',
            'session_id'      => 'required_if:enrollment_type,session|nullable|integer',
            // Required for both types now: for a program it's when this
            // participant starts; for a session it's the specific date they're
            // attending (admin picks/confirms it — the same Session can be
            // attended on more than one occasion).
            'start_date'      => 'required|date',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $price = null;
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $expiryDate = null;

        if ($validated['enrollment_type'] === 'program') {
            try {
                $program = $this->findProgram((int) $validated['program_id']);
            } catch (\RuntimeException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            // Price is snapshotted server-side from the program's current price,
            // never trusted from the client. expiry_date is computed once, here,
            // from the program's recurring weekday pattern — not recomputed on
            // every read.
            $price = (float) $program['program_price'];
            $expiryDate = ProgramSchedule::calculateExpiryDate(
                $startDate,
                $program['weekdays'] ?? [],
                (int) $program['num_sessions']
            );
        } else {
            try {
                $price = $this->currentSessionPrice((int) $validated['session_id']);
            } catch (\RuntimeException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        $discountAmount = (float) ($validated['discount_amount'] ?? 0);
        if ($discountAmount > $price) {
            return back()->withInput()->with('error', 'Discount amount cannot exceed the price.');
        }

        $payload = [
            'registration_id' => $validated['registration_id'],
            'enrollment_type' => $validated['enrollment_type'],
            'program_id'      => $validated['enrollment_type'] === 'program' ? $validated['program_id'] : null,
            'session_id'      => $validated['enrollment_type'] === 'session' ? $validated['session_id'] : null,
            'price'           => $price,
            'discount_amount' => $discountAmount,
            'start_date'      => $startDate->toDateString(),
            'expiry_date'     => $expiryDate?->toDateString(),
            // No payments recorded yet — a $0 total (fully discounted) is
            // already "paid" with nothing owed, per PaymentStatus::derive().
            'payment_status'  => PaymentStatus::derive(0, $price - $discountAmount),
        ];

        try {
            $this->supabase->insertEnrollment($payload);
            return back()->with('success', 'Enrollment added.');
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'DUPLICATE_NAME') {
                $message = $validated['enrollment_type'] === 'program'
                    ? 'This participant is already enrolled in this program with this start date.'
                    : 'This participant is already enrolled in this session on this date.';
                return back()->withInput()->with('error', $message);
            }
            return back()->withInput()->with('error', 'Could not add enrollment.');
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $enrollment = $this->supabase->getEnrollment($id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not load enrollment.');
        }

        if (!$enrollment) {
            return back()->with('error', 'Enrollment not found.');
        }

        // The "what" and "how much" of an enrollment are immutable snapshots —
        // enrollment_type, program_id/session_id and price can't be edited here
        // (that's effectively a different enrollment; delete and re-add instead).
        // Who's enrolled, their discount, and their date (program start date or
        // session attendance date) can change.
        $isProgram = $enrollment['enrollment_type'] === 'program';
        $validated = $request->validate([
            'registration_id' => 'required|integer',
            'start_date'      => 'required|date',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $price = (float) $enrollment['price'];
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);
        if ($discountAmount > $price) {
            return back()->withInput()->with('error', 'Discount amount cannot exceed the price.');
        }

        $startDate = \Carbon\Carbon::parse($validated['start_date']);

        $payload = [
            'registration_id' => $validated['registration_id'],
            'discount_amount' => $discountAmount,
            'start_date'      => $startDate->toDateString(),
        ];

        if ($isProgram) {
            try {
                $program = $this->findProgram((int) $enrollment['program_id']);
            } catch (\RuntimeException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            $expiryDate = ProgramSchedule::calculateExpiryDate(
                $startDate,
                $program['weekdays'] ?? [],
                (int) $program['num_sessions']
            );
            $payload['expiry_date'] = $expiryDate->toDateString();
        }

        try {
            $existingPayments = $this->supabase->getPaymentsForEnrollment($id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not verify existing payments.');
        }

        $alreadyPaid = collect($existingPayments)->sum(fn($p) => (float) $p['amount']);
        $payload['payment_status'] = PaymentStatus::derive($alreadyPaid, $price - $discountAmount);

        try {
            $this->supabase->updateEnrollment($id, $payload);
            return back()->with('success', 'Enrollment updated.');
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'DUPLICATE_NAME') {
                $message = $isProgram
                    ? 'This participant is already enrolled in this program with this start date.'
                    : 'This participant is already enrolled in this session on this date.';
                return back()->withInput()->with('error', $message);
            }
            return back()->withInput()->with('error', 'Could not update enrollment.');
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteEnrollment($id);
            return back()->with('success', 'Enrollment deleted.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'ROW_IN_USE'
                ? 'This enrollment has recorded payments and cannot be deleted.'
                : 'Could not delete enrollment.';
            return back()->with('error', $message);
        }
    }

    private function findProgram(int $programId): array
    {
        $program = collect($this->supabase->getAllPrograms())->firstWhere('id', $programId);
        if (!$program) {
            throw new \RuntimeException('Selected program was not found.');
        }
        return $program;
    }

    private function currentSessionPrice(int $sessionId): float
    {
        $session = collect($this->supabase->getAllSessions())->firstWhere('id', $sessionId);
        if (!$session) {
            throw new \RuntimeException('Selected session was not found.');
        }
        return (float) $session['price'];
    }
}
