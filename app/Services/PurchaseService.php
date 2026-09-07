<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Create a purchase order, line items, update supplier balance, and adjust stock if received.
     */
    public function createPurchase(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Calculate financial line items and subtotal
            $subtotal = 0.0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $qty = (int) $item['quantity'];
                $cost = (float) $item['unit_cost'];
                $lineSubtotal = $qty * $cost;
                $subtotal += $lineSubtotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'subtotal' => $lineSubtotal,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ];
            }

            $discountAmount = (float) ($data['discount_amount'] ?? 0.0);
            $taxAmount = (float) ($data['tax_amount'] ?? 0.0);
            $totalAmount = max(0.0, ($subtotal + $taxAmount) - $discountAmount);

            $paidAmount = isset($data['paid_amount']) ? min($totalAmount, max(0.0, (float) $data['paid_amount'])) : 0.0;
            $dueAmount = max(0.0, $totalAmount - $paidAmount);

            // Determine payment status
            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'unpaid';
            }
            if (isset($data['payment_status']) && in_array($data['payment_status'], ['paid', 'partial', 'unpaid'])) {
                $paymentStatus = $data['payment_status'];
            }

            $referenceNo = $data['reference_no'] ?? 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            // 2. Create Purchase Record
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => $userId,
                'reference_no' => $referenceNo,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $data['status'] ?? 'received',
                'payment_status' => $paymentStatus,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
            ]);

            // 3. Create Purchase Items & Update Inventory if status is 'received'
            foreach ($itemsData as $itemRow) {
                $purchase->items()->create($itemRow);

                if ($purchase->status === 'received') {
                    $product = Product::lockForUpdate()->findOrFail($itemRow['product_id']);

                    // Current Stock + Purchased Quantity = New Stock
                    $this->inventoryService->adjustStock(
                        product: $product,
                        quantityChange: $itemRow['quantity'],
                        type: 'purchase',
                        reason: "Purchase Order {$purchase->reference_no}",
                        refType: Purchase::class,
                        refId: $purchase->id,
                        userId: $userId
                    );
                }
            }

            // 4. Update Supplier balance if due amount exists
            if ($dueAmount > 0) {
                $supplier = Supplier::lockForUpdate()->find($purchase->supplier_id);
                if ($supplier) {
                    $supplier->increment('balance', $dueAmount);
                }
            }

            return $purchase->fresh(['supplier', 'items.product', 'user']);
        });
    }

    /**
     * Update an existing purchase order, reconciling inventory and supplier balance changes.
     */
    public function updatePurchase(Purchase $purchase, array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $userId) {
            $purchase = Purchase::lockForUpdate()->with('items')->findOrFail($purchase->id);
            $oldStatus = $purchase->status;
            $oldSupplierId = $purchase->supplier_id;
            $oldDueAmount = (float) $purchase->due_amount;

            // 1. If previously received, revert old stock additions
            if ($oldStatus === 'received') {
                foreach ($purchase->items as $oldItem) {
                    $product = Product::lockForUpdate()->findOrFail($oldItem->product_id);
                    $this->inventoryService->adjustStock(
                        product: $product,
                        quantityChange: -$oldItem->quantity,
                        type: 'purchase',
                        reason: "Purchase Order Update Adjustment Reversal {$purchase->reference_no}",
                        refType: Purchase::class,
                        refId: $purchase->id,
                        userId: $userId
                    );
                }
            }

            // 2. Revert previous supplier balance impact
            if ($oldDueAmount > 0) {
                $oldSupplier = Supplier::lockForUpdate()->find($oldSupplierId);
                if ($oldSupplier) {
                    $oldSupplier->decrement('balance', min($oldSupplier->balance, $oldDueAmount));
                }
            }

            // 3. Remove old purchase items
            $purchase->items()->delete();

            // 4. Calculate new financials
            $subtotal = 0.0;
            $itemsData = [];
            foreach ($data['items'] as $item) {
                $qty = (int) $item['quantity'];
                $cost = (float) $item['unit_cost'];
                $lineSubtotal = $qty * $cost;
                $subtotal += $lineSubtotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'subtotal' => $lineSubtotal,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ];
            }

            $discountAmount = (float) ($data['discount_amount'] ?? 0.0);
            $taxAmount = (float) ($data['tax_amount'] ?? 0.0);
            $totalAmount = max(0.0, ($subtotal + $taxAmount) - $discountAmount);

            $paidAmount = isset($data['paid_amount']) ? min($totalAmount, max(0.0, (float) $data['paid_amount'])) : 0.0;
            $dueAmount = max(0.0, $totalAmount - $paidAmount);

            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'unpaid';
            }
            if (isset($data['payment_status']) && in_array($data['payment_status'], ['paid', 'partial', 'unpaid'])) {
                $paymentStatus = $data['payment_status'];
            }

            // 5. Update purchase record
            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'] ?? $purchase->purchase_date,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $data['status'] ?? $purchase->status,
                'payment_status' => $paymentStatus,
                'payment_method' => $data['payment_method'] ?? $purchase->payment_method,
                'notes' => $data['notes'] ?? null,
            ]);

            // 6. Create new items and apply stock if status is received
            foreach ($itemsData as $itemRow) {
                $purchase->items()->create($itemRow);

                if ($purchase->status === 'received') {
                    $product = Product::lockForUpdate()->findOrFail($itemRow['product_id']);
                    $this->inventoryService->adjustStock(
                        product: $product,
                        quantityChange: $itemRow['quantity'],
                        type: 'purchase',
                        reason: "Purchase Order {$purchase->reference_no} Updated",
                        refType: Purchase::class,
                        refId: $purchase->id,
                        userId: $userId
                    );
                }
            }

            // 7. Update supplier balance with new due amount
            if ($dueAmount > 0) {
                $newSupplier = Supplier::lockForUpdate()->find($purchase->supplier_id);
                if ($newSupplier) {
                    $newSupplier->increment('balance', $dueAmount);
                }
            }

            return $purchase->fresh(['supplier', 'items.product', 'user']);
        });
    }

    /**
     * Delete purchase order, rolling back inventory additions if goods were received.
     */
    public function deletePurchase(Purchase $purchase, int $userId): bool
    {
        return DB::transaction(function () use ($purchase, $userId) {
            $purchase = Purchase::lockForUpdate()->with('items')->findOrFail($purchase->id);

            // Revert stock additions if goods were received
            if ($purchase->status === 'received') {
                foreach ($purchase->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);
                    $this->inventoryService->adjustStock(
                        product: $product,
                        quantityChange: -$item->quantity,
                        type: 'purchase',
                        reason: "Purchase Order {$purchase->reference_no} Deleted / Cancelled",
                        refType: Purchase::class,
                        refId: $purchase->id,
                        userId: $userId
                    );
                }
            }

            // Revert supplier balance if due amount existed
            if ($purchase->due_amount > 0) {
                $supplier = Supplier::lockForUpdate()->find($purchase->supplier_id);
                if ($supplier) {
                    $supplier->decrement('balance', min($supplier->balance, $purchase->due_amount));
                }
            }

            // Delete line items and soft-delete purchase
            $purchase->items()->delete();
            return $purchase->delete();
        });
    }
}
