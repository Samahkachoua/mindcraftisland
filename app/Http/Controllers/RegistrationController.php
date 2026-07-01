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
            'full_name'                  => 'required|string|max:255',
            'phone_number'               => 'required|numeric|digits_between:7,15',
            'emergency_contact_number'   => 'required|numeric|digits_between:7,15',
            'mother_name'                => 'required|string|max:255',
            'medical_conditions'     => 'nullable|string|max:350',
            'field_of_interests'     => 'nullable|string|max:350',
            'photo_video_consent'    => 'accepted',
            'date_of_birth'      => [
                'required',
                'date',
                'after_or_equal:' . now()->subYears(18)->format('Y-m-d'),
                'before_or_equal:' . now()->subYears(8)->format('Y-m-d'),
            ],
        ], [
            'date_of_birth.after_or_equal'  => __('register.dob_too_old'),
            'date_of_birth.before_or_equal' => __('register.dob_too_young'),
        ]);

        $validated['photo_video_consent'] = 1;

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
