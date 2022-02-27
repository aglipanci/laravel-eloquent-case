<?php

namespace AgliPanci\LaravelCase\Exceptions;

use Exception;

final class InvalidCaseBuilderException extends Exception
{
    /**
     * @return static
     */
    public static function elseIsPresent(): InvalidCaseBuilderException
    {
        return new static('ELSE statement is already present. The CASE statement can have only one ELSE.');
    }

    /**
     * @return static
     */
    public static function noConditionsPresent(): InvalidCaseBuilderException
    {
        return new static('The CASE statement must have at least one WHEN/THEN condition.');
    }

    /**
     * @return static
     */
    public static function numberOfConditionsNotMatching(): InvalidCaseBuilderException
    {
        return new static('The CASE statement must have a matching number of WHEN/THEN conditions.');
    }

    /**
     * @return static
     */
    public static function subjectMustBePresentWhenCaseOperatorNotUsed(): InvalidCaseBuilderException
    {
        return new static('The CASE statement subject must be present when operator and column are not present.');
    }

    /**
     * @return static
     */
    public static function thenCannotBeBeforeWhen(): InvalidCaseBuilderException
    {
        return new static('THEN cannot be before WHEN on a CASE statement.');
    }

    /**
     * @return static
     */
    public static function elseCanOnlyBeAfterAWhenThen(): InvalidCaseBuilderException
    {
        return new static('ELSE can only be set after a WHEN/THEN in a CASE statement.');
    }

    /**
     * @return static
     */
    public static function wrongWhenPosition(): InvalidCaseBuilderException
    {
        return new static('Wrong WHEN position.');
    }
}
