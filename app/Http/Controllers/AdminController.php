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

    public function dashboard(Request $request)
    {
        try {
            $registrations = $this->supabase->getAllRegistrations();
        } catch (\RuntimeException $e) {
            $registrations = [];
            session()->flash('error', 'Could not load registrations: ' . $e->getMessage());
        }

        $collection = collect($registrations);
        $total      = $collection->count();

        $todayCount = $collection->filter(fn($r) => isset($r['created_at']) && \Carbon\Carbon::parse($r['created_at'])->isToday())->count();
        $weekCount  = $collection->filter(fn($r) => isset($r['created_at']) && \Carbon\Carbon::parse($r['created_at'])->isCurrentWeek())->count();

        // Search
        $search = trim($request->input('search', ''));
        if ($search !== '') {
            $lower = mb_strtolower($search);
            $collection = $collection->filter(
                fn($r) =>
                str_contains(mb_strtolower($r['full_name'] ?? ''), $lower) ||
                    str_contains(mb_strtolower($r['phone_number'] ?? ''), $lower) ||
                    str_contains(mb_strtolower($r['emergency_contact_number'] ?? ''), $lower) ||
                    str_contains(mb_strtolower($r['mother_name'] ?? ''), $lower) ||
                    str_contains(mb_strtolower($r['medical_conditions'] ?? ''), $lower) ||
                    str_contains(mb_strtolower($r['field_of_interests'] ?? ''), $lower)
            );
        }

        // Sort
        $allowedSorts = ['full_name', 'date_of_birth', 'created_at'];
        $sort      = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $collection = $direction === 'asc'
            ? $collection->sortBy(fn($r) => $r[$sort] ?? '')
            : $collection->sortByDesc(fn($r) => $r[$sort] ?? '');

        // Paginate
        $perPage  = 15;
        $page     = max(1, (int) $request->input('page', 1));
        $filtered = $collection->count();
        $items    = $collection->forPage($page, $perPage)->values();

        $paginator = (new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $filtered,
            $perPage,
            $page,
            ['path' => $request->url()]
        ))->appends($request->except('page'));

        return view('admin.dashboard', [
            'registrations' => $paginator,
            'total'         => $total,
            'todayCount'    => $todayCount,
            'weekCount'     => $weekCount,
            'search'        => $search,
            'sort'          => $sort,
            'direction'     => $direction,
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
