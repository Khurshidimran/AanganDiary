<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::with(['category', 'brand', 'variants.unit'])->orderBy('name')->paginate(20);

        return view('products.index', compact('products'));
    }

    public function report(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');

        $variants = ProductVariant::query()
            ->select('product_variants.*')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->with(['product.category', 'product.brand', 'unit'])
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('product_variants.sku', 'like', "%{$search}%")
                        ->orWhere('product_variants.barcode', 'like', "%{$search}%")
                        ->orWhere('product_variants.name', 'like', "%{$search}%")
                        ->orWhere('products.name', 'like', "%{$search}%");
                });
            })
            ->orderBy('products.name')
            ->orderBy('product_variants.name')
            ->paginate(30)
            ->withQueryString();

        return view('products.report', [
            'variants' => $variants,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'filters' => ['category_id' => $categoryId, 'search' => $search],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', [
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
            'units' => Unit::orderBy('name')->get(),
            'componentOptions' => $this->componentOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $isBundle = (bool) ($validated['is_bundle'] ?? false);

        $product = DB::transaction(function () use ($validated, $isBundle) {
            $product = Product::create([
                ...collect($validated)->except(['variants', 'bundle_items'])->all(),
                'is_bundle' => $isBundle,
                'track_inventory' => $isBundle ? false : ($validated['track_inventory'] ?? true),
                'slug' => $this->uniqueSlug($validated['name']),
            ]);

            foreach ($validated['variants'] as $variant) {
                $product->variants()->create($variant);
            }

            if ($isBundle) {
                foreach ($validated['bundle_items'] ?? [] as $item) {
                    $product->bundleItems()->create($item);
                }
            }

            return $product;
        });

        $this->auditLog->log('created', 'products', $product, null, $product->only(['name', 'status']));

        return redirect()->route('products.index')->with('status', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load(['variants', 'bundleItems', 'images']);

        return view('products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
            'units' => Unit::orderBy('name')->get(),
            'componentOptions' => $this->componentOptions($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $isBundle = (bool) ($validated['is_bundle'] ?? false);
        $before = $product->only(['name', 'status']);

        DB::transaction(function () use ($product, $validated, $isBundle) {
            $productData = collect($validated)->except(['variants', 'bundle_items'])->all();
            $productData['is_bundle'] = $isBundle;
            $productData['track_inventory'] = $isBundle ? false : ($validated['track_inventory'] ?? true);

            if ($productData['name'] !== $product->name) {
                $productData['slug'] = $this->uniqueSlug($productData['name'], $product->id);
            }

            $product->update($productData);

            $keptIds = [];

            foreach ($validated['variants'] as $variant) {
                $variantModel = $product->variants()->updateOrCreate(
                    ['id' => $variant['id'] ?? null],
                    collect($variant)->except('id')->all(),
                );

                $keptIds[] = $variantModel->id;
            }

            $product->variants()->whereNotIn('id', $keptIds)->delete();

            $product->bundleItems()->delete();

            if ($isBundle) {
                foreach ($validated['bundle_items'] ?? [] as $item) {
                    $product->bundleItems()->create($item);
                }
            }
        });

        $this->auditLog->log('updated', 'products', $product, $before, $product->only(['name', 'status']));

        return redirect()->route('products.index')->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $before = $product->only(['name', 'status']);
        $product->delete();

        $this->auditLog->log('deleted', 'products', null, $before, null);

        return redirect()->route('products.index')->with('status', 'Product deleted successfully.');
    }

    /**
     * Variants eligible to be picked as bundle components: active, non-bundle
     * products, excluding the bundle product itself (no self-reference) and
     * any other bundle's variants (no nested bundles).
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, ProductVariant>>
     */
    private function componentOptions(?Product $excludingProduct = null): \Illuminate\Support\Collection
    {
        return ProductVariant::query()
            ->with('product')
            ->where('is_active', true)
            ->whereHas('product', function ($q) use ($excludingProduct) {
                $q->where('is_bundle', false);

                if ($excludingProduct) {
                    $q->where('id', '!=', $excludingProduct->id);
                }
            })
            ->get()
            ->groupBy(fn (ProductVariant $variant) => $variant->product->name);
    }

    private function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 1;

        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
