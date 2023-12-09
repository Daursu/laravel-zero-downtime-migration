<?php

namespace Integration;

use Daursu\ZeroDowntimeMigration\ServiceProvider;
use Daursu\ZeroDowntimeMigration\ZeroDowntimeSchema;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class DatabaseMigrationTest extends TestCase
{
    private $options = [];

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $serviceProvider = new ServiceProvider($app);
        $serviceProvider->register();

        // Setup default database to use MySQL and the zero-downtime connection
        tap($app['config'], function (Repository $config) {
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
            ]);

            $config->set('database.connections.zero-downtime', [
                'driver' => 'pt-online-schema-change',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'params' => [
                    '--nocheck-replication-filters',
                    '--nocheck-unique-key-change',
                    '--recursion-method=none',
                    '--chunk-size=2000',
                ],
            ]);

            $config->set('database.connections.ghost', [
                'driver' => 'gh-ost',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'params' => [
                    '--allow-on-master',
                    '--switch-to-rbr',
                    '--aliyun-rds', // This command ignores the port the database is running on, if proxied locally via docker
                    '--initially-drop-old-table',
                ],
            ]);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app['config']->get('database.default') !== 'testing') {
            $this->artisan('db:wipe', ['--drop-views' => true]);
        }

        $this->options = [
            '--path' => realpath(__DIR__.'/stubs/'),
            '--realpath' => true,
        ];

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback', $this->options);
        });
    }

    public function testZeroDowntimeMigrationHasSuccessfullyExecutedUsingPerconaToolkit()
    {
        $this->artisan('migrate', $this->options);

        $this->assertTrue(Schema::hasTable('members'));
        $this->assertTrue(Schema::hasColumn('members', 'age'));
        $this->assertTrue(Schema::hasColumn('members', 'description'));
    }

    public function testZeroDowntimeMigrationHasSuccessfullyExecutedUsingGhost()
    {
        ZeroDowntimeSchema::$connection = 'ghost';

        $this->artisan('migrate', $this->options);

        $this->assertTrue(Schema::hasTable('members'));
        $this->assertTrue(Schema::hasColumn('members', 'age'));
        $this->assertTrue(Schema::hasColumn('members', 'description'));
    }
}
