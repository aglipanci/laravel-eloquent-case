<?php

namespace AgliPanci\LaravelCase\Query;

use AgliPanci\LaravelCase\Exceptions\InvalidCaseBuilderException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
    public array $bindings = [
        'when' => [],
        'then' => [],
        'else' => [],
    ];

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
        $this->subject = $this->grammar->wrapColumn($subject);

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
            $this->whens[] = $this->grammar->wrapColumn($column).' '.$operator.' ?';

            $this->addBinding($value, 'when');
        } elseif ($operator) {
            $this->whens[] = $this->grammar->wrapColumn($column).' ?';

            $this->addBinding($operator, 'when');
        } else {
            $this->whens[] = $column;
        }

        return $this;
    }

    public function whenRaw(string $expression, $bindings = []): self
    {
        $this->whens[] = $expression;

        $this->addBinding($bindings, 'when');

        return $this;
    }

    public function then($value): self
    {
        $this->thens[] = '?';

        $this->addBinding($value, 'then');

        return $this;
    }

    public function thenRaw($value, $bindings = []): self
    {
        $this->thens[] = $value;

        $this->addBinding($bindings, 'then');

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

        $this->addBinding($value, 'else');

        return $this;
    }

    public function elseRaw($value, $bindings = []): self
    {
        $this->else = $value;

        $this->addBinding($bindings, 'else');

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
        $bindings = array_map(
            fn($parameter) => is_string($parameter) ? $this->grammar->wrapValue($parameter) : $parameter,
            $this->getBindings()
        );

        return Str::replaceArray(
            '?',
            $bindings,
            $this->toSql()
        );
    }

    public function addBinding(mixed $value, string $type): static
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        $this->bindings[$type][] = $value;

        return $this;
    }

    public function getBindings(): array
    {
        $bindings = [];

        /**
         * Flattening here is to handle raw cases with multiple bindings.
         */
        foreach ($this->whens as $i => $when) {
            if (is_array($this->bindings['when'][$i])) {
                $bindings = array_merge($bindings, $this->bindings['when'][$i]);
            } else {
                $bindings[] = $this->bindings['when'][$i];
            }

            if (is_array($this->bindings['then'][$i])) {
                $bindings = array_merge($bindings, $this->bindings['then'][$i]);
            } else {
                $bindings[] = $this->bindings['then'][$i];
            }
        }

        return array_merge($bindings, Arr::flatten($this->bindings['else']));
    }

    /**
     * @throws \Throwable
     */
    public function toQuery(): QueryBuilder
    {
        return $this->queryBuilder->newQuery()->selectRaw($this->toSql(), $this->getBindings());
    }
}
