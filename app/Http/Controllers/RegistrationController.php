<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function show()
    {
        $this->applyLocale();
        return view('register');
    }

    public function switchLang(string $locale)
    {
        if (in_array($locale, ['en', 'ar'])) {
            session(['register_locale' => $locale]);
        }
        return redirect()->route('register');
    }

    public function store(Request $request)
    {
        $this->applyLocale();

        $validated = $request->validate([
            'full_name'          => 'required|string|max:255',
            'phone_number'       => 'required|numeric|digits_between:7,15',
            'father_name'        => 'required|string|max:255',
            'mother_name'        => 'required|string|max:255',
            'medical_issues'     => 'nullable|string|max:350',
            'field_of_interests' => 'nullable|string|max:350',
            'date_of_birth'      => 'required|date|before:today',
        ]);

        try {
            $this->supabase->insertRegistration($validated);
            return redirect()->route('register')->with('success', __('register.success'));
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'DUPLICATE_REGISTRATION') {
                return back()->withInput()->with('error', __('register.error_duplicate'));
            }
            Log::error('Registration failed', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', __('register.error_generic'));
        }
    }

    private function applyLocale(): void
    {
        $locale = session('register_locale', 'en');
        app()->setLocale($locale);
    }
}
