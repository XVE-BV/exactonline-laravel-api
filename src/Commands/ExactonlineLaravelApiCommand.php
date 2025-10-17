<?php

namespace Skylence\ExactonlineLaravelApi\Commands;

use Illuminate\Console\Command;

class ExactonlineLaravelApiCommand extends Command
{
    public $signature = 'exactonline-laravel-api';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
