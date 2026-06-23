<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function loginForm()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $validUsername = config('admin.username');
        $validPassword = config('admin.password');

        if ($request->username === $validUsername && $request->password === $validPassword) {
            $request->session()->put('admin_logged_in', true);
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()->with('error', 'Invalid credentials.');
    }

    public function dashboard()
    {
        try {
            $registrations = $this->supabase->getAllRegistrations();
        } catch (\RuntimeException $e) {
            $registrations = [];
            session()->flash('error', 'Could not load registrations: ' . $e->getMessage());
        }

        return view('admin.dashboard', compact('registrations'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
