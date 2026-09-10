<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StorePurchaseReceiptRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Services\AccountingPostingService;
use App\Services\AuditLogService;
use App\Services\PurchaseReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseReceiptController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly PurchaseReceiptService $receiptService,
        private readonly AccountingPostingService $accounting,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseReceipt::class);

        $purchaseReceipts = PurchaseReceipt::with(['vendor', 'warehouse', 'purchaseOrder'])
            ->latest('receipt_date')
            ->paginate(20);

        return view('purchase-receipts.index', compact('purchaseReceipts'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PurchaseReceipt::class);

        $purchaseOrder = PurchaseOrder::with(['vendor', 'warehouse', 'items.productVariant.product', 'items.productVariant.unit'])
            ->where('id', $request->query('purchase_order'))
            ->firstOrFail();

        abort_unless($purchaseOrder->canReceiveStock(), 403, 'This purchase order is not open to receive stock.');

        return view('purchase-receipts.create', compact('purchaseOrder'));
    }

    public function store(StorePurchaseReceiptRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);

        $receipt = $this->receiptService->receive(
            purchaseOrder: $purchaseOrder,
            receiptDate: $validated['receipt_date'],
            items: $validated['items'],
            receivedBy: $request->user()->id,
            invoiceNumber: $validated['invoice_number'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        $this->auditLog->log('created', 'purchase_receipts', $receipt, null, ['receipt_number' => $receipt->receipt_number]);

        $entry = $this->accounting->postPurchaseEntry($receipt);
        $accountingNote = $entry ? '' : ' Accounting entry was not posted — finish Account Mapping setup.';

        return redirect()->route('purchase-receipts.show', $receipt)->with('status', 'Stock received successfully.'.$accountingNote);
    }

    public function show(PurchaseReceipt $purchaseReceipt): View
    {
        $this->authorize('view', $purchaseReceipt);

        $purchaseReceipt->load(['vendor', 'warehouse', 'purchaseOrder', 'receivedBy', 'items.productVariant.product', 'items.productVariant.unit']);

        return view('purchase-receipts.show', compact('purchaseReceipt'));
    }

    /**
     * Reverses a receipt made with the wrong quantity or cost — undoes its
     * stock and accounting effect, and reopens the PO (back to Draft if
     * nothing else is still received against it) so staff can fix the
     * numbers and receive again, correctly.
     */
    public function unpost(Request $request, PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->authorize('unpost', $purchaseReceipt);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $this->receiptService->unpost($purchaseReceipt, $validated['reason']);
        } catch (InsufficientStockException $e) {
            return back()->with('error', "Cannot unpost: {$e->getMessage()} — some of this stock has already been used elsewhere.");
        }

        $this->accounting->voidPurchaseEntry($purchaseReceipt, $validated['reason']);

        $this->auditLog->log('unposted', 'purchase_receipts', $purchaseReceipt, null, ['reason' => $validated['reason']]);

        return redirect()->route('purchase-orders.show', $purchaseReceipt->purchase_order_id)
            ->with('status', "Receipt {$purchaseReceipt->receipt_number} unposted — the purchase order is open to edit and receive again.");
    }
}
