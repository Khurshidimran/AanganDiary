<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\UpdateStockAdjustmentRequest;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    private const REASON_TO_TYPE = [
        StockAdjustmentItem::REASON_CORRECTION => InventoryTransaction::TYPE_STOCK_ADJUSTMENT,
        StockAdjustmentItem::REASON_WASTAGE => InventoryTransaction::TYPE_WASTAGE,
        StockAdjustmentItem::REASON_DAMAGE => InventoryTransaction::TYPE_DAMAGE,
    ];

    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly InventoryService $inventory,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', StockAdjustment::class);

        $stockAdjustments = StockAdjustment::with(['warehouse', 'createdBy'])
            ->latest('adjustment_date')
            ->paginate(20);

        return view('stock-adjustments.index', compact('stockAdjustments'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StockAdjustment::class);

        return view('stock-adjustments.create', [
            'warehouses' => Warehouse::where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'selectedWarehouseId' => $request->query('warehouse_id'),
            'selectedCategoryId' => $request->query('category_id'),
            'rows' => $request->query('warehouse_id') ? $this->buildRows($request->query('warehouse_id'), $request->query('category_id')) : collect(),
            'stockAdjustment' => null,
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $stockAdjustment = DB::transaction(function () use ($validated, $request) {
            $stockAdjustment = StockAdjustment::create([
                'adjustment_number' => $this->nextAdjustmentNumber(),
                'warehouse_id' => $validated['warehouse_id'],
                'status' => StockAdjustment::STATUS_DRAFT,
                'adjustment_date' => $validated['adjustment_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $stockAdjustment->items()->create([
                    ...$item,
                    'batch_number' => $item['batch_number'] ?? '',
                ]);
            }

            return $stockAdjustment;
        });

        $this->auditLog->log('created', 'stock_adjustments', $stockAdjustment, null, ['adjustment_number' => $stockAdjustment->adjustment_number]);

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('status', 'Stock adjustment draft created.');
    }

    public function show(StockAdjustment $stockAdjustment): View
    {
        $this->authorize('view', $stockAdjustment);

        $stockAdjustment->load(['warehouse', 'createdBy', 'postedBy', 'items.productVariant.product', 'items.productVariant.unit']);

        return view('stock-adjustments.show', compact('stockAdjustment'));
    }

    public function edit(Request $request, StockAdjustment $stockAdjustment): View
    {
        $this->authorize('update', $stockAdjustment);

        $stockAdjustment->load('items');
        $categoryId = $request->query('category_id');

        return view('stock-adjustments.edit', [
            'stockAdjustment' => $stockAdjustment,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'selectedCategoryId' => $categoryId,
            'rows' => $this->buildRows($stockAdjustment->warehouse_id, $categoryId, $stockAdjustment),
        ]);
    }

    public function update(UpdateStockAdjustmentRequest $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $stockAdjustment) {
            $stockAdjustment->update([
                'adjustment_date' => $validated['adjustment_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $stockAdjustment->items()->delete();

            foreach ($validated['items'] as $item) {
                $stockAdjustment->items()->create([
                    ...$item,
                    'batch_number' => $item['batch_number'] ?? '',
                ]);
            }
        });

        $this->auditLog->log('updated', 'stock_adjustments', $stockAdjustment, null, ['adjustment_number' => $stockAdjustment->adjustment_number]);

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('status', 'Stock adjustment draft updated.');
    }

    public function destroy(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorize('delete', $stockAdjustment);

        $stockAdjustment->delete();

        $this->auditLog->log('deleted', 'stock_adjustments', null, ['adjustment_number' => $stockAdjustment->adjustment_number], null);

        return redirect()->route('stock-adjustments.index')->with('status', 'Stock adjustment draft deleted.');
    }

    public function post(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorize('post', $stockAdjustment);

        try {
            DB::transaction(function () use ($stockAdjustment) {
                $stockAdjustment->load(['items.productVariant', 'warehouse']);

                foreach ($stockAdjustment->items as $item) {
                    $signedQuantity = $item->direction === StockAdjustmentItem::DIRECTION_INCREASE
                        ? (float) $item->quantity
                        : -1 * (float) $item->quantity;

                    $this->inventory->postTransaction(
                        variant: $item->productVariant,
                        warehouse: $stockAdjustment->warehouse,
                        transactionType: self::REASON_TO_TYPE[$item->reason],
                        quantity: $signedQuantity,
                        batchNumber: $item->batch_number,
                        referenceType: 'stock_adjustment',
                        referenceId: $stockAdjustment->id,
                        notes: $item->notes ?? "Posted on {$stockAdjustment->adjustment_number}",
                    );
                }

                $stockAdjustment->update([
                    'status' => StockAdjustment::STATUS_POSTED,
                    'posted_by' => request()->user()->id,
                    'posted_at' => now(),
                ]);
            });
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('posted', 'stock_adjustments', $stockAdjustment, null, ['status' => $stockAdjustment->status]);

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('status', 'Stock adjustment posted — stock updated.');
    }

    /**
     * Build one row per active variant (optionally filtered by category), always including
     * any variant already selected on the given draft so filtering never silently drops items.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildRows(string $warehouseId, ?string $categoryId, ?StockAdjustment $stockAdjustment = null): \Illuminate\Support\Collection
    {
        $existingItems = $stockAdjustment?->items->keyBy('product_variant_id') ?? collect();
        $existingVariantIds = $existingItems->keys()->all();

        $variants = ProductVariant::with(['product.category', 'unit'])
            ->where('is_active', true)
            ->when($categoryId, function ($query) use ($categoryId, $existingVariantIds) {
                $query->where(function ($q) use ($categoryId, $existingVariantIds) {
                    $q->whereHas('product', fn ($pq) => $pq->where('category_id', $categoryId));

                    if (! empty($existingVariantIds)) {
                        $q->orWhereIn('id', $existingVariantIds);
                    }
                });
            })
            ->get()
            ->sortBy('product.name');

        $balances = StockBalance::where('warehouse_id', $warehouseId)
            ->whereIn('product_variant_id', $variants->pluck('id'))
            ->get()
            ->groupBy('product_variant_id');

        return $variants->map(function (ProductVariant $variant) use ($balances, $existingItems) {
            $variantBalances = $balances->get($variant->id, collect());

            return [
                'variant' => $variant,
                'current_stock' => rtrim(rtrim((string) $variantBalances->sum('quantity'), '0'), '.') ?: '0',
                'batches' => $variantBalances->where('quantity', '>', 0)->map(fn (StockBalance $b) => [
                    'batch' => $b->batch_number ?: '(none)',
                    'qty' => rtrim(rtrim((string) $b->quantity, '0'), '.'),
                ])->values(),
                'existing' => $existingItems->get($variant->id),
            ];
        })->values();
    }

    private function nextAdjustmentNumber(): string
    {
        $next = StockAdjustment::withTrashed()->count() + 1;

        return 'SA-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
