<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $vendors = $this->supabase->getAllVendors();
        } catch (\RuntimeException $e) {
            $vendors = [];
            session()->flash('error', 'Could not load vendors: ' . $e->getMessage());
        }

        return view('admin.vendors', [
            'vendors' => $vendors,
            'total'   => count($vendors),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule()],
        ]);

        try {
            $this->supabase->insertVendor($validated);
            return back()->with('success', 'Vendor added.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'A vendor with this name already exists.'
                : 'Could not add vendor.';
            return back()->withInput()->with('error', $message);
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule($id)],
        ]);

        try {
            $this->supabase->updateVendor($id, $validated);
            return back()->with('success', 'Vendor updated.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'A vendor with this name already exists.'
                : 'Could not update vendor.';
            return back()->withInput()->with('error', $message);
        }
    }

    private function uniqueNameRule(?int $excludeId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($excludeId) {
            try {
                $vendors = $this->supabase->getAllVendors();
            } catch (\RuntimeException $e) {
                return;
            }

            $duplicate = collect($vendors)->contains(
                fn($vendor) => (int) $vendor['id'] !== $excludeId
                    && mb_strtolower(trim($vendor['name'])) === mb_strtolower(trim($value))
            );

            if ($duplicate) {
                $fail('A vendor with this name already exists.');
            }
        };
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteVendor($id);
            return back()->with('success', 'Vendor deleted.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'ROW_IN_USE'
                ? 'This vendor is used by existing expenses and cannot be deleted.'
                : 'Could not delete vendor.';
            return back()->with('error', $message);
        }
    }
}
