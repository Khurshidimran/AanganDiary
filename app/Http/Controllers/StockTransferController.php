<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\PurchaseReceiptItem;
use App\Models\StockBalance;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly InventoryService $inventory,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', StockTransfer::class);

        $stockTransfers = StockTransfer::with(['fromWarehouse', 'toWarehouse'])->latest('transfer_date')->paginate(20);

        return view('stock-transfers.index', compact('stockTransfers'));
    }

    public function create(): View
    {
        $this->authorize('create', StockTransfer::class);

        return view('stock-transfers.create', $this->formData());
    }

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $stockTransfer = DB::transaction(function () use ($validated, $request) {
            $stockTransfer = StockTransfer::create([
                'transfer_number' => $this->nextTransferNumber(),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'status' => StockTransfer::STATUS_DRAFT,
                'transfer_date' => $validated['transfer_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $stockTransfer->items()->create([
                    ...$item,
                    'batch_number' => $item['batch_number'] ?? '',
                ]);
            }

            return $stockTransfer;
        });

        $this->auditLog->log('created', 'stock_transfers', $stockTransfer, null, ['transfer_number' => $stockTransfer->transfer_number]);

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('status', 'Stock transfer created successfully.');
    }

    public function show(StockTransfer $stockTransfer): View
    {
        $this->authorize('view', $stockTransfer);

        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'createdBy', 'approvedBy', 'dispatchedBy', 'receivedBy', 'items.productVariant.product', 'items.productVariant.unit']);

        return view('stock-transfers.show', compact('stockTransfer'));
    }

    public function edit(StockTransfer $stockTransfer): View
    {
        $this->authorize('update', $stockTransfer);

        $stockTransfer->load('items');

        return view('stock-transfers.edit', [...$this->formData(), 'stockTransfer' => $stockTransfer]);
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $stockTransfer) {
            $stockTransfer->update([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'transfer_date' => $validated['transfer_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $stockTransfer->items()->delete();

            foreach ($validated['items'] as $item) {
                $stockTransfer->items()->create([
                    ...$item,
                    'batch_number' => $item['batch_number'] ?? '',
                ]);
            }
        });

        $this->auditLog->log('updated', 'stock_transfers', $stockTransfer, null, ['transfer_number' => $stockTransfer->transfer_number]);

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('status', 'Stock transfer updated successfully.');
    }

    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('delete', $stockTransfer);

        $stockTransfer->delete();

        $this->auditLog->log('deleted', 'stock_transfers', null, ['transfer_number' => $stockTransfer->transfer_number], null);

        return redirect()->route('stock-transfers.index')->with('status', 'Stock transfer deleted successfully.');
    }

    public function request(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('request', $stockTransfer);

        $stockTransfer->update(['status' => StockTransfer::STATUS_REQUESTED]);

        $this->auditLog->log('requested', 'stock_transfers', $stockTransfer, null, ['status' => $stockTransfer->status]);

        return back()->with('status', 'Transfer requested for approval.');
    }

    public function approve(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('approve', $stockTransfer);

        $stockTransfer->update([
            'status' => StockTransfer::STATUS_APPROVED,
            'approved_by' => request()->user()->id,
        ]);

        $this->auditLog->log('approved', 'stock_transfers', $stockTransfer, null, ['status' => $stockTransfer->status]);

        return back()->with('status', 'Transfer approved.');
    }

    public function dispatch(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('dispatch', $stockTransfer);

        try {
            DB::transaction(function () use ($stockTransfer) {
                $stockTransfer->load('items.productVariant');

                foreach ($stockTransfer->items as $item) {
                    $this->inventory->postTransaction(
                        variant: $item->productVariant,
                        warehouse: $stockTransfer->fromWarehouse,
                        transactionType: InventoryTransaction::TYPE_STOCK_TRANSFER_OUT,
                        quantity: -1 * (float) $item->quantity,
                        batchNumber: $item->batch_number,
                        referenceType: 'stock_transfer',
                        referenceId: $stockTransfer->id,
                        notes: "Dispatched on {$stockTransfer->transfer_number}",
                    );
                }

                $stockTransfer->update([
                    'status' => StockTransfer::STATUS_IN_TRANSIT,
                    'dispatched_by' => request()->user()->id,
                    'dispatched_at' => now(),
                ]);
            });
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('dispatched', 'stock_transfers', $stockTransfer, null, ['status' => $stockTransfer->status]);

        return back()->with('status', 'Transfer dispatched — stock deducted from source warehouse.');
    }

    public function receive(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('receive', $stockTransfer);

        DB::transaction(function () use ($stockTransfer) {
            $stockTransfer->load('items.productVariant');

            foreach ($stockTransfer->items as $item) {
                $expiryDate = StockBalance::where('product_variant_id', $item->product_variant_id)
                    ->where('warehouse_id', $stockTransfer->from_warehouse_id)
                    ->where('batch_number', $item->batch_number)
                    ->value('expiry_date');

                $this->inventory->postTransaction(
                    variant: $item->productVariant,
                    warehouse: $stockTransfer->toWarehouse,
                    transactionType: InventoryTransaction::TYPE_STOCK_TRANSFER_IN,
                    quantity: (float) $item->quantity,
                    batchNumber: $item->batch_number,
                    referenceType: 'stock_transfer',
                    referenceId: $stockTransfer->id,
                    notes: "Received from {$stockTransfer->transfer_number}",
                    expiryDate: $expiryDate,
                );
            }

            $stockTransfer->update([
                'status' => StockTransfer::STATUS_RECEIVED,
                'received_by' => request()->user()->id,
                'received_at' => now(),
            ]);
        });

        $this->auditLog->log('received', 'stock_transfers', $stockTransfer, null, ['status' => $stockTransfer->status]);

        return back()->with('status', 'Transfer received — stock added to destination warehouse.');
    }

    public function cancel(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('cancel', $stockTransfer);

        $stockTransfer->update(['status' => StockTransfer::STATUS_CANCELLED]);

        $this->auditLog->log('cancelled', 'stock_transfers', $stockTransfer, null, ['status' => $stockTransfer->status]);

        return back()->with('status', 'Transfer cancelled.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'warehouses' => Warehouse::where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'variants' => ProductVariant::with('product')->where('is_active', true)->get(),
            'stockByWarehouseJson' => $this->stockByWarehouseJson(),
            'lastPurchasePrices' => $this->lastPurchasePrices(),
        ];
    }

    /**
     * Most recent unit cost paid per variant, from purchase receipt history —
     * used to prefill the transfer item's unit cost when it's already known.
     *
     * @return array<string, string>
     */
    private function lastPurchasePrices(): array
    {
        return PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipts.id', '=', 'purchase_receipt_items.purchase_receipt_id')
            ->orderByDesc('purchase_receipts.receipt_date')
            ->orderByDesc('purchase_receipt_items.created_at')
            ->get(['purchase_receipt_items.product_variant_id', 'purchase_receipt_items.unit_cost'])
            ->unique('product_variant_id')
            ->pluck('unit_cost', 'product_variant_id')
            ->all();
    }

    private function nextTransferNumber(): string
    {
        $next = StockTransfer::withTrashed()->count() + 1;

        return 'ST-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Current stock per warehouse, shaped for the create/edit form's JS stock hint.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function stockByWarehouseJson(): array
    {
        return StockBalance::with('productVariant')
            ->where('quantity', '>', 0)
            ->get()
            ->groupBy('warehouse_id')
            ->map(fn ($balances) => $balances->map(fn (StockBalance $balance) => [
                'name' => $balance->productVariant->name,
                'sku' => $balance->productVariant->sku,
                'batch' => $balance->batch_number,
                'qty' => rtrim(rtrim($balance->quantity, '0'), '.'),
            ])->values())
            ->all();
    }
}
