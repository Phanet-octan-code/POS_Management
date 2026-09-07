<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PosService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Generate unique POS Invoice Number matching INV-YYYY-XXXXXX format (e.g. INV-2026-000001)
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $rawPrefix = Setting::get('invoice_prefix', 'INV-');
        $cleanPrefix = rtrim($rawPrefix, '-');
        $fullPrefix = "{$cleanPrefix}-{$year}-";

        // Query the latest invoice number for the year with row lock
        $latestSale = Sale::withTrashed()
            ->where('invoice_no', 'like', "{$fullPrefix}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $nextNumber = 1;
        $pattern = '/^' . preg_quote($cleanPrefix, '/') . '-\d{4}-(\d+)$/';
        if ($latestSale && preg_match($pattern, $latestSale->invoice_no, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        // Guaranteed unique collision check
        do {
            $candidate = sprintf('%s-%s-%06d', $cleanPrefix, $year, $nextNumber);
            $exists = Sale::withTrashed()->where('invoice_no', $candidate)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $candidate;
    }

    /**
     * Generate unique Payment Reference Number (e.g. PAY-2026-000001)
     */
    public function generatePaymentNumber(): string
    {
        $year = date('Y');
        $prefix = "PAY-{$year}-";

        $latestPayment = Payment::where('payment_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $nextNumber = 1;
        if ($latestPayment && preg_match('/^PAY-\d{4}-(\d+)$/', $latestPayment->payment_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = sprintf('PAY-%s-%06d', $year, $nextNumber);
            $exists = Payment::where('payment_number', $candidate)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $candidate;
    }

    /**
     * Calculate order totals (subtotal, tax, discount, total)
     */
    public function calculateOrderTotals(array $items, float $discount = 0.0, float $taxRate = 0.0): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $unitPrice = $item['unit_price'] ?? ($product ? (float) $product->selling_price : 0.0);
            $qty = (int) ($item['quantity'] ?? 1);
            $itemDiscount = (float) ($item['discount'] ?? 0);
            $itemSubtotal = ($unitPrice * $qty) - $itemDiscount;
            $subtotal += max(0, $itemSubtotal);
        }

        $taxAmount = ($subtotal - $discount) * ($taxRate / 100);
        $taxAmount = max(0, $taxAmount);
        $total = max(0, ($subtotal - $discount) + $taxAmount);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Complete a POS Sale transaction executing all required steps:
     * 1. Validate the cart
     * 2. Validate stock
     * 3. Generate a unique invoice number (INV-YYYY-XXXXXX)
     * 4. Create the sale
     * 5. Create sale items
     * 6. Create payment
     * 7. Decrease product stock
     * 8. Create stock movement records
     * 9. Record the cashier
     * 10. Use a database transaction
     * 
     * @throws InvalidArgumentException
     */
    public function processSale(array $data): Sale
    {
        // 10. Use a database transaction
        return DB::transaction(function () use ($data) {
            // 1. Validate the cart
            if (empty($data['items']) || !is_array($data['items'])) {
                throw new InvalidArgumentException('Shopping cart is empty. Please add products before checking out.');
            }

            // 9. Record the cashier
            $cashierId = Auth::id() ?? 1;

            // 2. Validate stock for each item before writing changes
            $lockedProducts = [];
            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) ($item['quantity'] ?? 1);

                if ($quantity <= 0) {
                    throw new InvalidArgumentException("Item quantity must be at least 1.");
                }

                $product = Product::lockForUpdate()->findOrFail($productId);

                if ($product->stock_quantity < $quantity) {
                    throw new InvalidArgumentException(
                        "Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}, requested: {$quantity}."
                    );
                }

                $lockedProducts[$productId] = $product;
            }

            // Financial Calculations
            $subtotal = round((float) ($data['subtotal'] ?? 0.0), 2);
            $discountAmount = round((float) ($data['discount_amount'] ?? 0.0), 2);
            $taxAmount = round((float) ($data['tax_amount'] ?? 0.0), 2);
            $totalAmount = round((float) ($data['total_amount'] ?? 0.0), 2);
            $paidAmount = round((float) ($data['paid_amount'] ?? 0.0), 2);

            // Determine Payment Status and balances
            // Paid, Partial, Unpaid
            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $paymentStatus = 'paid';
                $changeAmount = round($paidAmount - $totalAmount, 2);
                $dueAmount = 0.00;
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
                $changeAmount = 0.00;
                $dueAmount = round($totalAmount - $paidAmount, 2);
            } else {
                $paymentStatus = 'unpaid';
                $changeAmount = 0.00;
                $dueAmount = $totalAmount;
            }

            // 3. Generate unique invoice number (INV-YYYY-XXXXXX)
            $invoiceNo = $this->generateInvoiceNumber();

            // 4. Create the sale record
            $sale = Sale::create([
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $cashierId,
                'invoice_no' => $invoiceNo,
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'due_amount' => $dueAmount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => $paymentStatus,
                'notes' => $data['notes'] ?? null,
            ]);

            // 5. Create sale items & 7. Decrease stock & 8. Create stock movement records
            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $product = $lockedProducts[$productId];
                $unitPrice = (float) ($item['unit_price'] ?? $product->selling_price);
                $discount = (float) ($item['discount'] ?? 0.0);
                $itemSubtotal = (float) ($item['subtotal'] ?? (($unitPrice * $quantity) - $discount));

                // 5. Create sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => (float) ($product->purchase_price ?? 0.0),
                    'discount' => $discount,
                    'tax' => 0.00,
                    'subtotal' => max(0, $itemSubtotal),
                ]);

                // 7. Decrease product stock & 8. Create stock movement record
                $this->inventoryService->adjustStock(
                    product: $product,
                    quantityChange: -$quantity,
                    type: 'sale',
                    reason: "POS Sale Invoice {$sale->invoice_no}",
                    refType: Sale::class,
                    refId: $sale->id,
                    userId: $cashierId
                );
            }

            // 6. Create payment
            $payment = Payment::create([
                'payment_number' => $this->generatePaymentNumber(),
                'payable_type' => Sale::class,
                'payable_id' => $sale->id,
                'user_id' => $cashierId,
                'customer_id' => $sale->customer_id,
                'supplier_id' => null,
                'amount' => $paidAmount,
                'payment_method' => $sale->payment_method,
                'payment_date' => now()->toDateString(),
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes' => "POS Payment for Invoice {$sale->invoice_no} ({$sale->payment_status})",
            ]);

            // Update customer loyalty points and balance
            if (!empty($data['customer_id'])) {
                $customer = Customer::lockForUpdate()->find($data['customer_id']);
                if ($customer) {
                    $customer->increment('total_spent', $sale->total_amount);
                    $pointsEarned = (int) floor($sale->total_amount / 10); // 1 point per $10 spent
                    if ($pointsEarned > 0) {
                        $customer->increment('points', $pointsEarned);
                    }
                    if ($dueAmount > 0) {
                        $customer->increment('balance', $dueAmount);
                    }
                }
            }

            // Trigger Notifications
            $notificationService = app(NotificationService::class);
            $notificationService->notifyNewSale($sale);
            if ($paidAmount > 0) {
                $notificationService->notifyPayment($payment, $sale->invoice_no);
            }

            // Check stock alerts for purchased products
            foreach ($data['items'] as $item) {
                $p = Product::find($item['product_id']);
                if ($p) {
                    $notificationService->checkProductStockAlert($p, $p->stock_quantity);
                }
            }

            // Audit Activity Log
            ActivityLoggerService::log(
                action: 'sale.complete',
                description: "completed sale {$sale->invoice_no}",
                subject: $sale,
                properties: ['total' => $sale->total_amount, 'paid' => $paidAmount, 'status' => $sale->payment_status],
                module: 'sales'
            );

            // Sync to Firebase Cloud Firestore
            try {
                app(\App\Services\FirebaseService::class)->storeSale($sale);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Firebase] PosService sync error: ' . $e->getMessage());
            }

            return $sale;
        });
    }
}
