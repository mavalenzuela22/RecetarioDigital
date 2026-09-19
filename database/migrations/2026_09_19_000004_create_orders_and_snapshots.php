<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->char('request_hash', 64);
            $table->string('customer_name', 120);
            $table->date('delivery_date');
            $table->time('delivery_time');
            $table->text('notes')->nullable();
            $table->string('fulfillment_state', 32)->default('confirmed');
            $table->string('payment_state', 32);
            $table->unsignedBigInteger('total_minor');
            $table->unsignedBigInteger('paid_minor');
            $table->unsignedBigInteger('balance_minor');
            $table->timestamps();
            $table->index(['delivery_date', 'delivery_time']);
        });

        Schema::create('order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 120);
            $table->enum('sale_unit', ['piece']);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('agreed_unit_price_minor');
            $table->unsignedBigInteger('line_revenue_minor');
            $table->foreignId('product_price_id')->nullable()->constrained('product_prices')->restrictOnDelete();
            $table->foreignId('recipe_version_id')->nullable()->constrained('recipe_versions')->restrictOnDelete();
            $table->foreignId('product_cost_profile_id')->nullable()->constrained('product_cost_profiles')->restrictOnDelete();
            $table->unsignedBigInteger('attributable_unit_cost_micros')->nullable();
            $table->unsignedBigInteger('attributable_line_cost_micros')->nullable();
            $table->boolean('cost_complete')->default(false);
            $table->timestamps();
            $table->unique(['order_id', 'product_id']);
        });

        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('kind', 32);
            $table->timestamp('effective_at');
            $table->timestamps();
            $table->index(['order_id', 'effective_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
