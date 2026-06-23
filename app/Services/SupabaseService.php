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
}
