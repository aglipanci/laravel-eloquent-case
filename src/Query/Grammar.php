<?php

namespace AgliPanci\LaravelCase\Query;

use AgliPanci\LaravelCase\Exceptions\InvalidCaseBuilder;

class Grammar
{
    /**
     * @throws \Throwable
     */
    public function compile(CaseBuilder $caseStatement): string
    {
        throw_if(
            ! isset($caseStatement->whens) || ! isset($caseStatement->thens),
            InvalidCaseBuilder::noConditionsPresent()
        );

        throw_if(
            count($caseStatement->whens) !== count($caseStatement->thens),
            InvalidCaseBuilder::numberOfConditionsNotMatching()
        );

        $components = ['case'];

        if ($caseStatement->subject) {
            $components[] = $caseStatement->subject;
        }

        foreach ($caseStatement->whens as $i => $when) {
            $components[] = 'when';
            $components[] = $when;
            $components[] = 'then';
            $components[] = $caseStatement->thens[$i];
        }

        if ($caseStatement->else) {
            $components[] = 'else';
            $components[] = $caseStatement->else;
        }

        $components[] = 'end';

        $sql = trim(implode(' ', $components));

        if ($caseStatement->sum) {
            $sql = 'sum('.$sql.')';
        }

        return $sql;
    }
}
