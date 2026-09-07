<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'user_id',
        'invoice_no',
        'sale_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'due_amount',
        'payment_method',
        'payment_status',
        'reprint_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'reprint_count' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match (strtolower($this->payment_method ?? '')) {
            'cash' => 'Cash',
            'aba' => 'ABA',
            'acleda' => 'ACLEDA',
            'credit_card', 'credit card' => 'Credit Card',
            'debit_card', 'debit card' => 'Debit Card',
            'bank_transfer', 'bank transfer' => 'Bank Transfer',
            'other' => 'Other',
            'card' => 'Credit Card',
            'qr' => 'ABA',
            default => ucfirst(str_replace('_', ' ', $this->payment_method ?? 'Cash')),
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match (strtolower($this->payment_status ?? '')) {
            'paid' => 'Paid',
            'partial' => 'Partial',
            'unpaid', 'due' => 'Unpaid',
            default => ucfirst($this->payment_status ?? 'Paid'),
        };
    }
}

