<?php

namespace AgliPanci\LaravelCase\Facades;

use AgliPanci\LaravelCase\Query\CaseBuilder as CaseBuilderQuery;
use Illuminate\Support\Facades\Facade;

/**
 * @see \AgliPanci\LaravelCase\Query\CaseBuilder
 */
class CaseBuilder extends Facade
{
    protected static function getFacadeAccessor()
    {
        self::clearResolvedInstance(CaseBuilderQuery::class);

        return CaseBuilderQuery::class;
    }
}
