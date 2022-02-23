<?php

namespace AgliPanci\LaravelCase\Query;

use AgliPanci\LaravelCase\Exceptions\InvalidCaseBuilderException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

class CaseBuilder
{
    /**
     * @var string|null
     */
    public ?string $subject = null;

    /**
     * @var array
     */
    public array $whens;

    /**
     * @var array
     */
    public array $thens;

    /**
     * @var string|null
     */
    public ?string $else = null;

    /**
     * @var array
     */
    public array $bindings = [];

    /**
     * @var bool
     */
    public bool $sum = false;

    /**
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \AgliPanci\LaravelCase\Query\Grammar  $grammar
     */
    public function __construct(
        protected QueryBuilder $queryBuilder,
        protected Grammar $grammar
    ) {

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
            InvalidCaseBuilderException::subjectMustBePresentWhenCaseOperatorNotUsed()
        );

        [$value, $operator] = $this->queryBuilder->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($value) {
            $this->whens[] = $column.' '.$operator.' ?';
            $this->bindings[] = $value;
        } elseif ($operator) {
            $this->whens[] = $column.' ?';
            $this->bindings[] = $operator;
        } else {
            $this->whens[] = $column;
        }

        return $this;
    }

    public function whenRaw(string $expression, $bindings = []): self
    {
        $this->whens[] = $expression;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    public function then($value): self
    {
        $this->thens[] = '?';
        $this->bindings[] = $value;

        return $this;
    }

    public function thenRaw($value, $bindings = []): self
    {
        $this->thens[] = $value;
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
            InvalidCaseBuilderException::elseIsPresent()
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
    public function toSql(): string
    {
        return $this->grammar->compile($this);
    }

    /**
     * @throws \Throwable
     */
    public function toRaw(): string
    {
        return Str::replaceArray('?', $this->getBindings(), $this->toSql());
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * @throws \Throwable
     */
    public function toQuery(): QueryBuilder
    {
        return $this->queryBuilder->newQuery()->selectRaw($this->toSql(), $this->getBindings());
    }
}
