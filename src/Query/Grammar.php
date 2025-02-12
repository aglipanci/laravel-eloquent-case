<?php

namespace AgliPanci\LaravelCase\Query;

class Grammar
{
    /**
     * @throws \Throwable
     */
    public function compile(CaseBuilder $caseBuilder): string
    {
        $components = ['case'];

        if ($caseBuilder->subject) {
            $components[] = $caseBuilder->subject;
        }

        foreach ($caseBuilder->whens as $i => $when) {
            $components[] = 'when';
            $components[] = $when['query'];
            $components[] = 'then';
            $components[] = $caseBuilder->thens[$i];
        }

        if ($caseBuilder->else !== null) {
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

    public function wrapColumn($value): string
    {
        $values = explode('.', $value);

        if (count($values) === 2) {
            return '`'.str_replace('`', '``', $values[0]).'`.'.'`'.str_replace('`', '``', $values[1]).'`';
        }

        return '`'.str_replace('`', '``', $value).'`';
    }

    public function wrapValue($value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }
}
