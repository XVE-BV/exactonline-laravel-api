<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class DivisionsSynced
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ExactConnection $connection,
        public readonly int $created,
        public readonly int $updated,
        public readonly int $total
    ) {}
}
