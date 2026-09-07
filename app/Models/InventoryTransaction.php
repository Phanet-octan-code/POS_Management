<?php

namespace App\Models;

/**
 * Class InventoryTransaction
 * Alias/adapter for StockMovement for backwards-compatibility
 */
class InventoryTransaction extends StockMovement
{
    protected $table = 'stock_movements';
}
