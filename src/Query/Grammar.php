<?php

namespace AgliPanci\LaravelCase\Query;

use AgliPanci\LaravelCase\Exceptions\InvalidCaseBuilderException;

class Grammar
{
    /**
     * @throws \Throwable
     */
    public function compile(CaseBuilder $caseBuilder): string
    {
        throw_if(
            ! isset($caseBuilder->whens) || ! isset($caseBuilder->thens),
            InvalidCaseBuilderException::noConditionsPresent()
        );

        throw_if(
            count($caseBuilder->whens) !== count($caseBuilder->thens),
            InvalidCaseBuilderException::numberOfConditionsNotMatching()
        );

        $components = ['case'];

        if ($caseBuilder->subject) {
            $components[] = $caseBuilder->subject;
        }

        foreach ($caseBuilder->whens as $i => $when) {
            $components[] = 'when';
            $components[] = $when;
            $components[] = 'then';
            $components[] = $caseBuilder->thens[$i];
        }

        if ($caseBuilder->else) {
            $components[] = 'else';
            $components[] = $caseBuilder->else;
        }

        $components[] = 'end';

        $sql = trim(implode(' ', $components));

        if ($caseBuilder->sum) {
            $sql = 'sum('.$sql.')';
        }

        return $sql;
    }
}
