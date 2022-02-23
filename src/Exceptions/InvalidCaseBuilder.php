<?php

namespace AgliPanci\LaravelCase\Exceptions;

use Exception;

class InvalidCaseBuilder extends Exception
{
    public static function elseIsPresent(): static
    {
        return new static('ELSE statement is already present. The CASE statement can have only one ELSE.');
    }

    public static function noConditionsPresent(): static
    {
        return new static('The CASE statement must have at least one WHEN/THEN condition.');
    }

    public static function numberOfConditionsNotMatching(): static
    {
        return new static('The CASE statement must have a matching number of WHEN/THEN conditions.');
    }

    public static function subjectMustBePresentWhenCaseOperatorNotUsed(): static
    {
        return new static('The CASE statement subject must be present when operator and column are not present.');
    }
}
