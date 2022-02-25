<?php

namespace AgliPanci\LaravelCase\Exceptions;

use Exception;

class InvalidCaseBuilderException extends Exception
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

    public static function thenCannotBeBeforeWhen(): static
    {
        return new static('THEN cannot be before WHEN on a CASE statement.');
    }

    public static function elseCanOnlyBeAfterAWhenThen(): static
    {
        return new static('ELSE can only be set after a WHEN/THEN in a CASE statement.');
    }

    public static function wrongWhenPosition(): static
    {
        return new static('Wrong WHEN position.');
    }
}
