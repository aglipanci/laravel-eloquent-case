<?php

namespace AgliPanci\Case;

use Closure;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder;

class LaravelCaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Builder::macro('case', function (Closure|CaseStatement $statement, string $as) {

            if ($statement instanceof Closure) {
                $callback = $statement;

                $callback($statement = app(CaseStatement::class));
            }

            $this->selectRaw(
                '('.$statement->getQuery().') as '.$this->grammar->wrap($as), $statement->getBindings()
            );
        });

        $this->app->bind('casestatement',
            fn($app) => $app->make(CaseStatement::class)
        );
    }
}
