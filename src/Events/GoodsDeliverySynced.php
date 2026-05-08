<?php

declare(strict_types=1);

namespace XVE\Exactonline\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XVE\Exactonline\Contracts\HasExactMapping;
use XVE\Exactonline\Models\ExactConnection;
use XVE\Exactonline\Support\Results\SyncResult;

class GoodsDeliverySynced
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
