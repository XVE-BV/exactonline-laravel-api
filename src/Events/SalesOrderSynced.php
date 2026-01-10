<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skylence\ExactonlineLaravelApi\Contracts\HasExactMapping;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Results\SyncResult;

class SalesOrderSynced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model&HasExactMapping  $model
     */
    public function __construct(
        public ExactConnection $connection,
        public Model $model,
        public SyncResult $result
    ) {}
}
