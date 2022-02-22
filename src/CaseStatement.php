<?php

namespace AgliPanci\Case;

use AgliPanci\Case\Exceptions\InvalidCaseStatement;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class CaseStatement
{
    /**
     * @var string|null
     */
    private ?string $subject = null;

    /**
     * @var array
     */
    private array $when;

    /**
     * @var array
     */
    private array $then;

    /**
     * @var string|null
     */
    private ?string $else = null;

    /**
     * @var array
     */
    private array $bindings = [];

    /**
     * @var \Illuminate\Support\Stringable
     */
    private Stringable $caseQuery;

    /**
     * @var bool
     */
    private bool $sum = false;

    /**
     * The base query builder instance.
     *
     * @var QueryBuilder
     */
    protected QueryBuilder $query;

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    public function case($subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param  mixed  $column
     * @param $operator
     * @param $value
     * @return $this
     * @throws \Throwable
     */
    public function when(mixed $column, $operator = null, $value = null): self
    {
        throw_if(
            ! $this->subject && func_num_args() === 1,
            InvalidCaseStatement::subjectMustBePresentWhenCaseOperatorNotUsed()
        );

        [$value, $operator] = $this->query->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($value) {
            $this->when[] = $column.' '.$operator.' ?';
            $this->bindings[] = $value;
        } elseif ($operator) {
            $this->when[] = $column.' ?';
            $this->bindings[] = $operator;
        } else {
            $this->when[] = $column;
        }

        return $this;
    }

    public function whenRaw(string $expression, $bindings = []): self
    {
        $this->when[] = $expression;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    public function then($value): self
    {
        $this->then[] = '?';
        $this->bindings[] = $value;

        return $this;
    }

    public function thenRaw($value, $bindings = []): self
    {
        $this->then[] = $value;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function else($value): self
    {
        throw_if(
            $this->else,
            InvalidCaseStatement::elseIsPresent()
        );

        $this->else = '?';
        $this->bindings[] = $value;

        return $this;
    }

    public function elseRaw($value, $bindings = []): self
    {
        $this->else = $value;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    public function sum(): self
    {
        $this->sum = true;

        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function build(): self
    {
        throw_if(
            ! isset($this->when) || ! isset($this->then),
            InvalidCaseStatement::noConditionsPresent()
        );

        throw_if(
            count($this->when) !== count($this->then),
            InvalidCaseStatement::numberOfConditionsNotMatching()
        );

        /**
         * TODO: if sum is present all THEN/ELSE should be integers.
         */
        $this->caseQuery = Str::of('CASE ')
            ->when($this->subject,
                fn(Stringable $query) => $query->append($this->subject.' ')
            )
            ->when(true, function (Stringable $query) {
                for ($i = 0; $i < count($this->when); $i++) {
                    $query = $query
                        ->append('WHEN ')
                        ->append($this->when[$i].' ')
                        ->append('THEN ')
                        ->append($this->then[$i].' ');
                }

                return $query;
            })
            ->when($this->else,
                fn($query) => $query->append('ELSE ')->append($this->else.' ')
            )
            ->append('END')
            ->when($this->sum,
                fn(Stringable $query) => $query->prepend('SUM(')->append(')')
            );

        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function getQuery(): string
    {
        return $this->build()->caseQuery->toString();
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}
