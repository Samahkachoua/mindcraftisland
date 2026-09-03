<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class SupabaseService
{
    private string $url;
    private string $anonKey;
    private string $serviceKey;

    public function __construct()
    {
        // Strip any trailing path (e.g. /rest/v1/) — we build paths ourselves
        $raw = rtrim(config('services.supabase.url'), '/');
        $parsed = parse_url($raw);
        $this->url = $parsed['scheme'] . '://' . $parsed['host'];
        $this->anonKey = config('services.supabase.anon_key');
        $this->serviceKey = config('services.supabase.service_key');
    }

    private function headers(bool $useServiceKey = false): array
    {
        $key = $useServiceKey ? $this->serviceKey : $this->anonKey;
        return [
            'apikey'        => $key,
            'Authorization' => 'Bearer ' . $key,
            'Content-Type'  => 'application/json',
            'Prefer'        => 'return=representation',
        ];
    }

    public function insertRegistration(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();

        $response = Http::withHeaders($this->headers(true))
            ->post("{$this->url}/rest/v1/registrations", $data);

        if ($response->failed()) {
            $body = $response->json() ?? [];
            // PostgreSQL unique-constraint violation (composite key: full_name + phone_number)
            if (($body['code'] ?? '') === '23505') {
                throw new \RuntimeException('DUPLICATE_REGISTRATION');
            }
            throw new \RuntimeException('Supabase insert failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    public function getAllRegistrations(): array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/registrations", [
                'order' => 'created_at.desc',
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Supabase fetch failed: ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    // ── Categories ──────────────────────────────────────────

    public function getAllCategories(): array
    {
        return $this->fetchAll('categories', 'name.asc');
    }

    public function insertCategory(array $data): array
    {
        return $this->insertRow('categories', $data);
    }

    public function updateCategory(int $id, array $data): array
    {
        return $this->updateRow('categories', $id, $data);
    }

    public function deleteCategory(int $id): void
    {
        $this->deleteRow('categories', $id);
    }

    // ── Members ─────────────────────────────────────────────

    public function getAllMembers(): array
    {
        return $this->fetchAll('members', 'name.asc');
    }

    public function insertMember(array $data): array
    {
        return $this->insertRow('members', $data);
    }

    public function updateMember(int $id, array $data): array
    {
        return $this->updateRow('members', $id, $data);
    }

    public function deleteMember(int $id): void
    {
        $this->deleteRow('members', $id);
    }


    // ── Vendors ─────────────────────────────────────────────

    public function getAllVendors(): array
    {
        return $this->fetchAll('vendors', 'name.asc');
    }

    public function insertVendor(array $data): array
    {
        return $this->insertRow('vendors', $data);
    }

    public function updateVendor(int $id, array $data): array
    {
        return $this->updateRow('vendors', $id, $data);
    }

    public function deleteVendor(int $id): void
    {
        $this->deleteRow('vendors', $id);
    }

    // ── Expenses ────────────────────────────────────────────

    public function getAllExpenses(): array
    {
        return $this->fetchAll('expenses', 'expense_date.desc');
    }

    public function getExpense(int $id): ?array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/expenses", [
                'id'     => "eq.{$id}",
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (expenses): ' . $response->body());
        }

        return ($response->json() ?? [])[0] ?? null;
    }

    public function insertExpense(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();
        $data['updated_at'] = $data['created_at'];

        return $this->insertRow('expenses', $data);
    }

    public function updateExpense(int $id, array $data): array
    {
        $data['updated_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->updateRow('expenses', $id, $data);
    }

    public function deleteExpense(int $id): void
    {
        $this->deleteRow('expenses', $id);
    }

    // ── Programs ────────────────────────────────────────────

    public function getAllPrograms(): array
    {
        return $this->fetchAll('programs', 'name.asc');
    }

    public function insertProgram(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();
        $data['updated_at'] = $data['created_at'];

        return $this->insertRow('programs', $data);
    }

    public function updateProgram(int $id, array $data): array
    {
        $data['updated_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->updateRow('programs', $id, $data);
    }

    public function deleteProgram(int $id): void
    {
        $this->deleteRow('programs', $id);
    }

    // ── Sessions ────────────────────────────────────────────

    public function getAllSessions(): array
    {
        return $this->fetchAll('sessions', 'name.asc');
    }

    public function insertSession(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();
        $data['updated_at'] = $data['created_at'];

        return $this->insertRow('sessions', $data);
    }

    public function updateSession(int $id, array $data): array
    {
        $data['updated_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->updateRow('sessions', $id, $data);
    }

    public function deleteSession(int $id): void
    {
        $this->deleteRow('sessions', $id);
    }

    // ── Enrollments ─────────────────────────────────────────

    public function getAllEnrollments(): array
    {
        return $this->fetchAll('enrollments', 'created_at.desc');
    }

    public function getEnrollment(int $id): ?array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/enrollments", [
                'id'     => "eq.{$id}",
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (enrollments): ' . $response->body());
        }

        return ($response->json() ?? [])[0] ?? null;
    }

    public function insertEnrollment(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->insertRow('enrollments', $data);
    }

    public function updateEnrollment(int $id, array $data): array
    {
        return $this->updateRow('enrollments', $id, $data);
    }

    public function deleteEnrollment(int $id): void
    {
        $this->deleteRow('enrollments', $id);
    }

    // ── Payments ────────────────────────────────────────────

    public function getAllPayments(): array
    {
        return $this->fetchAll('payments', 'payment_date.desc');
    }

    public function getPayment(int $id): ?array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/payments", [
                'id'     => "eq.{$id}",
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (payments): ' . $response->body());
        }

        return ($response->json() ?? [])[0] ?? null;
    }

    public function getPaymentsForEnrollment(int $enrollmentId): array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/payments", [
                'enrollment_id' => "eq.{$enrollmentId}",
                'select'        => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (payments): ' . $response->body());
        }

        return $response->json() ?? [];
    }

    public function insertPayment(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->insertRow('payments', $data);
    }

    public function deletePayment(int $id): void
    {
        $this->deleteRow('payments', $id);
    }

    // ── Accounts ────────────────────────────────────────────

    public function getAllAccounts(): array
    {
        return $this->fetchAll('accounts', 'name_en.asc');
    }

    public function getAccount(int $id): ?array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/accounts", [
                'id'     => "eq.{$id}",
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (accounts): ' . $response->body());
        }

        return ($response->json() ?? [])[0] ?? null;
    }

    public function insertAccount(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();
        $data['updated_at'] = $data['created_at'];

        return $this->insertRow('accounts', $data);
    }

    public function updateAccount(int $id, array $data): array
    {
        $data['updated_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->updateRow('accounts', $id, $data);
    }

    public function deleteAccount(int $id): void
    {
        $this->deleteRow('accounts', $id);
    }

    public function getTransactionsForAccount(int $accountId): array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/transactions", [
                'account_id' => "eq.{$accountId}",
                'select'     => 'id',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (transactions): ' . $response->body());
        }

        return $response->json() ?? [];
    }

    // Full rows, chronological (date then id, so same-day rows keep a
    // stable order) — the account ledger walks this once to compute each
    // row's running balance before any display filter is applied.
    public function getAllTransactionsForAccount(int $accountId): array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/transactions", [
                'account_id' => "eq.{$accountId}",
                'select'     => '*',
                'order'      => 'date.asc,id.asc',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (transactions): ' . $response->body());
        }

        return $response->json() ?? [];
    }

    // Reads the account_balances view built in the original ledger
    // migration — the ledger page's "current balance" always comes from
    // here, never a second calculation.
    public function getAccountBalanceRow(int $id): ?array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/account_balances", [
                'id'     => "eq.{$id}",
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (account_balances): ' . $response->body());
        }

        return ($response->json() ?? [])[0] ?? null;
    }

    public function getPaymentsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/payments", [
                'id'     => 'in.(' . implode(',', $ids) . ')',
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (payments): ' . $response->body());
        }

        return $response->json() ?? [];
    }

    public function getExpensesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/expenses", [
                'id'     => 'in.(' . implode(',', $ids) . ')',
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (expenses): ' . $response->body());
        }

        return $response->json() ?? [];
    }

    public function getRentalsByPaymentIds(array $paymentIds): array
    {
        if (empty($paymentIds)) {
            return [];
        }

        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/rentals", [
                'payment_id' => 'in.(' . implode(',', $paymentIds) . ')',
                'select'     => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (rentals): ' . $response->body());
        }

        return $response->json() ?? [];
    }

    // ── Transactions ────────────────────────────────────────

    public function insertTransaction(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->insertRow('transactions', $data);
    }

    public function getTransactionsForReference(string $referenceType, int $referenceId): array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/transactions", [
                'reference_type' => "eq.{$referenceType}",
                'reference_id'   => "eq.{$referenceId}",
                'select'         => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (transactions): ' . $response->body());
        }

        return $response->json() ?? [];
    }

    // Transactions are append-only everywhere except one narrow, deliberate
    // carve-out (see rentals_delete_migration.sql): a transaction whose
    // reference is a payment that a Rental still points to. The DB trigger
    // itself enforces that scope — this method has no special knowledge of
    // it and will fail the same way insertRow's callers do for anything the
    // trigger still refuses.
    public function deleteTransaction(int $id): void
    {
        $response = Http::withHeaders($this->headers(true))
            ->delete("{$this->url}/rest/v1/transactions?id=eq.{$id}");

        if ($response->failed()) {
            throw new \RuntimeException('Supabase delete failed (transactions): ' . $response->body());
        }
    }

    // ── Rental items ────────────────────────────────────────

    public function getAllRentalItems(): array
    {
        return $this->fetchAll('rental_items', 'name_en.asc');
    }

    public function getRentalItem(int $id): ?array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/rental_items", [
                'id'     => "eq.{$id}",
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (rental_items): ' . $response->body());
        }

        return ($response->json() ?? [])[0] ?? null;
    }

    public function insertRentalItem(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();
        $data['updated_at'] = $data['created_at'];

        return $this->insertRow('rental_items', $data);
    }

    public function updateRentalItem(int $id, array $data): array
    {
        $data['updated_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->updateRow('rental_items', $id, $data);
    }

    public function deleteRentalItem(int $id): void
    {
        $this->deleteRow('rental_items', $id);
    }

    // ── Rentals ─────────────────────────────────────────────

    public function getAllRentals(): array
    {
        return $this->fetchAll('rentals', 'date_out.desc');
    }

    public function getRental(int $id): ?array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/rentals", [
                'id'     => "eq.{$id}",
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase fetch failed (rentals): ' . $response->body());
        }

        return ($response->json() ?? [])[0] ?? null;
    }

    public function insertRental(array $data): array
    {
        $data['created_at'] = now('Asia/Beirut')->toIso8601String();
        $data['updated_at'] = $data['created_at'];

        return $this->insertRow('rentals', $data);
    }

    public function updateRental(int $id, array $data): array
    {
        $data['updated_at'] = now('Asia/Beirut')->toIso8601String();

        return $this->updateRow('rentals', $id, $data);
    }

    public function deleteRental(int $id): void
    {
        $this->deleteRow('rentals', $id);
    }

    // ── Generic REST helpers ────────────────────────────────

    private function fetchAll(string $table, string $order): array
    {
        $response = Http::withHeaders($this->headers(true))
            ->get("{$this->url}/rest/v1/{$table}", [
                'order' => $order,
                'select' => '*',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("Supabase fetch failed ({$table}): " . $response->body());
        }

        return $response->json() ?? [];
    }

    private function insertRow(string $table, array $data): array
    {
        $response = Http::withHeaders($this->headers(true))
            ->post("{$this->url}/rest/v1/{$table}", $data);

        if ($response->failed()) {
            $this->throwForWriteFailure($table, $response);
        }

        return ($response->json() ?? [])[0] ?? [];
    }

    private function updateRow(string $table, int $id, array $data): array
    {
        $response = Http::withHeaders($this->headers(true))
            ->patch("{$this->url}/rest/v1/{$table}?id=eq.{$id}", $data);

        if ($response->failed()) {
            $this->throwForWriteFailure($table, $response);
        }

        return ($response->json() ?? [])[0] ?? [];
    }

    private function deleteRow(string $table, int $id): void
    {
        $response = Http::withHeaders($this->headers(true))
            ->delete("{$this->url}/rest/v1/{$table}?id=eq.{$id}");

        if ($response->failed()) {
            $body = $response->json() ?? [];
            if (($body['code'] ?? '') === '23503') {
                throw new \RuntimeException('ROW_IN_USE');
            }
            throw new \RuntimeException("Supabase delete failed ({$table}): " . $response->body());
        }
    }

    private function throwForWriteFailure(string $table, $response): never
    {
        $body = $response->json() ?? [];
        if (($body['code'] ?? '') === '23505') {
            throw new \RuntimeException('DUPLICATE_NAME');
        }
        throw new \RuntimeException("Supabase write failed ({$table}): " . $response->body());
    }
}
