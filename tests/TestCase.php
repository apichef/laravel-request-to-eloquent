<?php

namespace LaravelRequestToEloquent;

use LaravelJsonApiQueryParams\JsonApiQueryParamsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->artisan('migrate', ['--database' => 'testbench']);
        $this->withFactories(__DIR__.'/database/factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            JsonApiQueryParamsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('query-params', [
            'include' => 'include',
            'filter' => 'filter',
            'sort' => 'sort',
            'fields' => 'fields',
        ]);
    }
}
