<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('recipe_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('name', 120);
            $table->unsignedBigInteger('expected_yield');
            $table->text('instructions')->nullable();
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('snapshot_batch_cost_micros')->nullable();
            $table->unsignedBigInteger('snapshot_unit_cost_micros')->nullable();
            $table->uuid('request_key')->unique();
            $table->char('request_hash', 64);
            $table->timestamps();
            $table->unique(['recipe_id', 'version_number']);
        });

        Schema::create('recipe_ingredient_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipe_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedBigInteger('quantity_milli');
            $table->enum('unit', ['g', 'kg', 'ml', 'l', 'piece']);
            $table->unsignedBigInteger('normalized_quantity_milli');
            $table->foreignId('snapshot_purchase_id')->nullable()->constrained('ingredient_purchases')->restrictOnDelete();
            $table->unsignedBigInteger('snapshot_unit_cost_micros')->nullable();
            $table->unsignedBigInteger('snapshot_usage_cost_micros')->nullable();
            $table->timestamps();
            $table->unique(['recipe_version_id', 'ingredient_id']);
            $table->unique(['recipe_version_id', 'position']);
            $table->index(['ingredient_id', 'recipe_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredient_lines');
        Schema::dropIfExists('recipe_versions');
        Schema::dropIfExists('recipes');
    }
};
