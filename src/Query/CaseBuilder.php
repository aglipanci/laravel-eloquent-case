<?php

declare(strict_types=1);

namespace AgliPanci\LaravelCase\Query;

use AgliPanci\LaravelCase\Exceptions\CaseBuilderException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Throwable;

class CaseBuilder
{
    public ?string $subject = null;

    /**
     * @var array<int, array{query: string, binding?: int}>
     */
    public array $whens = [];

    /**
     * @var array<int, string>
     */
    public array $thens = [];

    public ?string $else = null;

    /**
     * @var array{when: array<int, mixed>, then: array<int, mixed>, else: array<int, mixed>}
     */
    public array $bindings = [
        'when' => [],
        'then' => [],
        'else' => [],
    ];

    /**
     * @deprecated Use $aggregate instead.
     */
    public bool $sum = false;

    public ?string $aggregate = null;

    public Grammar $grammar;

    public QueryBuilder $queryBuilder;

    public function __construct(
        QueryBuilder $queryBuilder,
        Grammar $grammar
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->grammar = $grammar;
    }

    public function case(mixed $subject): self
    {
        $this->subject = $this->grammar->wrapColumn($subject);

        return $this;
    }

    public function caseRaw(mixed $subject): self
    {
        $this->subject = (string) $subject;

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Throwable
     */
    public function when(mixed $column, mixed $operator = null, mixed $value = null): self
    {
        throw_if(
            ! $this->subject && func_num_args() === 1,
            CaseBuilderException::subjectMustBePresentWhenCaseOperatorNotUsed()
        );

        throw_unless(
            count($this->whens) === count($this->thens),
            CaseBuilderException::wrongWhenPosition()
        );

        [$value, $operator] = $this->queryBuilder->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        if (isset($value)) {
            $this->addBinding($value, 'when');

            $this->whens[] = [
                'query' => $this->grammar->wrapColumn($column).' '.$operator.' ?',
                'binding' => count($this->bindings['when']) - 1,
            ];
        } elseif (func_num_args() === 1) {
            $this->addBinding($column, 'when');

            $this->whens[] = [
                'query' => '?',
                'binding' => count($this->bindings['when']) - 1,
            ];
        } else {
            $operator = $operator === '=' ? 'IS' : 'IS NOT';

            $this->whens[] = [
                'query' => $this->grammar->wrapColumn($column).' '.$operator.' NULL',
            ];
        }

        return $this;
    }

    /**
     * Compare two columns in a WHEN condition.
     *
     * @return $this
     *
     * @throws Throwable
     */
    public function whenColumn(mixed $first, mixed $operator = null, mixed $second = null): self
    {
        throw_unless(
            count($this->whens) === count($this->thens),
            CaseBuilderException::wrongWhenPosition()
        );

        if (func_num_args() === 2) {
            [$second, $operator] = [$operator, '='];
        }

        $this->whens[] = [
            'query' => $this->grammar->wrapColumn($first).' '.$operator.' '.$this->grammar->wrapColumn($second),
        ];

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function whenRaw(string $expression, mixed $bindings = []): self
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
    public function then(mixed $value): self
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
     * Use a column as the THEN result.
     *
     * @return $this
     *
     * @throws Throwable
     */
    public function thenColumn(mixed $column): self
    {
        throw_if(
            count($this->whens) == count($this->thens),
            CaseBuilderException::thenCannotBeBeforeWhen()
        );

        $this->addBinding([], 'then');

        $this->thens[] = $this->grammar->wrapColumn($column);

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function thenRaw(mixed $value, mixed $bindings = []): self
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
    public function else(mixed $value): self
    {
        $this->guardAgainstInvalidElse();

        $this->else = '?';

        $this->addBinding($value, 'else');

        return $this;
    }

    /**
     * Use a column as the ELSE result.
     *
     * @return $this
     *
     * @throws Throwable
     */
    public function elseColumn(mixed $column): self
    {
        $this->guardAgainstInvalidElse();

        $this->else = $this->grammar->wrapColumn($column);

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function elseRaw(mixed $value, mixed $bindings = []): self
    {
        $this->guardAgainstInvalidElse();

        $this->else = (string) $value;

        $this->addBinding($bindings, 'else');

        return $this;
    }

    /**
     * @throws Throwable
     */
    protected function guardAgainstInvalidElse(): void
    {
        throw_unless(
            is_null($this->else),
            CaseBuilderException::elseIsPresent()
        );

        throw_if(
            count($this->whens) === 0 || count($this->whens) !== count($this->thens),
            CaseBuilderException::elseCanOnlyBeAfterAWhenThen()
        );
    }

    /**
     * Wrap the CASE statement in an aggregate function.
     *
     * @return $this
     */
    public function aggregate(string $function): self
    {
        $this->aggregate = $function;

        return $this;
    }

    public function sum(): self
    {
        $this->sum = true;

        return $this->aggregate('sum');
    }

    public function count(): self
    {
        return $this->aggregate('count');
    }

    public function avg(): self
    {
        return $this->aggregate('avg');
    }

    public function min(): self
    {
        return $this->aggregate('min');
    }

    public function max(): self
    {
        return $this->aggregate('max');
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
        $sql = $this->toSql();

        $bindings = array_map(
            fn ($parameter) => is_string($parameter) ? $this->grammar->wrapValue($parameter) : $parameter,
            $this->getBindings()
        );

        /**
         * Substitute the bindings in a single pass over the compiled SQL, so
         * placeholder characters inside already-substituted values are never
         * mistaken for the next placeholder.
         */
        $raw = '';
        $offset = 0;

        foreach ($bindings as $binding) {
            $position = strpos($sql, '?', $offset);

            if ($position === false) {
                break;
            }

            $raw .= substr($sql, $offset, $position - $offset).$binding;
            $offset = $position + 1;
        }

        return $raw.substr($sql, $offset);
    }

    /**
     * @return $this
     *
     * @throws Throwable
     */
    public function addBinding(mixed $value, string $type): CaseBuilder
    {
        throw_unless(
            array_key_exists($type, $this->bindings),
            InvalidArgumentException::class,
            "Invalid binding type: {$type}."
        );

        $this->bindings[$type][] = $value;

        return $this;
    }

    /**
     * @return array<int, mixed>
     */
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

            if (array_key_exists($i, $this->bindings['then'])) {
                if (is_array($this->bindings['then'][$i])) {
                    $bindings = array_merge($bindings, $this->bindings['then'][$i]);
                } else {
                    $bindings[] = $this->bindings['then'][$i];
                }
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
