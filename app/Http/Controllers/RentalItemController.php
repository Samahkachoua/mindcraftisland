<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class RentalItemController extends Controller
{
    public const STATUSES = ['available', 'rented', 'maintenance'];

    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $rentalItems = $this->supabase->getAllRentalItems();
        } catch (\RuntimeException $e) {
            $rentalItems = [];
            session()->flash('error', 'Could not load rental items: ' . $e->getMessage());
        }

        try {
            $rentals = $this->supabase->getAllRentals();
        } catch (\RuntimeException $e) {
            $rentals = [];
        }

        $usedRentalItemIds = collect($rentals)->pluck('rental_item_id')->filter()->unique()->all();

        $rentalItems = collect($rentalItems)->map(function ($item) use ($usedRentalItemIds) {
            $item['in_use'] = in_array($item['id'], $usedRentalItemIds, true);
            return $item;
        })->all();

        return view('admin.rental-items', [
            'rentalItems' => $rentalItems,
            'total'       => count($rentalItems),
            'statuses'    => self::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        try {
            $this->supabase->insertRentalItem($validated);
            return back()->with('success', 'Rental item added.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'A rental item with this English name already exists.'
                : 'Could not add rental item.';
            return back()->withInput()->with('error', $message);
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validated($request, $id);

        try {
            $this->supabase->updateRentalItem($id, $validated);
            return back()->with('success', 'Rental item updated.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'A rental item with this English name already exists.'
                : 'Could not update rental item.';
            return back()->withInput()->with('error', $message);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteRentalItem($id);
            return back()->with('success', 'Rental item deleted.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'ROW_IN_USE'
                ? 'This rental item has rental history and cannot be deleted.'
                : 'Could not delete rental item.';
            return back()->with('error', $message);
        }
    }

    private function validated(Request $request, ?int $excludeId = null): array
    {
        return $request->validate([
            'name_en'         => ['required', 'string', 'max:150', $this->uniqueNameRule($excludeId)],
            'name_ar'         => 'required|string|max:150',
            'rate'            => 'required|numeric|min:0',
            'deposit_amount'  => 'nullable|numeric|min:0',
            'status'          => 'required|in:' . implode(',', self::STATUSES),
        ]);
    }

    private function uniqueNameRule(?int $excludeId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($excludeId) {
            try {
                $rentalItems = $this->supabase->getAllRentalItems();
            } catch (\RuntimeException $e) {
                return;
            }

            $duplicate = collect($rentalItems)->contains(
                fn($item) => (int) $item['id'] !== $excludeId
                    && mb_strtolower(trim($item['name_en'])) === mb_strtolower(trim($value))
            );

            if ($duplicate) {
                $fail('A rental item with this English name already exists.');
            }
        };
    }
}
