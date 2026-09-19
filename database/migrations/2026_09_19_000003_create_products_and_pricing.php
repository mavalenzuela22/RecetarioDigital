<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipe_id')->unique()->constrained()->restrictOnDelete();
            $table->enum('sale_unit', ['piece']);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_cost_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->unsignedBigInteger('reference_order_quantity');
            $table->uuid('request_key')->unique();
            $table->char('request_hash', 64);
            $table->timestamps();
            $table->unique(['product_id', 'version_number']);
        });

        Schema::create('product_cost_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_cost_profile_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->string('concept', 120);
            $table->unsignedBigInteger('amount_minor');
            $table->enum('allocation', ['batch', 'unit', 'order']);
            $table->timestamps();
            $table->unique(['product_cost_profile_id', 'position']);
        });

        Schema::create('product_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('price_minor');
            $table->timestamp('effective_at');
            $table->uuid('request_key')->unique();
            $table->char('request_hash', 64);
            $table->timestamps();
            $table->index(['product_id', 'effective_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('product_cost_components');
        Schema::dropIfExists('product_cost_profiles');
        Schema::dropIfExists('products');
    }
};
