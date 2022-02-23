<?php

namespace AgliPanci\LaravelCase\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AgliPanci\LaravelCase\CaseStatement
 */
class CaseBuilder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'casebuilder';
    }
}
