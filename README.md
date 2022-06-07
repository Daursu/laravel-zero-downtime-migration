# laravel-zero-downtime-migration

Zero downtime migrations with Laravel and `gh-ost` or `pt-online-schema-change`.

NOTE: works only with MySQL databases, including (Percona & MariaDB).

## Installation

Compatible with Laravel `5.5`, `5.6`, `5.7`, `5.8`, `6.0`, `7.0`, `8.0` & `9.0`

#### Prerequisites

If you are using `gh-ost` then make sure you download the binary from their releases page:

- <https://github.com/github/gh-ost/releases>

If you are using `pt-online-schema-change` then make sure you have `percona-toolkit` installed.

- On Mac you can install it using brew `brew install percona-toolkit`.
- On Debian/Ubuntu `apt-get install percona-toolkit`.

#### Installation steps

1. Run `composer require l-alexandrov/laravel-zero-downtime-migration`
2. (Optional) Add the service provider to your `config/app.php` file, if you are not using autoloading.

```php
LAlexandrov\ZeroDowntimeMigration\ServiceProvider::class,
```

3. Update your `config/database.php` and add a new connection:

This package support `pt-online-schema-change` and `gh-ost`. Below are the configurations for each package:

###### gh-ost

```php
'connections' => [
    'zero-downtime' => [
        'driver' => 'gh-ost',
        
        // This is your master write access database connection details
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        
        // Additional options, depending on your setup
        // all options available here: https://github.com/github/gh-ost/blob/master/doc/cheatsheet.md
        'options' => [
            '--max-load=Threads_running=25',
            '--critical-load=Threads_running=1000',
            '--chunk-size=1000',
            '--throttle-control-replicas=myreplica.1.com,myreplica.2.com',
            '--max-lag-millis=1500',
            '--verbose',
            '--switch-to-rbr',
            '--exact-rowcount',
            '--concurrent-rowcount',
            '--default-retries=120',
        ],
    ],
],
```

###### pt-online-schema-change

```php
'connections' => [
    'zero-downtime' => [
        'driver' => 'pt-online-schema-change',
        
        // This is your master write access database connection details
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        
        // Additional options, depending on your setup
        // all options available here: https://www.percona.com/doc/percona-toolkit/LATEST/pt-online-schema-change.html
        'options' => [
            '--nocheck-replication-filters',
            '--nocheck-unique-key-change',
            '--recursion-method=none',
            '--chunk-size=2000',
        ],
    ],
],
```

## Usage

When writing a new migration, use the helper facade `ZeroDowntimeSchema` instead of Laravel's `Schema`,
and all your commands will run through `gh-ost` or `pt-online-schema-change`.

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use LAlexandrov\ZeroDowntimeMigration\ZeroDowntimeSchema;

class AddPhoneNumberToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ZeroDowntimeSchema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ZeroDowntimeSchema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone-number');
        });
    }
}
```

Run `php artisan:migrate`

## Configuration

All the configuration is done inside `config/database.php` on the connection itself.
You can pass down custom flags to the raw `pt-online-schema-change` command.
Simply add the parameters you want inside the `options` array like so:

```php
'options' => [
    '--nocheck-replication-filters',
    '--nocheck-unique-key-change',
    '--recursion-method=none',
    '--chunk-size=2000',
]
```

You can find all the possible options here:
<https://www.percona.com/doc/percona-toolkit/LATEST/pt-online-schema-change.html>

### Tests

The `ZeroDowntimeSchema` facades allows you disable running `pt-online-schema-change` during tests.
To do so, in your base test case `TestCase.php` under the setUp method add the following:

```php
public function setUp()
{
   // ... existing code
   ZeroDowntimeSchema::disable();
}
```

This will disable `pt-online-schema-change` and all the migrations using the helper facade will run
through the default laravel migrator.

### Custom connection name

By default, the connection name used by `ZeroDowntimeSchema` helper is set to `zero-downtime`, however you can
override this if you called your connection something else in `config/database.php`.

To do so, in your `AppServiceProvider.php` add the following under the `boot()` method:

```php
public function boot()
{
    // ... existing code
    ZeroDowntimeSchema::$connection = 'your-custom-name';
}
```

### Replication

If your database is running in a cluster with replication, then you need to
configure how `pt-online-schema-changes` finds your replica slaves.
Here's an example setup, but feel free to customize it to your own needs

```php
'options' => [
    '--nocheck-replication-filters',
    '--nocheck-unique-key-change',
    '--recursion-method=dsn=D=database_name,t=dsns',
    '--chunk-size=2000',
]
```

1. Replace `database_name` with your database name.
2. Create a new table called `dsns`

```mysql
CREATE TABLE `dsns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `dsn` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
```

3. Add a new row for each replica you have, example

```mysql
INSERT INTO `dsns` (`id`, `parent_id`, `dsn`)
VALUES
 (1, NULL, 'h=my-replica-1.example.org,P=3306');
```

## Gotchas

- This only works with MySQL, Percona & MariaDB
- Use this tool when you need to alter a table, not when creating or dropping tables.
