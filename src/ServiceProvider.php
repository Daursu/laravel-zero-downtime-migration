<?php

namespace Daursu\ZeroDowntimeMigration;

use Daursu\ZeroDowntimeMigration\Connections\GhostConnection;
use Daursu\ZeroDowntimeMigration\Connections\PtOnlineSchemaChangeConnection;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
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
    }
}
