<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $sessions = $this->supabase->getAllSessions();
        } catch (\RuntimeException $e) {
            $sessions = [];
            session()->flash('error', 'Could not load sessions: ' . $e->getMessage());
        }

        return view('admin.sessions', [
            'sessions' => $sessions,
            'total'    => count($sessions),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        try {
            $this->supabase->insertSession($validated);
            return back()->with('success', 'Session added.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not add session.');
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validated($request);

        try {
            $this->supabase->updateSession($id, $validated);
            return back()->with('success', 'Session updated.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not update session.');
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteSession($id);
            return back()->with('success', 'Session deleted.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'ROW_IN_USE'
                ? 'This session has existing enrollments and cannot be deleted.'
                : 'Could not delete session.';
            return back()->with('error', $message);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'          => 'required|string|max:150',
            'price'         => 'required|numeric|min:0',
            'description'   => 'nullable|string|max:1000',
        ]);
    }
}
