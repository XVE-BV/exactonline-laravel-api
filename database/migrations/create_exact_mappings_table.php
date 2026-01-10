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
        Schema::create('exact_mappings', function (Blueprint $table): void {
            $table->id();
            $table->morphs('mappable');
            $table->foreignId('connection_id')
                ->constrained('exact_connections')
                ->cascadeOnDelete();
            $table->string('division');
            $table->string('environment', 20)->default('production');
            $table->string('exact_id');
            $table->string('exact_code')->nullable();
            $table->string('reference_type')->default('primary');
            $table->timestamp('synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            // Unique constraint: one mapping per model per connection per reference type
            $table->unique(
                ['mappable_type', 'mappable_id', 'connection_id', 'reference_type'],
                'exact_mappings_unique'
            );

            // Indexes for lookups
            $table->index('exact_id');
            $table->index('division');
            $table->index('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exact_mappings');
    }
};
