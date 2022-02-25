<?php

namespace AgliPanci\LaravelCase;

use AgliPanci\LaravelCase\Query\Grammar;
use Closure;
use AgliPanci\LaravelCase\Query\CaseBuilder;
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
        Builder::macro('case', function (Closure|CaseBuilder $statement, string $as) {

            if ($statement instanceof Closure) {
                $callback = $statement;

                $callback($statement = new CaseBuilder($this, new Grammar()));
            }

            $this->selectRaw(
                '('.$statement->toSql().') as '.$this->grammar->wrap($as), $statement->getBindings()
            );
        });

        $this->app->bind('casebuilder',
            fn($app) => $app->make(CaseBuilder::class)
        );
    }
}
