<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getPreviousStockAttribute(): int
    {
        return (int) $this->stock_before;
    }

    public function getNewStockAttribute(): int
    {
        return (int) $this->stock_after;
    }

    public function getTypeBadgeAttribute(): string
    {
        return match (strtolower($this->type)) {
            'in', 'addition', 'purchase', 'return' => 'success',
            'out', 'subtraction', 'damage', 'loss' => 'danger',
            'adjustment' => 'warning',
            'sale' => 'info',
            default => 'secondary',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match (strtolower($this->type)) {
            'in', 'addition' => 'Stock Addition (In)',
            'out', 'subtraction' => 'Stock Removal (Out)',
            'adjustment' => 'Manual Adjustment',
            'damage', 'loss' => 'Damaged / Lost',
            'return' => 'Customer Return',
            'sale' => 'POS Sale Order',
            'purchase' => 'Vendor Purchase',
            default => ucfirst($this->type),
        };
    }

    public function scopeFilter(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->when($filters['product_id'] ?? null, function ($q, $prodId) {
                $q->where('product_id', $prodId);
            })
            ->when($filters['type'] ?? null, function ($q, $type) {
                $q->where('type', $type);
            })
            ->when($filters['date_from'] ?? null, function ($q, $from) {
                $q->whereDate('created_at', '>=', $from);
            })
            ->when($filters['date_to'] ?? null, function ($q, $to) {
                $q->whereDate('created_at', '<=', $to);
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('reason', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%")
                              ->orWhere('sku', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%");
                        });
                });
            });
    }
}
