<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exact_rate_limits', function (Blueprint $table) {
            $table->id();
            
            // Relationship to connection
            $table->foreignId('connection_id')
                ->constrained('exact_connections')
                ->cascadeOnDelete();
            
            // Daily rate limits
            $table->integer('daily_limit')->nullable()->comment('Daily API call limit');
            $table->integer('daily_remaining')->nullable()->comment('Remaining daily API calls');
            $table->integer('daily_reset_at')->nullable()->comment('Unix timestamp when daily limit resets');
            
            // Minutely rate limits
            $table->integer('minutely_limit')->nullable()->comment('Minutely API call limit (usually 60)');
            $table->integer('minutely_remaining')->nullable()->comment('Remaining minutely API calls');
            $table->integer('minutely_reset_at')->nullable()->comment('Unix timestamp when minutely limit resets');
            
            // Tracking
            $table->timestamp('last_checked_at')->nullable()->comment('Last time rate limits were checked');
            $table->integer('total_calls_today')->default(0)->comment('Total API calls made today');
            
            $table->timestamps();
            
            // Indexes
            $table->unique('connection_id');
            $table->index('daily_reset_at');
            $table->index('minutely_reset_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exact_rate_limits');
    }
};
