<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->char('name_key', 64)->unique();
            $table->enum('canonical_unit', ['g', 'ml', 'piece']);
            $table->timestamps();
        });
        Schema::create('ingredient_purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('total_paid_minor');
            $table->unsignedBigInteger('purchase_quantity_milli');
            $table->unsignedBigInteger('normalized_quantity_milli');
            $table->unsignedBigInteger('normalized_unit_cost_micros');
            $table->enum('purchase_unit', ['g', 'kg', 'ml', 'l', 'piece']);
            $table->string('presentation', 120);
            $table->date('purchased_on');
            $table->string('store', 120)->nullable();
            $table->text('note')->nullable();
            $table->uuid('request_key')->unique();
            $table->timestamps();
            $table->index(['ingredient_id', 'purchased_on', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_purchases');
        Schema::dropIfExists('ingredients');
    }
};
