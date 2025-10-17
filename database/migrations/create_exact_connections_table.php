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
        Schema::create('exact_connections', function (Blueprint $table) {
            $table->id();

            // Multi-user and multi-tenant support
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();

            // Exact Online specifics
            $table->string('division')->nullable()->comment('Exact Online division/administration ID');

            // OAuth tokens (encrypted in model)
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->integer('token_expires_at')->nullable()->comment('Unix timestamp when access token expires');
            $table->timestamp('last_token_refresh_at')->nullable()->comment('Last successful token refresh');
            $table->integer('refresh_token_expires_at')->nullable()->comment('Unix timestamp when refresh token expires (30 days from acquisition)');

            // OAuth configuration
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable(); // Encrypted in model
            $table->string('redirect_url')->nullable();
            $table->string('base_url')->default('https://start.exactonline.nl')->comment('Base URL for different regions');

            // Connection status
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();

            // Metadata
            $table->string('name')->nullable()->comment('Friendly name for this connection');
            $table->json('metadata')->nullable()->comment('Additional connection metadata');

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['tenant_id', 'is_active']);
            $table->index('token_expires_at');
            $table->index('refresh_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exact_connections');
    }
};
