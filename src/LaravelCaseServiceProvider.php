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
        Builder::macro('case', function (Closure|CaseBuilder $caseBuilder, string $as) {

            if ($caseBuilder instanceof Closure) {
                $callback = $caseBuilder;

                $callback($caseBuilder = new CaseBuilder($this, new Grammar()));
            }

            $this->selectRaw(
                '('.$caseBuilder->toSql().') as '.$this->grammar->wrap($as), $caseBuilder->getBindings()
            );
        });

        $this->app->bind('casebuilder',
            fn($app) => $app->make(CaseBuilder::class)
        );
    }
}
