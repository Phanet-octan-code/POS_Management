<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReturnService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Generate unique Return Number in format RET-YYYY-XXXXXX (e.g. RET-2026-000001)
     */
    public function generateReturnNumber(): string
    {
        $year = date('Y');
        $prefix = "RET-{$year}-";

        $latestReturn = ReturnOrder::withTrashed()
            ->where('return_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $nextNumber = 1;
        if ($latestReturn && preg_match('/^RET-\d{4}-(\d+)$/', $latestReturn->return_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = sprintf('RET-%s-%06d', $year, $nextNumber);
            $exists = ReturnOrder::withTrashed()->where('return_number', $candidate)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $candidate;
    }

    /**
     * Get sale invoice details along with remaining returnable quantities per item.
     * 
     * @throws InvalidArgumentException
     */
    public function getInvoiceReturnableDetails(string $invoiceNo): array
    {
        $sale = Sale::with(['items.product', 'customer', 'user', 'returns.items'])
            ->where('invoice_no', trim($invoiceNo))
            ->first();

        if (!$sale) {
            throw new InvalidArgumentException("Sale invoice '{$invoiceNo}' was not found.");
        }

        $items = [];
        $totalReturnableUnits = 0;

        foreach ($sale->items as $saleItem) {
            $productId = $saleItem->product_id;
            
            // Calculate total units already returned for this specific product across all prior returns for this sale
            $previouslyReturned = (int) ReturnItem::whereHas('returnOrder', function ($q) use ($sale) {
                $q->where('sale_id', $sale->id)->where('status', 'completed');
            })->where('product_id', $productId)->sum('quantity');

            $maxReturnable = max(0, $saleItem->quantity - $previouslyReturned);
            $totalReturnableUnits += $maxReturnable;

            $items[] = [
                'sale_item_id' => $saleItem->id,
                'product_id' => $productId,
                'product_name' => $saleItem->product?->name ?? 'Product',
                'sku' => $saleItem->product?->sku ?? '',
                'barcode' => $saleItem->product?->barcode ?? '',
                'unit' => $saleItem->product?->unit ?? 'pcs',
                'current_stock' => (int) ($saleItem->product?->stock_quantity ?? 0),
                'quantity_sold' => (int) $saleItem->quantity,
                'previously_returned' => $previouslyReturned,
                'max_returnable' => $maxReturnable,
                'unit_price' => (float) $saleItem->unit_price,
                'subtotal' => (float) $saleItem->subtotal,
            ];
        }

        return [
            'sale' => $sale,
            'items' => $items,
            'total_returnable_units' => $totalReturnableUnits,
            'is_fully_returned' => $totalReturnableUnits <= 0,
        ];
    }

    /**
     * Complete a product return transaction:
     * 1. Validate sale exists & items selected
     * 2. Prevent returning more than originally sold (strictly checks cumulative return limits)
     * 3. Create ReturnOrder & ReturnItem records
     * 4. Automatically increase product stock
     * 5. Create stock movement records (type = 'return')
     * 6. Use database transaction
     * 
     * @throws InvalidArgumentException
     */
    public function processReturn(array $data): ReturnOrder
    {
        return DB::transaction(function () use ($data) {
            $saleId = (int) $data['sale_id'];
            $sale = Sale::lockForUpdate()->findOrFail($saleId);

            $cashierId = Auth::id() ?? 1;

            if (empty($data['items']) || !is_array($data['items'])) {
                throw new InvalidArgumentException('Please select at least one product to return.');
            }

            $validatedReturnItems = [];
            $totalRefund = 0.00;

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $returnQty = (int) ($item['quantity'] ?? 0);

                if ($returnQty <= 0) {
                    continue; // Skip zero-quantity lines
                }

                // Verify product exists and lock for update
                $product = Product::lockForUpdate()->findOrFail($productId);

                // Find corresponding sale line item
                $saleItem = SaleItem::where('sale_id', $sale->id)
                    ->where('product_id', $productId)
                    ->first();

                if (!$saleItem) {
                    throw new InvalidArgumentException("Product '{$product->name}' was not part of sale invoice {$sale->invoice_no}.");
                }

                // Calculate previously returned quantity with lock
                $previouslyReturned = (int) ReturnItem::whereHas('returnOrder', function ($q) use ($sale) {
                    $q->where('sale_id', $sale->id)->where('status', 'completed');
                })->where('product_id', $productId)->sum('quantity');

                $maxReturnable = max(0, $saleItem->quantity - $previouslyReturned);

                if ($returnQty > $maxReturnable) {
                    throw new InvalidArgumentException(
                        "Cannot return {$returnQty} units of '{$product->name}'. Maximum returnable quantity is {$maxReturnable} (Sold: {$saleItem->quantity}, Already Returned: {$previouslyReturned})."
                    );
                }

                $unitPrice = (float) ($item['unit_price'] ?? $saleItem->unit_price);
                $lineRefund = round($returnQty * $unitPrice, 2);
                $totalRefund += $lineRefund;

                $validatedReturnItems[] = [
                    'product' => $product,
                    'quantity' => $returnQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineRefund,
                    'condition' => $item['condition'] ?? 'resellable',
                ];
            }

            if (empty($validatedReturnItems)) {
                throw new InvalidArgumentException('Please specify a return quantity greater than 0 for at least one item.');
            }

            // Generate unique return number
            $returnNumber = $this->generateReturnNumber();

            // Create ReturnOrder
            $returnOrder = ReturnOrder::create([
                'return_number' => $returnNumber,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'user_id' => $cashierId,
                'return_date' => now(),
                'total_refund' => round($totalRefund, 2),
                'status' => 'completed',
                'reason' => $data['reason'] ?? 'Customer Return',
            ]);

            // Create ReturnItems & Increase Product Stock Automatically & Log Stock Movements
            foreach ($validatedReturnItems as $vItem) {
                $product = $vItem['product'];
                $returnQty = $vItem['quantity'];

                ReturnItem::create([
                    'return_id' => $returnOrder->id,
                    'product_id' => $product->id,
                    'quantity' => $returnQty,
                    'unit_price' => $vItem['unit_price'],
                    'subtotal' => $vItem['subtotal'],
                    'condition' => $vItem['condition'],
                ]);

                // Automatically increase product stock & create stock movement record
                $this->inventoryService->adjustStock(
                    product: $product,
                    quantityChange: $returnQty, // Positive quantity adds stock
                    type: 'return',
                    reason: "Product Return #{$returnOrder->return_number} (Invoice: {$sale->invoice_no})",
                    refType: ReturnOrder::class,
                    refId: $returnOrder->id,
                    userId: $cashierId
                );
            }

            // Adjust customer total_spent and balance if applicable
            if (!empty($sale->customer_id)) {
                $customer = Customer::lockForUpdate()->find($sale->customer_id);
                if ($customer) {
                    $customer->decrement('total_spent', min((float) $customer->total_spent, $totalRefund));
                    // If customer had an outstanding balance on this invoice, reduce balance by refund amount
                    if ((float) $customer->balance > 0 && (float) $sale->due_amount > 0) {
                        $balanceReduction = min((float) $customer->balance, (float) $sale->due_amount, $totalRefund);
                        $customer->decrement('balance', $balanceReduction);
                    }
                }
            }

            ActivityLoggerService::log(
                'return.created',
                "Processed return {$returnOrder->return_number} for Invoice {$sale->invoice_no} (\${$returnOrder->total_refund})",
                $returnOrder
            );

            return $returnOrder;
        });
    }
}
