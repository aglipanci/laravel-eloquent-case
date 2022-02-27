<?php

namespace AgliPanci\LaravelCase\Tests;

use AgliPanci\LaravelCase\LaravelCaseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [ LaravelCaseServiceProvider::class, ];
    }
}
