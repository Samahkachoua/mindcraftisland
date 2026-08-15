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
