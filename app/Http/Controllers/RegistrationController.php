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
        return view('register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'    => 'required|string|max:255',
            'phone_number' => 'required|numeric|digits_between:7,15',
            'father_name'  => 'required|string|max:255',
            'mother_name'  => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
        ]);

        try {
            $this->supabase->insertRegistration($validated);
            return redirect()->route('register')->with('success', 'Registration submitted successfully! We\'ll be in touch.');
        } catch (\RuntimeException $e) {
            Log::error('Registration failed', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Could not save your registration. Please try again.');
        }
    }
}
