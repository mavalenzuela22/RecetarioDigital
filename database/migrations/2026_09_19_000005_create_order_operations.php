<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table): void {
            $table->date('local_payment_date')->nullable()->after('kind');
            $table->uuid('request_key')->nullable()->unique()->after('local_payment_date');
            $table->char('request_hash', 64)->nullable()->after('request_key');
        });

        Schema::create('order_fulfillment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_state', 32);
            $table->string('to_state', 32);
            $table->uuid('request_key')->unique();
            $table->char('request_hash', 64);
            $table->timestamp('effective_at');
            $table->timestamps();
            $table->index(['order_id', 'effective_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfillment_events');
        Schema::table('order_payments', function (Blueprint $table): void {
            $table->dropUnique(['request_key']);
            $table->dropColumn(['local_payment_date', 'request_key', 'request_hash']);
        });
    }
};
