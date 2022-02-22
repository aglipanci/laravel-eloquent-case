<?php

namespace AgliPanci\Case\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AgliPanci\Case\CaseStatement
 */
class CaseStatement extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'casestatement';
    }
}
