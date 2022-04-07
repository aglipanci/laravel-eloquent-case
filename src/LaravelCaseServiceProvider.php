<?php

namespace AgliPanci\LaravelCase;

use AgliPanci\LaravelCase\Query\CaseBuilder;
use AgliPanci\LaravelCase\Query\Grammar;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

class LaravelCaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Builder::macro('case', function ($caseBuilder, string $as) {
            if ($caseBuilder instanceof Closure) {
                $callback = $caseBuilder;

                $callback($caseBuilder = new CaseBuilder($this, new Grammar()));
            }

            /** @var Builder $this */
            $this->selectRaw(
                '('.$caseBuilder->toSql().') as '.$this->grammar->wrap($as),
                $caseBuilder->getBindings()
            );

            return $this;
        });

        $this->app->bind(
            CaseBuilder::class,
            fn ($app) => new CaseBuilder($app->make(Builder::class), new Grammar())
        );
    }
}
