<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Models\ExactDivision;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exact_connections', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable()->after('division');
            $table->index('division_id');
            $table->foreign('division_id')
                ->references('id')
                ->on('exact_divisions')
                ->nullOnDelete();
        });

        ExactConnection::query()
            ->whereNotNull('division')
            ->each(function (ExactConnection $connection): void {
                $divisionId = ExactDivision::query()
                    ->where('connection_id', $connection->id)
                    ->where('code', $connection->division)
                    ->value('id');

                if ($divisionId === null) {
                    return;
                }

                $connection->forceFill(['division_id' => (int) $divisionId])->save();
            });
    }

    public function down(): void
    {
        Schema::table('exact_connections', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropIndex(['division_id']);
            $table->dropColumn('division_id');
        });
    }
};
