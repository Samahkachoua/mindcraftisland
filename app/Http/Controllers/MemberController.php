<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $members = $this->supabase->getAllMembers();
        } catch (\RuntimeException $e) {
            $members = [];
            session()->flash('error', 'Could not load members: ' . $e->getMessage());
        }

        try {
            $expenses = $this->supabase->getAllExpenses();
        } catch (\RuntimeException $e) {
            $expenses = [];
        }

        $usedMemberIds = collect($expenses)->pluck('funding_member_id')->filter()->unique()->all();

        $members = collect($members)->map(function ($member) use ($usedMemberIds) {
            $member['in_use'] = in_array($member['id'], $usedMemberIds, true);
            return $member;
        })->all();

        return view('admin.members', [
            'members' => $members,
            'total'   => count($members),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150', $this->uniqueNameRule()],
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->supabase->insertMember($validated);
            return back()->with('success', 'Member added.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'A member with this name already exists.'
                : 'Could not add member.';
            return back()->withInput()->with('error', $message);
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:150', $this->uniqueNameRule($id)],
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->supabase->updateMember($id, $validated);
            return back()->with('success', 'Member updated.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'A member with this name already exists.'
                : 'Could not update member.';
            return back()->withInput()->with('error', $message);
        }
    }

    private function uniqueNameRule(?int $excludeId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($excludeId) {
            try {
                $members = $this->supabase->getAllMembers();
            } catch (\RuntimeException $e) {
                return;
            }

            $duplicate = collect($members)->contains(
                fn($member) => (int) $member['id'] !== $excludeId
                    && mb_strtolower(trim($member['name'])) === mb_strtolower(trim($value))
            );

            if ($duplicate) {
                $fail('A member with this name already exists.');
            }
        };
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteMember($id);
            return back()->with('success', 'Member deleted.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'ROW_IN_USE'
                ? 'This member is used by existing expenses and cannot be deleted.'
                : 'Could not delete member.';
            return back()->with('error', $message);
        }
    }
}
