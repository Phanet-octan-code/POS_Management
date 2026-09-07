<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0.00)->after('purchase_date');
            $table->decimal('tax_amount', 12, 2)->default(0.00)->after('subtotal');
            $table->decimal('discount_amount', 12, 2)->default(0.00)->after('tax_amount');
            $table->string('payment_method', 50)->nullable()->default('cash')->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax_amount', 'discount_amount', 'payment_method']);
        });
    }
};
