<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index(): View
    {
        $categories = Category::query()
            ->withCount([
                'tasks',
                'tasks as todo_count'  => fn ($q) => $q->where('status', TaskStatus::TODO->value),
                'tasks as wip_count'   => fn ($q) => $q->where('status', TaskStatus::WIP->value),
                'tasks as draft_count' => fn ($q) => $q->where('status', TaskStatus::FIRST_DRAFT->value),
                'tasks as done_count'  => fn ($q) => $q->where('status', TaskStatus::DONE->value),
                'tasks as hold_count'  => fn ($q) => $q->where('status', TaskStatus::HOLD->value),
            ])
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create', [
            'category' => new Category(['color' => '#4F46E5']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::create([
            ...$this->validateCategory($request),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('categories.show', $category)
            ->with('status', 'Category created.');
    }

    public function show(Category $category): View
    {
        $category->load([
            'tasks' => fn ($q) => $q->byPriority()->with('assignee'),
        ]);

        return view('categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validateCategory($request, $category));

        return redirect()->route('categories.show', $category)
            ->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')
            ->with('status', 'Category deleted.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category)],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
