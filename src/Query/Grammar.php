<?php

namespace AgliPanci\LaravelCase\Query;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Grammar
{
    public function __construct(
        protected QueryBuilder $queryBuilder
    ) {}

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
        return $this->queryBuilder->getGrammar()->wrap($value);
    }

    public function wrapValue($value): string
    {
        $connection = $this->queryBuilder->getConnection();

        if ($connection instanceof Connection) {
            return $connection->escape($value);
        }

        return "'".str_replace("'", "''", $value)."'";
    }
}
