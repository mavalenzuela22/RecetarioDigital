<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
            $table->string('google_subject')->nullable()->unique();
            $table->boolean('active')->default(true)->index();
            $table->boolean('is_admin')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_subject']);
            $table->dropColumn(['google_subject', 'active', 'is_admin']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
