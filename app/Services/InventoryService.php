<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Atomically adjust stock for a product inside a database transaction.
     *
     * @throws InvalidArgumentException
     */
    public function adjustStock(
        Product $product,
        int $quantityChange,
        string $type,
        ?string $reason = null,
        ?string $refType = null,
        ?int $refId = null,
        ?int $userId = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantityChange, $type, $reason, $refType, $refId, $userId) {
            // Lock the product row for update to ensure concurrency safety
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);

            $stockBefore = (int) $lockedProduct->stock_quantity;
            $stockAfter = $stockBefore + $quantityChange;

            // Reject negative stock calculation if stock is deducted below zero
            if ($stockAfter < 0) {
                throw new InvalidArgumentException(
                    "Insufficient stock! Product '{$lockedProduct->name}' only has {$stockBefore} units available. Cannot deduct " . abs($quantityChange) . " units."
                );
            }

            // Update main product stock level
            $lockedProduct->update([
                'stock_quantity' => $stockAfter,
            ]);

            // Synchronize product_stocks location record if one exists
            $locationStock = $lockedProduct->stocks()->first();
            if ($locationStock) {
                $locationStock->update([
                    'quantity' => $stockAfter,
                ]);
            }

            // Log the stock movement audit record
            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'user_id' => $userId ?? Auth::id(),
                'type' => $type,
                'quantity' => $quantityChange,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $reason,
                'reference_type' => $refType,
                'reference_id' => $refId,
            ]);
        });
    }

    /**
     * Add stock to product inventory
     */
    public function addStock(
        Product $product,
        int $quantity,
        ?string $reason = null,
        string $type = 'in',
        ?int $userId = null
    ): StockMovement {
        $qty = abs($quantity);
        return $this->adjustStock(
            product: $product,
            quantityChange: $qty,
            type: $type,
            reason: $reason ?? 'Stock addition',
            userId: $userId
        );
    }

    /**
     * Remove stock from product inventory
     */
    public function removeStock(
        Product $product,
        int $quantity,
        ?string $reason = null,
        string $type = 'out',
        ?int $userId = null
    ): StockMovement {
        $qty = -abs($quantity);
        return $this->adjustStock(
            product: $product,
            quantityChange: $qty,
            type: $type,
            reason: $reason ?? 'Stock removal',
            userId: $userId
        );
    }

    /**
     * Correct/set stock to a specific target count
     */
    public function setStock(
        Product $product,
        int $newQuantity,
        ?string $reason = null,
        ?int $userId = null
    ): StockMovement {
        $currentStock = (int) $product->stock_quantity;
        $delta = $newQuantity - $currentStock;

        return $this->adjustStock(
            product: $product,
            quantityChange: $delta,
            type: 'adjustment',
            reason: $reason ?? "Stock audit adjustment to {$newQuantity}",
            userId: $userId
        );
    }

    /**
     * Retrieve all low-stock products (stock > 0 and stock <= alert_quantity)
     */
    public function getLowStockProducts(): Collection
    {
        return Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'alert_quantity')
            ->get();
    }

    /**
     * Retrieve all out-of-stock products (stock <= 0)
     */
    public function getOutOfStockProducts(): Collection
    {
        return Product::where('is_active', true)
            ->where('stock_quantity', '<=', 0)
            ->get();
    }
}
