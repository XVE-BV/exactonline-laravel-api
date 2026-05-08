<?php

namespace XVE\Exactonline\Commands;

use Illuminate\Console\Command;

class ExactonlineCommand extends Command
{
    public $signature = 'exactonline';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
