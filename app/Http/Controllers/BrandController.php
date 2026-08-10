<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Brand::class);

        $brands = Brand::orderBy('name')->paginate(20);

        return view('brands.index', compact('brands'));
    }

    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        $brand = Brand::create($validated);

        $this->auditLog->log('created', 'brands', $brand, null, $brand->only(['name', 'status']));

        return redirect()->route('brands.index')->with('status', 'Brand created successfully.');
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validated();
        $before = $brand->only(['name', 'status']);

        if ($validated['name'] !== $brand->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $brand->id);
        }

        $brand->update($validated);

        $this->auditLog->log('updated', 'brands', $brand, $before, $brand->only(['name', 'status']));

        return redirect()->route('brands.index')->with('status', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        $before = $brand->only(['name', 'status']);
        $brand->delete();

        $this->auditLog->log('deleted', 'brands', null, $before, null);

        return redirect()->route('brands.index')->with('status', 'Brand deleted successfully.');
    }

    private function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 1;

        while (Brand::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
