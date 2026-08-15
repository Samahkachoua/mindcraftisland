<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index(Request $request)
    {
        try {
            $categories = $this->supabase->getAllCategories();
        } catch (\RuntimeException $e) {
            $categories = [];
            session()->flash('error', 'Could not load categories: ' . $e->getMessage());
        }

        return view('admin.categories', [
            'categories' => $categories,
            'total'      => count($categories),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule()],
        ]);

        try {
            $this->supabase->insertCategory($validated);
            return back()->with('success', 'Category added.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'A category with this name already exists.'
                : 'Could not add category.';
            return back()->withInput()->with('error', $message);
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule($id)],
        ]);

        try {
            $this->supabase->updateCategory($id, $validated);
            return back()->with('success', 'Category updated.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'DUPLICATE_NAME'
                ? 'A category with this name already exists.'
                : 'Could not update category.';
            return back()->withInput()->with('error', $message);
        }
    }

    private function uniqueNameRule(?int $excludeId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($excludeId) {
            try {
                $categories = $this->supabase->getAllCategories();
            } catch (\RuntimeException $e) {
                return;
            }

            $duplicate = collect($categories)->contains(
                fn($category) => (int) $category['id'] !== $excludeId
                    && mb_strtolower(trim($category['name'])) === mb_strtolower(trim($value))
            );

            if ($duplicate) {
                $fail('A category with this name already exists.');
            }
        };
    }

    public function destroy(int $id)
    {
        try {
            $this->supabase->deleteCategory($id);
            return back()->with('success', 'Category deleted.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage() === 'ROW_IN_USE'
                ? 'This category is used by existing expenses and cannot be deleted.'
                : 'Could not delete category.';
            return back()->with('error', $message);
        }
    }
}
