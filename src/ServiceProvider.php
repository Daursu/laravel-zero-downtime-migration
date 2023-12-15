<?php

namespace Daursu\ZeroDowntimeMigration;

use Daursu\ZeroDowntimeMigration\Connections\GhostConnection;
use Daursu\ZeroDowntimeMigration\Connections\PtOnlineSchemaChangeConnection;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use ReflectionClass;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/zero-downtime-migrations.php',
            'zero-downtime-migrations'
        );

        $this->publishes([
            __DIR__ . '/config/zero-downtime-migrations.php' => config_path('zero-downtime-migrations.php'),
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Connection::resolverFor('pt-online-schema-change', function ($connection, $database, $prefix, $config) {
            return new PtOnlineSchemaChangeConnection($connection, $database, $prefix, $config);
        });

        Connection::resolverFor('gh-ost', function ($connection, $database, $prefix, $config) {
            return new GhostConnection($connection, $database, $prefix, $config);
        });

        $this->app->bind('db.connector.pt-online-schema-change', function () {
            return new MySqlConnector;
        });

        $this->app->bind('db.connector.gh-ost', function () {
            return new MySqlConnector;
        });

        $this->app->bind(Blueprint::class, function ($app, $args = []) {
            return $this->createInstance(BatchableBlueprint::class, $args);
        });
    }

    private function createInstance(string $class, array $args)
    {
        return call_user_func_array(
            [new ReflectionClass($class), 'newInstance'],
            $args
        );
    }
}
