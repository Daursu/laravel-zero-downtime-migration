# laravel-zero-downtime-migration
Zero downtime migrations with Laravel and `pt-online-schema-change`

NOTE: works only with MySQL databases, including (Percona & MariaDB).

## Installation
Compatible with Laravel `5.5` & `5.6`.

#### Prerequisites
Make sure you have `percona-toolkit` installed.
- On Mac you can install it using brew `brew install percona-toolkit`.
- On Debian/Ubuntu ` apt-get install percona-toolkit`.

#### Installation steps
1. Run `composer require daursu/laravel-zero-downtime-migration`
2. (Optional) Add the service provider to your `config/app.php` file, if you are not using autoloading.
```
Daursu\ZeroDowntimeMigration\ServiceProvider::class,
```
3. Update your `config/database.php` and add a new connection:
```
'connections' => [
    'pt-online-schema-change' => [
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
When writing a new migration, specify the `pt-online-schema-change` connection to use, and all your commands will run through `pt-online-schema-change`.

```
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhoneNumberToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pt-online-schema-change')->table('users', function (Blueprint $table) {
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
        Schema::connection('pt-online-schema-change')->table('users', function (Blueprint $table) {
            $table->dropColumn('phone-number');
        });
    }
}
```

Run `php artisan:migrate`

## Configuration
All the configuration is done inside `config/database.php` on the connection itself.
You can pass down custom flags to the raw `pt-online-schema-change` command. Simply add the parameters you want inside the `options` array like so:
```
'options' => [
    '--nocheck-replication-filters',
    '--nocheck-unique-key-change',
    '--recursion-method=none',
    '--chunk-size=2000',
]
```

You can find all the possible options here:
https://www.percona.com/doc/percona-toolkit/LATEST/pt-online-schema-change.html

### Replication
If your database is running in a cluster with replication, then you need to configure how `pt-online-schema-changes` finds your replica slaves.
Here's an example setup, but feel free to customize it to your own needs

```
'options' => [
    '--nocheck-replication-filters',
    '--nocheck-unique-key-change',
    '--recursion-method=dsn=D=database_name,t=dsns',
    '--chunk-size=2000',
]
```

1. Replace `database_name` with your database name.
2. Create a new table called `dsns`
```
CREATE TABLE `dsns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `dsn` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
```
3. Add a new row for each replica you have, example
```
INSERT INTO `dsns` (`id`, `parent_id`, `dsn`)
VALUES
	(1, NULL, 'h=my-replica-1.example.org,P=3306');
```

## Gotchas
- This only works with MySQL, Percona & MariaDB
- Use this tool when you need to alter a table, not when creating or dropping tables.
