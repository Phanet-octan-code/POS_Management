<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class Notification extends DatabaseNotification
{
    protected $table = 'notifications';

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Decode json data payload safely.
     */
    public function getDataArrayAttribute(): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }
        return json_decode($this->data, true) ?: [];
    }

    /**
     * Normalized notification type code.
     */
    public function getTypeCodeAttribute(): string
    {
        $type = $this->data_array['type'] ?? $this->type;
        return Str::snake(class_basename($type));
    }

    /**
     * Human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type_code) {
            'low_stock', 'low_stock_notification' => 'Low Stock Alert',
            'out_of_stock', 'out_of_stock_notification' => 'Out of Stock Alert',
            'new_sale', 'new_sale_notification' => 'New Sale Completed',
            'new_purchase', 'new_purchase_notification' => 'New Purchase Received',
            'payment', 'payment_notification' => 'Payment Recorded',
            'expense', 'expense_notification' => 'Expense Recorded',
            default => Str::headline($this->type_code)
        };
    }

    /**
     * Bootstrap Icon for notification type.
     */
    public function getIconAttribute(): string
    {
        return match ($this->type_code) {
            'low_stock', 'low_stock_notification' => 'bi-exclamation-triangle-fill',
            'out_of_stock', 'out_of_stock_notification' => 'bi-x-circle-fill',
            'new_sale', 'new_sale_notification' => 'bi-cart-check-fill',
            'new_purchase', 'new_purchase_notification' => 'bi-bag-check-fill',
            'payment', 'payment_notification' => 'bi-cash-coin',
            'expense', 'expense_notification' => 'bi-wallet2',
            default => 'bi-bell-fill'
        };
    }

    /**
     * Theme color for badge and icon backgrounds.
     */
    public function getColorAttribute(): string
    {
        return match ($this->type_code) {
            'low_stock', 'low_stock_notification' => 'warning',
            'out_of_stock', 'out_of_stock_notification' => 'danger',
            'new_sale', 'new_sale_notification' => 'success',
            'new_purchase', 'new_purchase_notification' => 'primary',
            'payment', 'payment_notification' => 'info',
            'expense', 'expense_notification' => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Notification title from payload.
     */
    public function getTitleAttribute(): string
    {
        return $this->data_array['title'] ?? $this->type_label;
    }

    /**
     * Notification message text from payload.
     */
    public function getMessageAttribute(): string
    {
        return $this->data_array['message'] ?? '';
    }

    /**
     * Target link to navigate to when clicked.
     */
    public function getLinkAttribute(): string
    {
        return $this->data_array['link'] ?? route('notifications.index');
    }

    /**
     * Is the notification unread?
     */
    public function getIsUnreadAttribute(): bool
    {
        return is_null($this->read_at);
    }
}
