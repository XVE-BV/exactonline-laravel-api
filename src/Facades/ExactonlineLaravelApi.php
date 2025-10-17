<?php

namespace Skylence\ExactonlineLaravelApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Skylence\ExactonlineLaravelApi\ExactonlineLaravelApi
 */
class ExactonlineLaravelApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Skylence\ExactonlineLaravelApi\ExactonlineLaravelApi::class;
    }
}
