<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_category_id',
        'user_id',
        'reference_no',
        'title',
        'amount',
        'expense_date',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias 'name' for 'title' (Expense Name)
     */
    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['title'] ?? '');
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['title'] = (string) $value;
    }

    /**
     * Alias 'description' for 'notes'
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['notes'] ?? null;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['notes'] = $value;
    }

    /**
     * Color scheme for category badge
     */
    public function getCategoryColorAttribute(): string
    {
        $slug = $this->category?->slug ?? '';
        return match ($slug) {
            'rent' => 'primary',
            'electricity' => 'warning',
            'internet' => 'info',
            'salary' => 'success',
            'transportation' => 'secondary',
            'maintenance' => 'danger',
            'marketing' => 'dark',
            default => 'light text-dark',
        };
    }

    /**
     * Scope query to apply search, date, category, and user filters
     */
    public function scopeFilter(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $term = trim($search);
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%")
                        ->orWhere('reference_no', 'like', "%{$term}%")
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['start_date'] ?? null, function ($q, $startDate) {
                $q->whereDate('expense_date', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($q, $endDate) {
                $q->whereDate('expense_date', '<=', $endDate);
            })
            ->when($filters['category_id'] ?? null, function ($q, $categoryId) {
                $q->where('expense_category_id', $categoryId);
            })
            ->when($filters['user_id'] ?? null, function ($q, $userId) {
                $q->where('user_id', $userId);
            });
    }
}
