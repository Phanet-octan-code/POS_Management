<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('location')->default('Store Shelf')->index();
            $table->integer('quantity')->default(0);
            $table->string('batch_number')->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->timestamps();

            $table->index(['product_id', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
