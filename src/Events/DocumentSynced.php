<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XVE\ExactonlineLaravelApi\Contracts\HasExactMapping;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Support\Results\SyncResult;

class DocumentSynced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Model&HasExactMapping  $model
     */
    public function __construct(
        public ExactConnection $connection,
        public Model $model,
        public SyncResult $result
    ) {}
}
