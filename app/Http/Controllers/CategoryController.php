<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::with('parent')->orderBy('name')->paginate(20);

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        $parents = Category::orderBy('name')->pluck('name', 'id');

        return view('categories.create', compact('parents'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        $category = Category::create($validated);

        $this->auditLog->log('created', 'categories', $category, null, $category->only(['name', 'status']));

        return redirect()->route('categories.index')->with('status', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->pluck('name', 'id');

        return view('categories.edit', compact('category', 'parents'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();
        $before = $category->only(['name', 'status']);

        if ($validated['name'] !== $category->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $category->id);
        }

        $category->update($validated);

        $this->auditLog->log('updated', 'categories', $category, $before, $category->only(['name', 'status']));

        return redirect()->route('categories.index')->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $before = $category->only(['name', 'status']);
        $category->delete();

        $this->auditLog->log('deleted', 'categories', null, $before, null);

        return redirect()->route('categories.index')->with('status', 'Category deleted successfully.');
    }

    private function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 1;

        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
