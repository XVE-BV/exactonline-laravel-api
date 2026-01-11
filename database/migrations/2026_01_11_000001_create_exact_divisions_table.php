<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exact_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')
                ->constrained('exact_connections')
                ->cascadeOnDelete();

            // Division info from Exact Online
            $table->integer('code')->comment('Division code (primary key in Exact)');
            $table->string('description')->nullable();
            $table->string('hid')->nullable()->comment('Human-readable ID given by customer');
            $table->string('customer_code')->nullable()->comment('Owner account code');
            $table->string('customer_name')->nullable()->comment('Owner account name');

            // Location & settings
            $table->string('country', 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('vat_number')->nullable();

            // Status
            $table->boolean('is_main')->default(false)->comment('True for main/hosting division');
            $table->integer('status')->default(0)->comment('0=active, 1=archived');
            $table->integer('blocking_status')->default(0)->comment('0=not blocked');

            // Timestamps from Exact
            $table->timestamp('started_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            // Local timestamps
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['connection_id', 'code']);
            $table->index('code');
            $table->index('is_main');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exact_divisions');
    }
};
