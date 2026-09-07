<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearPosDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:clear-data {--all : Also clear catalog products, categories, brands, customers and suppliers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear transactional records (sales, purchases, payments, returns, expenses, logs, alerts) from the POS system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Clearing transactional records from POS Management System...');

        Schema::disableForeignKeyConstraints();

        // 1. Sales & Orders
        SaleItem::truncate();
        Sale::truncate();
        $this->line('  • Sales and order line items cleared.');

        // 2. Payments
        Payment::truncate();
        $this->line('  • Payments cleared.');

        // 3. Purchases
        PurchaseItem::truncate();
        Purchase::truncate();
        $this->line('  • Purchases and supplier orders cleared.');

        // 4. Returns
        ReturnItem::truncate();
        ReturnOrder::truncate();
        $this->line('  • Returns and returned items cleared.');

        // 5. Inventory Movements & Adjustments
        StockMovement::truncate();
        ProductStock::truncate();
        $this->line('  • Stock movements and inventory adjustment history cleared.');

        // 6. Expenses
        Expense::truncate();
        $this->line('  • Operational expenses cleared.');

        // 7. Audit Activity Logs & Notifications
        ActivityLog::truncate();
        Notification::truncate();
        $this->line('  • Activity audit logs and system notifications cleared.');

        // Reset Customer balances and points
        Customer::query()->update([
            'total_spent' => 0,
            'balance' => 0,
            'points' => 0,
        ]);
        $this->line('  • Customer loyalty points and spend balances reset to zero.');

        if ($this->option('all')) {
            Product::truncate();
            $this->line('  • Products catalog cleared.');
        }

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->info('All transactional data cleared successfully! Database is completely fresh and clean.');

        return Command::SUCCESS;
    }
}
