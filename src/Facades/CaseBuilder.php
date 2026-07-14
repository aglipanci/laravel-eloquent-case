<?php

declare(strict_types=1);

namespace AgliPanci\LaravelCase\Facades;

use AgliPanci\LaravelCase\Query\CaseBuilder as CaseBuilderQuery;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CaseBuilderQuery case(mixed $subject)
 * @method static CaseBuilderQuery caseRaw(mixed $subject)
 * @method static CaseBuilderQuery when(mixed $column, mixed $operator = null, mixed $value = null)
 * @method static CaseBuilderQuery whenColumn(mixed $first, mixed $operator = null, mixed $second = null)
 * @method static CaseBuilderQuery whenRaw(string $expression, mixed $bindings = [])
 * @method static CaseBuilderQuery then(mixed $value)
 * @method static CaseBuilderQuery thenColumn(mixed $column)
 * @method static CaseBuilderQuery thenRaw(mixed $value, mixed $bindings = [])
 * @method static CaseBuilderQuery else(mixed $value)
 * @method static CaseBuilderQuery elseColumn(mixed $column)
 * @method static CaseBuilderQuery elseRaw(mixed $value, mixed $bindings = [])
 *
 * @see CaseBuilderQuery
 */
class CaseBuilder extends Facade
{
    protected static function getFacadeAccessor()
    {
        /**
         * Facades cache the resolved instance by accessor, but every static
         * call chain must start from a fresh CaseBuilder, otherwise state
         * from a previous CASE statement would leak into the next one.
         */
        self::clearResolvedInstance(CaseBuilderQuery::class);

        return CaseBuilderQuery::class;
    }
}
