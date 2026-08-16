<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $programs = $this->supabase->getAllPrograms();
        } catch (\RuntimeException $e) {
            $programs = [];
            session()->flash('error', 'Could not load programs: ' . $e->getMessage());
        }

        return view('admin.programs', [
            'programs' => $programs,
            'total'    => count($programs),
            'weekdays' => self::WEEKDAYS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        try {
            $this->supabase->insertProgram($validated);
            return back()->with('success', 'Program added.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not add program.');
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validated($request);

        try {
            $this->supabase->updateProgram($id, $validated);
            return back()->with('success', 'Program updated.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'Could not update program.');
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteProgram($id);
            return back()->with('success', 'Program deleted.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'ROW_IN_USE'
                ? 'This program has existing enrollments and cannot be deleted.'
                : 'Could not delete program.';
            return back()->with('error', $message);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'          => 'required|string|max:150',
            'weekdays'      => 'required|array|min:1',
            'weekdays.*'    => 'in:' . implode(',', self::WEEKDAYS),
            'num_sessions'  => 'required|integer|min:1',
            'program_price' => 'required|numeric|min:0',
            'description'   => 'nullable|string|max:1000',
        ]);
    }
}
