<?php

namespace Aesis\Storage\Tests;

use Aesis\Storage\StorageServiceProvider;
use Aesis\Storage\Tests\Fixtures\Owner;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public Owner $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('vendor:publish', [
            '--tag' => 'storage-migrations',
            '--force' => true,
        ]);
        $this->artisan('migrate');

    }

    protected function getPackageProviders($app)
    {
        return [
            StorageServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }
}
