<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'type')) {
                $table->enum('type', ['Regular', 'VIP', 'Wholesale'])->default('Regular')->index()->after('gender');
            }
            if (!Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 12, 2)->default(0.00)->after('type');
            }
            if (!Schema::hasColumn('customers', 'balance')) {
                $table->decimal('balance', 12, 2)->default(0.00)->after('credit_limit');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'balance')) {
                $table->decimal('balance', 12, 2)->default(0.00)->after('tax_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['gender', 'type', 'credit_limit', 'balance']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['balance']);
        });
    }
};
