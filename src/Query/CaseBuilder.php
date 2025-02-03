<?php

namespace AgliPanci\LaravelCase\Query;

use AgliPanci\LaravelCase\Exceptions\CaseBuilderException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class CaseBuilder
{
    public ?string $subject = null;

    public array $whens = [];

    public array $thens = [];

    public ?string $else = null;

    public array $bindings = [
        'when' => [],
        'then' => [],
        'else' => [],
    ];

    public bool $sum = false;

    public Grammar $grammar;

    public QueryBuilder $queryBuilder;

    /**
     * @param QueryBuilder $queryBuilder
     * @param Grammar $grammar
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        Grammar      $grammar
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->grammar = $grammar;
    }

    public function case($subject): self
    {
        $this->subject = $this->grammar->wrapColumn($subject);

        return $this;
    }

    public function caseRaw($subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     * @throws Throwable
     */
    public function when($column, $operator = null, $value = null): self
    {
        throw_if(
            ! $this->subject && func_num_args() === 1,
            CaseBuilderException::subjectMustBePresentWhenCaseOperatorNotUsed()
        );

        throw_unless(
            count($this->whens) === count($this->thens),
            CaseBuilderException::wrongWhenPosition()
        );

        [ $value, $operator ] = $this->queryBuilder->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        if (isset($value)) {
            $this->addBinding($value, 'when');

            $this->whens[] = [
                'query' => $this->grammar->wrapColumn($column) . ' ' . $operator . ' ?',
                'binding' => count($this->bindings['when']) - 1,
            ];
        } elseif (is_null($value)) {
            $operator = $operator === '=' ? 'IS' : 'IS NOT';

            $this->whens[] = [
                'query' => $this->grammar->wrapColumn($column) . ' ' . $operator . ' NULL',
            ];
        } elseif ($operator) {
            $this->addBinding($operator, 'when');

            $this->whens[] = [
                'query' => $this->grammar->wrapColumn($column) . ' ?',
                'binding' => count($this->bindings['when']) - 1,
            ];
        } else {
            $this->whens[] = [
                'query' => $column,
            ];
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function whenRaw(string $expression, $bindings = []): self
    {
        throw_unless(
            count($this->whens) === count($this->thens),
            CaseBuilderException::wrongWhenPosition()
        );

        $this->addBinding($bindings, 'when');

        $this->whens[] = [
            'query' => $expression,
            'binding' => count($this->bindings['when']) - 1,
        ];

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function then($value): self
    {
        throw_if(
            count($this->whens) == count($this->thens),
            CaseBuilderException::thenCannotBeBeforeWhen()
        );

        $this->addBinding($value, 'then');

        $this->thens[] = '?';

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function thenRaw($value, $bindings = []): self
    {
        throw_if(
            count($this->whens) == count($this->thens),
            CaseBuilderException::thenCannotBeBeforeWhen()
        );

        $this->thens[] = $value;

        $this->addBinding($bindings, 'then');

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function else($value): self
    {
        throw_if(
            $this->else,
            CaseBuilderException::elseIsPresent()
        );

        throw_if(
            count($this->whens) === 0 || count($this->whens) !== count($this->thens),
            CaseBuilderException::elseCanOnlyBeAfterAWhenThen()
        );

        $this->else = '?';

        $this->addBinding($value, 'else');

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function elseRaw($value, $bindings = []): self
    {
        throw_if(
            count($this->whens) === 0,
            CaseBuilderException::elseCanOnlyBeAfterAWhenThen()
        );

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
     * @throws Throwable
     */
    public function toSql(): string
    {
        throw_if(
            ! count($this->whens) || ! count($this->thens),
            CaseBuilderException::noConditionsPresent()
        );

        throw_if(
            count($this->whens) !== count($this->thens),
            CaseBuilderException::numberOfConditionsNotMatching()
        );

        return $this->grammar->compile($this);
    }

    /**
     * @throws Throwable
     */
    public function toRaw(): string
    {
        $bindings = array_map(
            fn ($parameter) => is_string($parameter) ? $this->grammar->wrapValue($parameter) : $parameter,
            $this->getBindings()
        );

        return Str::replaceArray(
            '?',
            $bindings,
            $this->toSql()
        );
    }

    /**
     * @param mixed $value
     * @param  string  $type
     * @return $this
     * @throws \Throwable
     */
    public function addBinding($value, string $type): CaseBuilder
    {
        throw_unless(
            array_key_exists($type, $this->bindings),
            InvalidArgumentException::class,
            "Invalid binding type: {$type}."
        );

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
            if (array_key_exists('binding', $when)) {
                if (is_array($this->bindings['when'][$when['binding']])) {
                    $bindings = array_merge($bindings, $this->bindings['when'][$when['binding']]);
                } else {
                    $bindings[] = $this->bindings['when'][$when['binding']];
                }
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
     * @throws Throwable
     */
    public function toQuery(): QueryBuilder
    {
        return $this->queryBuilder->newQuery()->selectRaw($this->toSql(), $this->getBindings());
    }
}
