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
        Schema::create('exact_webhooks', function (Blueprint $table) {
            $table->id();

            // Relationship to connection
            $table->foreignId('connection_id')
                ->constrained('exact_connections')
                ->cascadeOnDelete();

            // Exact Online webhook details
            $table->string('webhook_id')->nullable()->comment('Exact Online webhook ID');
            $table->string('topic')->index()->comment('Webhook topic/event type (e.g., Accounts, SalesInvoices)');
            $table->string('callback_url')->comment('URL where webhooks will be received');
            $table->string('webhook_secret')->nullable()->comment('Secret for webhook signature validation');

            // Status
            $table->boolean('is_active')->default(true)->index();

            // Metadata
            $table->json('metadata')->nullable()->comment('Additional webhook metadata');
            $table->timestamp('last_received_at')->nullable()->comment('Last time a webhook was received');
            $table->integer('events_received')->default(0)->comment('Count of events received');

            $table->timestamps();

            // Indexes
            $table->unique(['connection_id', 'topic']);
            $table->index(['connection_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exact_webhooks');
    }
};
